<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Receipt extends BaseModel
{
    protected ?string $moduleKey = 'finance';

    /**
     * Fillable fields aligned with migration:
     * 2026_01_04_000005_create_sales_purchases_tables.php
     */
    protected $fillable = [
        'branch_id',
        'sale_id',
        'payment_id',
        'receipt_number',
        'amount',
        'type',
        'printed_at',
        'print_data',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'printed_at' => 'datetime',
        'print_data' => 'array',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(SalePayment::class, 'payment_id');
    }

    // Backward compatibility accessors
    public function getReferenceAttribute()
    {
        return $this->receipt_number;
    }

    public function getMethodAttribute()
    {
        return $this->payment?->payment_method;
    }

    public function getPaidAtAttribute()
    {
        return $this->payment?->payment_date ?? $this->created_at;
    }
}
