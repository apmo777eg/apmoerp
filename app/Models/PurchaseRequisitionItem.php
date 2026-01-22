<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRequisitionItem extends BaseModel
{
    protected ?string $moduleKey = 'purchases';

    protected $table = 'purchase_requisition_items';

    protected $fillable = [
        'requisition_id', 'branch_id', 'product_id', 'quantity', 'unit_id',
        'estimated_price', 'specifications',
        'preferred_supplier_id',
        'extra_attributes', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'estimated_price' => 'decimal:4',
        'extra_attributes' => 'array',
    ];

    // Backward compatibility accessors
    public function getQtyAttribute()
    {
        return $this->quantity;
    }

    public function getUomAttribute()
    {
        return $this->unit_id;
    }

    public function getEstimatedUnitCostAttribute()
    {
        return $this->estimated_price;
    }

    public function getEstimatedTotalAttribute()
    {
        return bcmul((string) ($this->quantity ?? 0), (string) ($this->estimated_price ?? 0), 4);
    }

    public function getNotesAttribute()
    {
        return $this->specifications;
    }

    // Relationships
    public function requisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class, 'requisition_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'unit_id');
    }

    public function preferredSupplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'preferred_supplier_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
