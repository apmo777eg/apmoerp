<?php

namespace App\Models;

use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SalesReturnItem - Sales return item model
 *
 * V56-CRITICAL-03 FIX: Added HasBranch trait for proper multi-branch scoping.
 * Sales return items must be isolated by branch for accurate inventory
 * and financial tracking per branch.
 */
class SalesReturnItem extends Model
{
    use HasBranch;
    use HasFactory;

    protected $fillable = [
        'sales_return_id',
        'sale_item_id',
        'product_id',
        'branch_id',
        'qty_returned',
        'qty_original',
        'unit_price',
        'unit_cost', // V29-HIGH-02 FIX: Added for proper inventory valuation
        'discount',
        'tax_amount',
        'line_total',
        'condition',
        'reason',
        'notes',
        'restock',
        'restocked_by',
        'restocked_at',
    ];

    // V29-HIGH-02 FIX: Aligned decimal precision with core inventory (qty decimal:4, monetary decimal:4)
    // This ensures consistent precision across SaleItem, StockMovements, and Returns for accurate reconciliation
    protected $casts = [
        'qty_returned' => 'decimal:4',
        'qty_original' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'discount' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'line_total' => 'decimal:4',
        'restock' => 'boolean',
        'restocked_at' => 'datetime',
    ];

    // Condition constants
    public const CONDITION_NEW = 'new';
    public const CONDITION_USED = 'used';
    public const CONDITION_DAMAGED = 'damaged';
    public const CONDITION_DEFECTIVE = 'defective';

    protected static function boot()
    {
        parent::boot();

        // Auto-calculate line total when creating/updating
        // V29-HIGH-02 FIX: Use scale 4 for consistency with other ERP monetary fields
        static::saving(function ($item) {
            $subtotal = bcmul((string)$item->qty_returned, (string)$item->unit_price, 4);
            $total = bcadd($subtotal, (string)$item->tax_amount, 4);
            $item->line_total = bcsub($total, (string)$item->discount, 4);
        });
    }

    /**
     * Relationships
     */
    public function salesReturn(): BelongsTo
    {
        return $this->belongsTo(SalesReturn::class);
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function restockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'restocked_by');
    }

    /**
     * Check if item should be restocked to inventory
     */
    public function shouldRestock(): bool
    {
        return $this->restock && in_array($this->condition, [self::CONDITION_NEW, self::CONDITION_USED]);
    }

    /**
     * Mark item as restocked
     */
    public function markAsRestocked(int $userId): bool
    {
        if ($this->restocked_at) {
            return false; // Already restocked
        }

        $this->update([
            'restocked_by' => $userId,
            'restocked_at' => now(),
        ]);

        return true;
    }

    /**
     * Calculate maximum returnable quantity
     */
    public function getMaxReturnableQty(): float
    {
        // Can't return more than originally sold
        $alreadyReturned = static::where('sale_item_id', $this->sale_item_id)
            ->where('sales_return_id', '!=', $this->sales_return_id ?? 0)
            ->sum('qty_returned');

        return max(0, $this->qty_original - $alreadyReturned);
    }
}
