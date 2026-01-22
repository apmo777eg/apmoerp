<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierQuotationItem extends BaseModel
{
    protected ?string $moduleKey = 'purchases';

    protected $table = 'supplier_quotation_items';

    protected $fillable = [
        'quotation_id', 'branch_id', 'product_id', 'quantity',
        'unit_price', 'tax_percent', 'line_total',
        'notes', 'extra_attributes',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'tax_percent' => 'decimal:2',
        'line_total' => 'decimal:4',
        'extra_attributes' => 'array',
    ];

    // Backward compatibility accessors
    public function getQtyAttribute()
    {
        return $this->quantity;
    }

    public function getUnitCostAttribute()
    {
        return $this->unit_price;
    }

    public function getTaxRateAttribute()
    {
        return $this->tax_percent;
    }

    public function getUomAttribute()
    {
        return null; // Not in migration, backward compat only
    }

    public function getDiscountAttribute()
    {
        return 0; // Not in migration, backward compat only
    }

    public function getSpecificationsAttribute()
    {
        return null; // Not in migration, backward compat only
    }

    // Relationships
    public function quotation(): BelongsTo
    {
        return $this->belongsTo(SupplierQuotation::class, 'quotation_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
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
