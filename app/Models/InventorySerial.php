<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * InventorySerial - Serial number tracking model
 *
 * V56-CRITICAL-03 FIX: Added HasBranch trait for proper multi-branch scoping.
 * Serial numbers must be isolated by branch to prevent cross-branch access
 * and ensure accurate serial tracking per branch.
 */
class InventorySerial extends Model
{
    use HasBranch;
    protected $fillable = [
        'product_id',
        'warehouse_id',
        'branch_id',
        'serial_number',
        'batch_id',
        'unit_cost',
        'warranty_start',
        'warranty_end',
        'status',
        'customer_id',
        'sale_id',
        'purchase_id',
        'notes',
        'meta',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:4',
        'warranty_start' => 'date',
        'warranty_end' => 'date',
        'meta' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class, 'batch_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    /**
     * Check if serial is in stock
     */
    public function isInStock(): bool
    {
        return $this->status === 'in_stock';
    }

    /**
     * Check if serial is sold
     */
    public function isSold(): bool
    {
        return $this->status === 'sold';
    }

    /**
     * Check if warranty is active
     */
    public function isWarrantyActive(): bool
    {
        if (! $this->warranty_start || ! $this->warranty_end) {
            return false;
        }

        $now = now();

        return $now->between($this->warranty_start, $this->warranty_end);
    }

    /**
     * Scope to get available serials
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', 'in_stock')
            ->whereNotNull('warehouse_id');
    }

    /**
     * Scope to get sold serials
     */
    public function scopeSold(Builder $query): Builder
    {
        return $query->where('status', 'sold');
    }
}
