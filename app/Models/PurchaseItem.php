<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItem extends BaseModel
{
    protected ?string $moduleKey = 'purchases';

    protected $table = 'purchase_items';

    protected $with = ['product'];

    /**
     * Fillable fields aligned with migration:
     * 2026_01_04_000005_create_sales_purchases_tables.php
     */
    protected $fillable = [
        'purchase_id',
        'branch_id',
        'product_id',
        'variation_id',
        'product_name',
        'sku',
        'quantity',
        'received_quantity',
        'unit_id',
        'unit_price',
        'discount_percent',
        'tax_percent',
        'tax_amount',
        'line_total',
        'expiry_date',
        'batch_number',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'received_quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'discount_percent' => 'decimal:2',
        'tax_percent' => 'decimal:2',
        'tax_amount' => 'decimal:4',
        'line_total' => 'decimal:4',
        'expiry_date' => 'date',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class, 'variation_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'unit_id');
    }

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

    public function getDiscountAttribute()
    {
        return $this->discount_percent;
    }

    public function getTaxRateAttribute()
    {
        return $this->tax_percent;
    }
}
