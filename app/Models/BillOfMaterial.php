<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillOfMaterial extends BaseModel
{
    protected ?string $moduleKey = 'manufacturing';

    protected $table = 'bills_of_materials';

    /**
     * DB-first canonical columns (see create_manufacturing_tables migration).
     */
    protected $fillable = [
        'reference_number',
        'branch_id',
        'product_id',

        'name',
        'name_ar',
        'version',
        'status',

        // Defines the base output quantity for this BOM. Components quantities are defined for this base.
        'quantity',

        // Scrap/loss factor at BOM level (assembly losses)
        'scrap_percentage',
        'is_multi_level',

        'estimated_cost',
        'estimated_time_hours',

        'notes',
        'description',
        'custom_fields',

        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'scrap_percentage' => 'decimal:2',
        'is_multi_level' => 'boolean',
        'estimated_cost' => 'decimal:4',
        'estimated_time_hours' => 'decimal:2',
        'custom_fields' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (BillOfMaterial $bom) {
            if (! $bom->reference_number) {
                $bom->reference_number = $bom->generateReferenceNumber();
            }
        });
    }

    // Relationships
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Finished product for this BOM.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BomItem::class, 'bom_id');
    }

    public function operations(): HasMany
    {
        return $this->hasMany(BomOperation::class, 'bom_id');
    }

    public function productionOrders(): HasMany
    {
        return $this->hasMany(ProductionOrder::class, 'bom_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    // Accessors
    public function getDisplayNameAttribute(): string
    {
        return $this->reference_number ?: 'BOM-' . $this->id;
    }

    // Reference number generation
    public function generateReferenceNumber(): string
    {
        $prefix = 'BOM-' . date('Ym') . '-';

        $count = static::query()
            ->when($this->branch_id, fn ($q) => $q->where('branch_id', $this->branch_id))
            ->where('reference_number', 'like', $prefix . '%')
            ->count();

        return $prefix . str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Cost per ONE finished unit (not per BOM batch).
     */
    public function calculateMaterialCost(): float
    {
        $baseOutput = max(0.0001, decimal_float($this->quantity ?? 1, 4));
        $totalCostPerUnit = 0.0;

        foreach ($this->items as $item) {
            $componentQtyPerBom = decimal_float($item->quantity ?? 0, 4);
            $componentQtyPerUnit = $componentQtyPerBom / $baseOutput;

            $itemScrapFactor = 1 + (decimal_float($item->scrap_percentage ?? 0, 2) / 100);
            $unitCost = decimal_float($item->unit_cost ?? 0, 4);

            $totalCostPerUnit += ($componentQtyPerUnit * $itemScrapFactor) * $unitCost;
        }

        // Apply BOM-level scrap factor (assembly losses)
        $bomScrap = decimal_float($this->scrap_percentage ?? 0, 2);
        $bomYieldFactor = max(0.0001, (100 - $bomScrap) / 100);

        return $totalCostPerUnit / $bomYieldFactor;
    }

    public function calculateLaborCost(): float
    {
        // Placeholder: can be derived from routing/operations.
        return 0.0;
    }

    public function calculateOverheadCost(): float
    {
        // Placeholder: can be derived from overhead rates.
        return 0.0;
    }

    public function calculateTotalCost(): float
    {
        return $this->calculateMaterialCost() + $this->calculateLaborCost() + $this->calculateOverheadCost();
    }

    public function updateEstimatedCosts(): void
    {
        $this->update([
            'estimated_cost' => $this->calculateTotalCost(),
        ]);
    }

    /**
     * Required materials for a given production quantity.
     *
     * @return array<int, array<string, mixed>>
     */
    public function calculateRequiredMaterials(float $productionQuantity = 1): array
    {
        $baseOutput = max(0.0001, decimal_float($this->quantity ?? 1, 4));
        $scale = $productionQuantity / $baseOutput;

        $requiredMaterials = [];

        foreach ($this->items as $item) {
            $componentQtyPerBom = decimal_float($item->quantity ?? 0, 4);
            $materialQuantity = $componentQtyPerBom * $scale;

            $scrapFactor = 1 + (decimal_float($item->scrap_percentage ?? 0, 2) / 100);
            $materialQuantity *= $scrapFactor;

            $unitCost = decimal_float($item->unit_cost ?? 0, 4);

            $requiredMaterials[] = [
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name,
                'quantity_required' => $materialQuantity,
                'unit_id' => $item->unit_id,
                'unit_name' => $item->unit?->name,
                'unit_cost' => $unitCost,
                'total_cost' => $materialQuantity * $unitCost,
            ];
        }

        return $requiredMaterials;
    }

    public function checkMaterialAvailability(float $productionQuantity = 1, ?int $warehouseId = null): array
    {
        $requiredMaterials = $this->calculateRequiredMaterials($productionQuantity);
        $availability = [];

        foreach ($requiredMaterials as $material) {
            $product = Product::find($material['product_id']);
            if (! $product) {
                continue;
            }

            $availableQuantity = $warehouseId
                ? $product->getStockQuantity($warehouseId)
                : $product->getTotalStockQuantity();

            $availability[] = [
                'product_id' => $material['product_id'],
                'product_name' => $material['product_name'],
                'required_quantity' => $material['quantity_required'],
                'available_quantity' => $availableQuantity,
                'shortage' => max(0, $material['quantity_required'] - $availableQuantity),
                'is_available' => $availableQuantity >= $material['quantity_required'],
            ];
        }

        return $availability;
    }

    public function validateQuantities(): bool
    {
        foreach ($this->items as $item) {
            if (decimal_float($item->quantity ?? 0, 4) <= 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Guard for UI/service when adding a component.
     */
    public function canAddComponent(int $productId): bool
    {
        if ($productId === (int) $this->product_id) {
            return false;
        }

        return ! $this->detectCircularDependency($productId);
    }

    /**
     * Service-facing detailed check (used by ManufacturingService).
     */
    public function checkCircularDependency(): array
    {
        foreach ($this->items as $item) {
            if ($this->detectCircularDependency((int) $item->product_id)) {
                return [
                    'has_circular' => true,
                    'message' => 'Circular dependency detected in BOM components.',
                ];
            }
        }

        return [
            'has_circular' => false,
            'message' => null,
        ];
    }

    public function hasCircularDependency(): bool
    {
        return (bool) ($this->checkCircularDependency()['has_circular'] ?? false);
    }

    private function detectCircularDependency(int $componentProductId, array $visited = []): bool
    {
        if (in_array($componentProductId, $visited, true)) {
            return true;
        }

        $visited[] = $componentProductId;

        $subBom = static::query()
            ->where('product_id', $componentProductId)
            ->where('status', 'active')
            ->first();

        if (! $subBom) {
            return false;
        }

        foreach ($subBom->items as $subItem) {
            if ($this->detectCircularDependency((int) $subItem->product_id, $visited)) {
                return true;
            }
        }

        return false;
    }

    public function getHierarchyLevel(): int
    {
        $level = 0;
        $currentBom = $this;

        while (true) {
            $parentBom = static::query()
                ->whereHas('items', function ($q) use ($currentBom) {
                    $q->where('product_id', $currentBom->product_id);
                })
                ->first();

            if (! $parentBom) {
                break;
            }

            $level++;
            $currentBom = $parentBom;

            if ($level > 10) {
                break;
            }
        }

        return $level;
    }

    public function getAllComponents(array $components = [], int $level = 0): array
    {
        if ($level > 10) {
            return $components;
        }

        foreach ($this->items as $item) {
            $components[] = [
                'level' => $level,
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name,
                'quantity' => $item->quantity,
                'scrap_percentage' => $item->scrap_percentage,
                'is_optional' => $item->is_optional,
                'unit_id' => $item->unit_id,
                'unit_name' => $item->unit?->name,
                'unit_cost' => $item->unit_cost,
            ];

            $subBom = static::query()
                ->where('product_id', $item->product_id)
                ->where('status', 'active')
                ->first();

            if ($subBom) {
                $components = $subBom->getAllComponents($components, $level + 1);
            }
        }

        return $components;
    }

    public function getComponentTree(): array
    {
        $tree = [];

        foreach ($this->items as $item) {
            $node = [
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name,
                'quantity' => $item->quantity,
                'scrap_percentage' => $item->scrap_percentage,
                'unit_id' => $item->unit_id,
                'unit_name' => $item->unit?->name,
                'children' => [],
            ];

            $subBom = static::query()
                ->where('product_id', $item->product_id)
                ->where('status', 'active')
                ->first();

            if ($subBom) {
                $node['children'] = $subBom->getComponentTree();
            }

            $tree[] = $node;
        }

        return $tree;
    }
}
