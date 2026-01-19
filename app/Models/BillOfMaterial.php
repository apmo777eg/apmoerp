<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BillOfMaterial extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'bills_of_materials';

    /**
     * Fillable fields aligned with migration:
     * 2026_01_04_000009_create_manufacturing_tables.php
     */
    protected $fillable = [
        'branch_id',
        'product_id',
        'reference_number',
        'name',
        'version',
        'quantity',
        'yield_percentage',
        'estimated_cost',
        'estimated_time_hours',
        'status',
        'notes',
        'custom_fields',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'yield_percentage' => 'decimal:2',
        'estimated_cost' => 'decimal:4',
        'estimated_time_hours' => 'decimal:2',
        'custom_fields' => 'array',
    ];

    /**
     * Get the branch that owns the BOM.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the finished product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the BOM items (components/materials).
     */
    public function items(): HasMany
    {
        return $this->hasMany(BomItem::class, 'bom_id');
    }

    /**
     * Get the BOM operations.
     */
    public function operations(): HasMany
    {
        return $this->hasMany(BomOperation::class, 'bom_id');
    }

    /**
     * Get production orders using this BOM.
     */
    public function productionOrders(): HasMany
    {
        return $this->hasMany(ProductionOrder::class, 'bom_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Backward compatibility accessors
    public function getBomNumberAttribute()
    {
        return $this->reference_number;
    }

    public function getScrapPercentageAttribute()
    {
        return 100 - $this->yield_percentage;
    }

    public function getIsMultiLevelAttribute(): bool
    {
        return $this->items()->whereHas('product', function ($q) {
            $q->whereHas('bom');
        })->exists();
    }

    public function getMetadataAttribute()
    {
        return $this->custom_fields;
    }

    /**
     * Calculate total material cost for this BOM.
     */
    public function calculateMaterialCost(): float
    {
        $cost = 0.0;

        foreach ($this->items as $item) {
            $productCost = $item->product->cost ?? 0.0;
            $itemQuantity = decimal_float($item->quantity);
            $scrapFactor = 1 + (decimal_float($item->scrap_percentage ?? 0) / 100);

            $cost += $productCost * $itemQuantity * $scrapFactor;
        }

        // Apply BOM-level yield percentage (default to 100% if not set or 0)
        $yieldFactor = decimal_float($this->yield_percentage ?? 100) / 100;
        // Prevent division by zero - if yield is 0 or negative, return raw cost
        if ($yieldFactor > 0) {
            $cost = $cost / $yieldFactor;
        }

        return $cost;
    }

    /**
     * Calculate total labor cost for this BOM.
     */
    public function calculateLaborCost(): float
    {
        return $this->operations->sum(function ($operation) {
            $durationHours = decimal_float($operation->duration_minutes ?? 0) / 60;
            $costPerHour = decimal_float($operation->workCenter->cost_per_hour ?? 0);

            return $durationHours * $costPerHour + decimal_float($operation->labor_cost ?? 0);
        });
    }

    /**
     * Calculate total production cost.
     */
    public function calculateTotalCost(): float
    {
        return $this->calculateMaterialCost() + $this->calculateLaborCost();
    }

    /**
     * Scope: Active BOMs only.
     */
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: Draft BOMs.
     */
    public function scopeDraft(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', 'draft');
    }

    /**
     * Generate next BOM number.
     */
    public static function generateBomNumber(int $branchId): string
    {
        $prefix = 'BOM';
        $date = now()->format('Ym');

        $lastBom = static::where('branch_id', $branchId)
            ->where('reference_number', 'like', "{$prefix}-{$date}-%")
            ->orderByDesc('id')
            ->first();

        if ($lastBom) {
            $lastNumber = (int) substr($lastBom->reference_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $date, $newNumber);
    }

    /**
     * Check for circular dependencies in the Bill of Materials.
     *
     * BUG FIX: Prevents infinite loops during cost calculation.
     * A circular dependency occurs when:
     * - Product A uses Product B as a component
     * - Product B uses Product A as a component (directly or indirectly)
     *
     * This would cause infinite recursion when calculating costs or
     * material requirements, potentially crashing the server.
     *
     * @param int|null $productId Product ID to check (defaults to this BOM's product)
     * @param array $visited Array of already visited product IDs (for recursion)
     * @return array ['has_circular' => bool, 'path' => array] Detection result
     */
    public function checkCircularDependency(?int $productId = null, array $visited = []): array
    {
        $productId = $productId ?? $this->product_id;

        // If we've seen this product before, we have a circular dependency
        if (in_array($productId, $visited, true)) {
            $visited[] = $productId; // Add for complete path
            return [
                'has_circular' => true,
                'path' => $visited,
                'message' => __('Circular dependency detected: :path', [
                    'path' => implode(' → ', array_map(fn($id) => "Product #{$id}", $visited)),
                ]),
            ];
        }

        // Add current product to visited path
        $visited[] = $productId;

        // Get all components (child products) for this product's BOM
        $components = $this->items()
            ->with('product.bom')
            ->get();

        foreach ($components as $component) {
            // If the component itself is a manufactured product (has its own BOM)
            $componentProduct = $component->product;
            
            if ($componentProduct && $componentProduct->bom) {
                // Recursively check the component's BOM
                $result = $componentProduct->bom->checkCircularDependency(
                    $componentProduct->id,
                    $visited
                );

                if ($result['has_circular']) {
                    return $result;
                }
            }
        }

        return [
            'has_circular' => false,
            'path' => $visited,
            'message' => null,
        ];
    }

    /**
     * Validate that adding a component won't create a circular dependency.
     *
     * @param int $componentProductId The product ID to be added as a component
     * @return bool True if safe to add, false if it would create a circular dependency
     */
    public function canAddComponent(int $componentProductId): bool
    {
        // Check if adding this component would create a circle
        // The component is circular if:
        // 1. It's the same as the finished product
        // 2. The component's BOM contains this BOM's product (at any level)

        // Direct self-reference check
        if ($componentProductId === $this->product_id) {
            return false;
        }

        // Check if the component has a BOM that contains our product
        $componentBom = static::where('product_id', $componentProductId)
            ->where('status', 'active')
            ->first();

        if (! $componentBom) {
            // Component is not a manufactured product, no circular dependency possible
            return true;
        }

        // Check if the component's BOM chain eventually leads back to this product
        return ! $this->wouldCreateCircle($componentBom, [$this->product_id]);
    }

    /**
     * Check if a BOM's component chain would lead back to any product in the visited set.
     *
     * @param BillOfMaterial $bom The BOM to check
     * @param array $ancestorProductIds Products that are "upstream" in the chain
     * @return bool True if adding this would create a circle
     */
    protected function wouldCreateCircle(BillOfMaterial $bom, array $ancestorProductIds): bool
    {
        // If this BOM's product is in our ancestors, it's a circle
        if (in_array($bom->product_id, $ancestorProductIds, true)) {
            return true;
        }

        // Add this BOM's product to ancestors for deeper checks
        $ancestorProductIds[] = $bom->product_id;

        // Check each component
        foreach ($bom->items as $item) {
            // If the component itself is in ancestors, it's a circle
            if (in_array($item->product_id, $ancestorProductIds, true)) {
                return true;
            }

            // If the component has its own BOM, check recursively
            $componentBom = static::where('product_id', $item->product_id)
                ->where('status', 'active')
                ->first();

            if ($componentBom && $this->wouldCreateCircle($componentBom, $ancestorProductIds)) {
                return true;
            }
        }

        return false;
    }
}
