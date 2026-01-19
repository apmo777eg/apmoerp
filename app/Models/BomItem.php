<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BomItem extends BaseModel
{
    use HasFactory, SoftDeletes;

    /**
     * Fillable fields aligned with migration:
     * 2026_01_04_000009_create_manufacturing_tables.php
     */
    protected $fillable = [
        'bom_id',
        'product_id',
        'quantity',
        'unit_id',
        'scrap_percentage',
        'unit_cost',
        'type',
        'is_optional',
        'notes',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'scrap_percentage' => 'decimal:2',
        'unit_cost' => 'decimal:4',
        'sort_order' => 'integer',
        'is_optional' => 'boolean',
    ];

    /**
     * Get the BOM that owns the item.
     */
    public function bom(): BelongsTo
    {
        return $this->belongsTo(BillOfMaterial::class, 'bom_id');
    }

    /**
     * Get the product (component/material).
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the unit of measure.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'unit_id');
    }

    /**
     * Calculate effective quantity including scrap.
     */
    public function getEffectiveQuantityAttribute(): float
    {
        $baseQuantity = decimal_float($this->quantity);
        $scrapFactor = 1 + (decimal_float($this->scrap_percentage ?? 0) / 100);

        return $baseQuantity * $scrapFactor;
    }

    // Backward compatibility accessors
    public function getIsAlternativeAttribute(): bool
    {
        return $this->type === 'alternative';
    }

    public function getMetadataAttribute()
    {
        return null;
    }
}
