<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * InventoryTransit Model
 * 
 * Tracks inventory that is in transit between warehouses.
 * This prevents the "vanishing stock" issue where inventory is
 * neither in the source nor destination warehouse during transfer.
 * 
 * V65-BUG-FIX: Added HasBranch trait for proper branch scoping.
 */
class InventoryTransit extends Model
{
    use HasBranch, HasFactory;

    protected $fillable = [
        'branch_id',
        'product_id',
        'from_warehouse_id',
        'to_warehouse_id',
        'stock_transfer_id',
        'reference_type',
        'reference_id',
        'quantity',
        'unit_cost',
        'batch_number',
        'expiry_date',
        'status',
        'shipped_at',
        'expected_arrival',
        'received_at', // V29-LOW-08 FIX: Added for audit/reporting
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'expiry_date' => 'date',
        'shipped_at' => 'datetime',
        'expected_arrival' => 'datetime',
        'received_at' => 'datetime', // V29-LOW-08 FIX
    ];

    // Status constants
    public const STATUS_IN_TRANSIT = 'in_transit';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Get the product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the source warehouse
     */
    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    /**
     * Get the destination warehouse
     */
    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    /**
     * Get the stock transfer
     */
    public function stockTransfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class);
    }

    /**
     * Get the user who created this transit record
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the polymorphic reference (if any)
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Check if transit is still active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_IN_TRANSIT;
    }

    /**
     * Mark as received
     * 
     * V29-LOW-08 FIX: Set received_at timestamp for audit/reporting (lead times, SLA, transit aging)
     */
    public function markAsReceived(): void
    {
        $this->update([
            'status' => self::STATUS_RECEIVED,
            'received_at' => now(),
        ]);
    }

    /**
     * Mark as cancelled
     */
    public function markAsCancelled(): void
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
    }

    /**
     * Scope for active transits
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_IN_TRANSIT);
    }

    /**
     * Scope for specific product
     */
    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope for specific warehouse
     */
    public function scopeForWarehouse($query, int $warehouseId)
    {
        return $query->where(function ($q) use ($warehouseId) {
            $q->where('from_warehouse_id', $warehouseId)
              ->orWhere('to_warehouse_id', $warehouseId);
        });
    }
}
