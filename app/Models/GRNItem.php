<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GRNItem extends BaseModel
{
    protected ?string $moduleKey = 'purchases';

    protected $table = 'grn_items';

    /**
     * Fillable fields aligned with migration:
     * 2026_01_04_000005_create_sales_purchases_tables.php
     */
    protected $fillable = [
        'grn_id',
        'branch_id',
        'product_id',
        'purchase_item_id',
        'expected_quantity',
        'received_quantity',
        'accepted_quantity',
        'rejected_quantity',
        'rejection_reason',
        'batch_number',
        'expiry_date',
        'quality_status',
        'notes',
    ];

    protected $casts = [
        'expected_quantity' => 'decimal:4',
        'received_quantity' => 'decimal:4',
        'accepted_quantity' => 'decimal:4',
        'rejected_quantity' => 'decimal:4',
        'expiry_date' => 'date',
    ];

    // Relationships
    public function grn(): BelongsTo
    {
        return $this->belongsTo(GoodsReceivedNote::class, 'grn_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function purchaseItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseItem::class, 'purchase_item_id');
    }

    // Backward compatibility accessors
    public function getQtyOrderedAttribute()
    {
        return $this->expected_quantity;
    }

    public function getQtyReceivedAttribute()
    {
        return $this->received_quantity;
    }

    public function getQtyAcceptedAttribute()
    {
        return $this->accepted_quantity;
    }

    public function getQtyRejectedAttribute()
    {
        return $this->rejected_quantity;
    }

    // Business Logic
    public function hasDiscrepancy(): bool
    {
        return $this->received_quantity != $this->expected_quantity || $this->rejected_quantity > 0;
    }

    public function getDiscrepancyPercentage(): float
    {
        $expectedQty = decimal_float($this->expected_quantity ?? 0, 4);
        // Prevent division by zero
        if ($expectedQty <= 0) {
            return 0.0;
        }

        $acceptedQty = $this->accepted_quantity ?? max(0, $this->received_quantity - $this->rejected_quantity);

        return (abs($expectedQty - decimal_float($acceptedQty, 4)) / $expectedQty) * 100;
    }

    public function isFullyReceived(): bool
    {
        return $this->received_quantity >= $this->expected_quantity && $this->rejected_quantity == 0;
    }

    public function isPartiallyReceived(): bool
    {
        return $this->received_quantity > 0 && $this->received_quantity < $this->expected_quantity;
    }
}
