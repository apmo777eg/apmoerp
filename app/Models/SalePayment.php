<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalePayment extends Model
{
    use SoftDeletes;

    /**
     * Fillable fields aligned with migration:
     * 2026_01_04_000005_create_sales_purchases_tables.php
     */
    protected $fillable = [
        'sale_id',
        'reference_number',
        'amount',
        'payment_method',
        'status',
        'payment_date',
        'currency',
        'exchange_rate',
        'card_last_four',
        'bank_name',
        'cheque_number',
        'cheque_date',
        'notes',
        'received_by',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'exchange_rate' => 'decimal:8',
        'payment_date' => 'date',
        'cheque_date' => 'date',
    ];

    public const METHOD_CASH = 'cash';

    public const METHOD_CARD = 'card';

    public const METHOD_TRANSFER = 'bank_transfer';

    public const METHOD_SIMPLE_TRANSFER = 'transfer';

    public const METHOD_CHEQUE = 'cheque';

    public static function paymentMethods(): array
    {
        return [
            self::METHOD_CASH => __('Cash'),
            self::METHOD_CARD => __('Card'),
            self::METHOD_TRANSFER => __('Bank Transfer'),
            self::METHOD_SIMPLE_TRANSFER => __('Transfer'),
            self::METHOD_CHEQUE => __('Cheque'),
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    // Backward compatibility accessor
    public function getCreatedByAttribute()
    {
        return $this->received_by;
    }

    // Backward compatibility accessor for reference_no -> reference_number
    public function getReferenceNoAttribute()
    {
        return $this->reference_number;
    }
}
