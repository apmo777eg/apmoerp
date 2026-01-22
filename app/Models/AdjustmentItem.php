<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdjustmentItem extends BaseModel
{
    protected ?string $moduleKey = 'inventory';

    /**
     * Fillable fields aligned with migration:
     * 2026_01_04_000003_create_inventory_tables.php
     */
    protected $fillable = [
        'adjustment_id',
        'branch_id',
        'product_id',
        'system_quantity',
        'counted_quantity',
        'difference',
        'unit_cost',
        'notes',
    ];

    protected $casts = [
        'system_quantity' => 'decimal:4',
        'counted_quantity' => 'decimal:4',
        'difference' => 'decimal:4',
        'unit_cost' => 'decimal:4',
    ];

    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(Adjustment::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Backward compatibility accessor.
     * Original qty field represented the adjustment difference (can be positive or negative).
     * Maps to 'difference' column which stores: counted_quantity - system_quantity
     */
    public function getQtyAttribute()
    {
        return $this->difference;
    }

    public function setQtyAttribute($value): void
    {
        $this->attributes['difference'] = $value;
    }
}
