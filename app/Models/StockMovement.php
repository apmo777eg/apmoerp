<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends BaseModel
{
    protected ?string $moduleKey = 'inventory';

    protected $table = 'stock_movements';

    // Movement type constants
    public const TYPE_PURCHASE = 'purchase';
    public const TYPE_SALE = 'sale';
    public const TYPE_TRANSFER = 'transfer';
    public const TYPE_TRANSFER_IN = 'transfer_in';
    public const TYPE_TRANSFER_OUT = 'transfer_out';
    public const TYPE_ADJUSTMENT = 'adjustment';
    public const TYPE_RETURN = 'return';
    public const TYPE_INITIAL = 'initial';

    /**
     * Fillable fields aligned with migration:
     * 2026_01_04_000003_create_inventory_tables.php
     */
    protected $fillable = [
        'product_id',
        'branch_id',
        'warehouse_id',
        'batch_id',
        'movement_type',
        'reference_type',
        'reference_id',
        'quantity',
        'unit_cost',
        'stock_before',
        'stock_after',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'stock_before' => 'decimal:4',
        'stock_after' => 'decimal:4',
    ];

    // Disable timestamps since migration only has created_at
    public $timestamps = false;

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function ($m) {
            $m->created_at = $m->created_at ?? now();
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class, 'batch_id');
    }

    /**
     * Polymorphic source document: PurchaseItem, SaleItem, AdjustmentItem, TransferItem, etc.
     */
    public function source(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'reference_type', 'reference_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeIn(Builder $query): Builder
    {
        return $query->where('quantity', '>', 0);
    }

    public function scopeOut(Builder $query): Builder
    {
        return $query->where('quantity', '<', 0);
    }

    public function scopeForProduct(Builder $query, $id): Builder
    {
        return $query->where('product_id', $id);
    }

    public function scopePurchase(Builder $query): Builder
    {
        return $query->where('movement_type', 'purchase');
    }

    public function scopeSale(Builder $query): Builder
    {
        return $query->where('movement_type', 'sale');
    }

    public function scopeTransfer(Builder $query): Builder
    {
        return $query->where('movement_type', 'transfer');
    }

    public function scopeAdjustment(Builder $query): Builder
    {
        return $query->where('movement_type', 'adjustment');
    }

    // Backward compatibility accessors
    public function getQtyAttribute()
    {
        return $this->quantity;
    }

    public function setQtyAttribute($value): void
    {
        $this->attributes['quantity'] = $value;
    }

    public function getDirectionAttribute(): string
    {
        return $this->quantity >= 0 ? 'in' : 'out';
    }

    public function getTypeAttribute(): ?string
    {
        return $this->movement_type;
    }

    public function setTypeAttribute($value): void
    {
        $this->attributes['movement_type'] = $value;
    }
}
