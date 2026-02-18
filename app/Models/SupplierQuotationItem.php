<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierQuotationItem extends BaseModel
{
    protected ?string $moduleKey = 'purchases';

    protected $table = 'supplier_quotation_items';

    /**
     * Fillable aligned with migration + safe legacy aliases.
     *
     * Legacy keys (qty/unit_cost/tax_rate) are mapped via mutators to the real columns.
     */
    protected $fillable = [
        'quotation_id', 'branch_id', 'product_id',
        'quantity', 'unit_price', 'tax_percent', 'line_total',
        'notes', 'extra_attributes',
        'created_by', 'updated_by',

        // Legacy aliases (mapped to real columns by mutators)
        'qty', 'unit_cost', 'tax_rate',
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

    public function setQtyAttribute($value): void
    {
        $this->attributes['quantity'] = $value;
    }

    public function getUnitCostAttribute()
    {
        return $this->unit_price;
    }

    public function setUnitCostAttribute($value): void
    {
        $this->attributes['unit_price'] = $value;
    }

    public function getTaxRateAttribute()
    {
        return $this->tax_percent;
    }

    public function setTaxRateAttribute($value): void
    {
        $this->attributes['tax_percent'] = $value;
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
