<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * InventoryBatch - Inventory batch tracking model
 *
 * V56-CRITICAL-03 FIX: Added HasBranch trait for proper multi-branch scoping.
 * Inventory batches must be isolated by branch to prevent cross-branch
 * access and ensure accurate batch tracking per branch.
 */
class InventoryBatch extends Model
{
    use HasBranch;
    protected $table = 'inventory_batches';

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'branch_id',
        'batch_number',
        'manufacturing_date',
        'expiry_date',
        'quantity',
        'unit_cost',
        'supplier_batch_ref',
        'purchase_id',
        'status',
        'notes',
        'meta',
    ];

    protected $casts = [
        'manufacturing_date' => 'date',
        'expiry_date' => 'date',
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
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

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    /**
     * Check if batch is expired
     */
    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    /**
     * Check if batch is depleted
     */
    public function isDepleted(): bool
    {
        return $this->quantity <= 0;
    }

    /**
     * Scope to get active batches
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->where('quantity', '>', 0)
            ->where(function ($q) {
                $q->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>', now());
            });
    }

    /**
     * Scope to get batches expiring soon
     */
    public function scopeExpiringSoon(Builder $query, int $days = 30): Builder
    {
        return $query->where('status', 'active')
            ->where('quantity', '>', 0)
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now(), now()->addDays($days)]);
    }
}
