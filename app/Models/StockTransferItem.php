<?php

namespace App\Models;

use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * V65-BUG-FIX: Added HasBranch trait for proper branch scoping.
 */
class StockTransferItem extends Model
{
    use HasBranch, HasFactory;

    protected $fillable = [
        'branch_id',
        'stock_transfer_id',
        'product_id',
        'qty_requested',
        'qty_approved',
        'qty_shipped',
        'qty_received',
        'qty_damaged',
        'batch_number',
        'expiry_date',
        'unit_cost',
        'condition_on_shipping',
        'condition_on_receiving',
        'notes',
        'damage_report',
    ];

    protected $casts = [
        'qty_requested' => 'decimal:3',
        'qty_approved' => 'decimal:3',
        'qty_shipped' => 'decimal:3',
        'qty_received' => 'decimal:3',
        'qty_damaged' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'expiry_date' => 'date',
    ];

    /**
     * Relationships
     */
    public function stockTransfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Check if item is fully received
     */
    public function isFullyReceived(): bool
    {
        return bccomp((string)$this->qty_shipped, (string)$this->qty_received, 3) === 0;
    }

    /**
     * Check if item has damage
     */
    public function hasDamage(): bool
    {
        return $this->qty_damaged > 0;
    }

    /**
     * Get variance between shipped and received
     * Note: Uses 3 decimal places for quantity variance (not currency) to preserve precision
     */
    public function getVariance(): float
    {
        return decimal_float(bcsub((string)$this->qty_shipped, (string)$this->qty_received, 3), 3);
    }
}
