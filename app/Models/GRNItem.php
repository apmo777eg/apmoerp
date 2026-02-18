<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GRNItem extends BaseModel
{
    protected ?string $moduleKey = 'purchases';

    protected $table = 'grn_items';

    /**
     * Canonical columns (DB-first):
     * - Quantities: expected_quantity / received_quantity / accepted_quantity / rejected_quantity
     * - Condition at receipt: item_condition (good|damaged|defective)
     * - Inspection result: inspection_pass + defect_* + inspection_notes
     */
    protected $fillable = [
        'grn_id',
        'branch_id',
        'product_id',
        'purchase_item_id',

        'unit_cost',
        'expected_quantity',
        'received_quantity',
        'accepted_quantity',
        'rejected_quantity',

        'rejection_reason',
        'batch_number',
        'expiry_date',
        'item_condition',

        'notes',

        // Inspection fields
        'inspection_pass',
        'defect_category',
        'defect_description',
        'inspection_notes',
        'received_photos',
        'inspection_photos',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:4',
        'expected_quantity' => 'decimal:4',
        'received_quantity' => 'decimal:4',
        'accepted_quantity' => 'decimal:4',
        'rejected_quantity' => 'decimal:4',
        'expiry_date' => 'date',

        'inspection_pass' => 'boolean',
        'received_photos' => 'array',
        'inspection_photos' => 'array',
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

    // Business Logic
    public function hasDiscrepancy(): bool
    {
        return $this->received_quantity != $this->expected_quantity || $this->rejected_quantity > 0;
    }

    public function getDiscrepancyPercentage(): float
    {
        $expectedQty = decimal_float($this->expected_quantity ?? 0, 4);
        if ($expectedQty <= 0) {
            return 0.0;
        }

        $acceptedQty = $this->accepted_quantity ?? max(0, ($this->received_quantity ?? 0) - ($this->rejected_quantity ?? 0));

        return (abs($expectedQty - decimal_float($acceptedQty, 4)) / $expectedQty) * 100;
    }

    public function isFullyReceived(): bool
    {
        return ($this->received_quantity ?? 0) >= ($this->expected_quantity ?? 0) && ($this->rejected_quantity ?? 0) == 0;
    }

    public function isPartiallyReceived(): bool
    {
        return ($this->received_quantity ?? 0) > 0 && ($this->received_quantity ?? 0) < ($this->expected_quantity ?? 0);
    }
}
