<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionOrderItem extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'production_order_id',
        'branch_id',
        'product_id',
        'quantity_required',
        'quantity_consumed',
        'unit_id',
        'unit_cost',
        'total_cost',
        'warehouse_id',
        'is_issued',
        'issued_at',
    ];

    protected $casts = [
        'quantity_required' => 'decimal:4',
        'quantity_consumed' => 'decimal:4',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'is_issued' => 'boolean',
        'issued_at' => 'datetime',
    ];

    /**
     * Get the production order.
     */
    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    /**
     * Get the product (raw material).
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
     * Get the warehouse.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Calculate remaining quantity to consume.
     */
    public function getRemainingQuantityAttribute(): float
    {
        return $this->quantity_required - $this->quantity_consumed;
    }

    /**
     * Issue material from warehouse.
     */
    public function issue(): void
    {
        $this->update([
            'is_issued' => true,
            'issued_at' => now(),
        ]);
    }
}
