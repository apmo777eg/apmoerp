<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierQuotationItem extends BaseModel
{
    protected ?string $moduleKey = 'purchases';

    protected $table = 'supplier_quotation_items';

    /**
     * Fillable aligned with the canonical schema.
     *
     * NOTE: This project is DB-first and avoids column aliases.
     */
    protected $fillable = [
        'quotation_id', 'branch_id', 'product_id',
        'description',
        'quantity',
        'unit_price',
        'tax_percent',
        'line_total',
        'notes',
        'extra_attributes',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'tax_percent' => 'decimal:2',
        'line_total' => 'decimal:4',
        'extra_attributes' => 'array',
    ];

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
