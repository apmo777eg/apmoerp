<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionOrderItem extends BaseModel
{
    protected ?string $moduleKey = 'manufacturing';

    protected $table = 'production_order_items';

    protected $fillable = [
        'production_order_id',
        'branch_id',
        'product_id',
        'warehouse_id',

        'quantity_required',
        'quantity_issued',
        'quantity_consumed',

        'unit_id',
        'unit_cost',
        'total_cost',

        'is_issued',
        'issued_at',
        'issued_by',

        'is_returned',
        'returned_at',
        'returned_by',

        'notes',
        'metadata',
    ];

    protected $casts = [
        'quantity_required' => 'decimal:4',
        'quantity_issued' => 'decimal:4',
        'quantity_consumed' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'total_cost' => 'decimal:4',

        'is_issued' => 'boolean',
        'issued_at' => 'datetime',
        'is_returned' => 'boolean',
        'returned_at' => 'datetime',

        'metadata' => 'array',
    ];

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class, 'production_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function returnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    public function getRemainingQuantityAttribute(): float
    {
        $required = decimal_float($this->quantity_required ?? 0, 4);
        $consumed = decimal_float($this->quantity_consumed ?? 0, 4);

        return max(0.0, $required - $consumed);
    }

    public function isFullyConsumed(): bool
    {
        return decimal_float($this->quantity_consumed ?? 0, 4) >= decimal_float($this->quantity_required ?? 0, 4);
    }

    public function hasShortage(): bool
    {
        return decimal_float($this->quantity_issued ?? 0, 4) < decimal_float($this->quantity_required ?? 0, 4);
    }
}
