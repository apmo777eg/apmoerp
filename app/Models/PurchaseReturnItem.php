<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Purchase Return Item
 *
 * Canonical column for condition is: item_condition
 * (No alias column named "condition" exists in the DB schema.)
 */
class PurchaseReturnItem extends Model
{
    use HasFactory, HasBranch;

    protected $fillable = [
        'purchase_return_id',
        'purchase_item_id',
        'grn_item_id',
        'product_id',
        'branch_id',
        'qty_returned',
        'qty_original',
        'unit_cost',
        'tax_amount',
        'line_total',
        'item_condition',
        'batch_number',
        'expiry_date',
        'reason',
        'notes',
        'deduct_from_stock',
        'deducted_by',
        'deducted_at',
    ];

    protected $casts = [
        'qty_returned' => 'decimal:4',
        'qty_original' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'line_total' => 'decimal:4',
        'deduct_from_stock' => 'boolean',
        'deducted_at' => 'datetime',
        'expiry_date' => 'date',
    ];

    // Condition constants
    public const CONDITION_DEFECTIVE = 'defective';
    public const CONDITION_DAMAGED = 'damaged';
    public const CONDITION_WRONG_ITEM = 'wrong_item';
    public const CONDITION_EXCESS = 'excess';
    public const CONDITION_EXPIRED = 'expired';

    /**
     * Relationships
     */
    public function purchaseReturn(): BelongsTo
    {
        return $this->belongsTo(PurchaseReturn::class);
    }

    public function purchaseItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseItem::class);
    }

    public function grnItem(): BelongsTo
    {
        return $this->belongsTo(GRNItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function deductor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deducted_by');
    }

    /**
     * Helper methods
     */
    public function isDefective(): bool
    {
        return $this->item_condition === self::CONDITION_DEFECTIVE;
    }

    public function isDamaged(): bool
    {
        return $this->item_condition === self::CONDITION_DAMAGED;
    }

    public function isWrongItem(): bool
    {
        return $this->item_condition === self::CONDITION_WRONG_ITEM;
    }

    public function isExcess(): bool
    {
        return $this->item_condition === self::CONDITION_EXCESS;
    }

    public function isExpired(): bool
    {
        return $this->item_condition === self::CONDITION_EXPIRED;
    }

    public function isDeducted(): bool
    {
        return ! is_null($this->deducted_at);
    }
}
