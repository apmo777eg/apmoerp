<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Sale extends BaseModel
{
    use LogsActivity, SoftDeletes;

    protected ?string $moduleKey = 'sales';

    protected $table = 'sales';

    protected $with = ['customer', 'createdBy'];

    /**
     * Fillable fields aligned with migration:
     * 2026_01_04_000005_create_sales_purchases_tables.php
     */
    protected $fillable = [
        'branch_id',
        'warehouse_id',
        'customer_id',
        'reference_number',
        'client_uuid',
        'external_reference',
        'type',
        'channel',
        'status',
        'payment_status',
        // Dates
        'sale_date',
        'due_date',
        'delivery_date',
        // Amounts
        'subtotal',
        'discount_type',
        'discount_amount',
        'tax_amount',
        'shipping_amount',
        'total_amount',
        'paid_amount',
        'change_amount',
        'currency',
        'exchange_rate',
        // Shipping
        'shipping_address',
        'shipping_method',
        'tracking_number',
        // Additional
        'notes',
        'internal_notes',
        'terms_conditions',
        'custom_fields',
        // References
        'store_order_id',
        'quotation_id',
        'salesperson_id',
        'created_by',
        // POS specific
        'pos_session_id',
        'is_pos_sale',
        // Accounting linkage
        'journal_entry_id',
        // For BaseModel compatibility
        'extra_attributes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:4',
        'discount_amount' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'shipping_amount' => 'decimal:4',
        'total_amount' => 'decimal:4',
        'paid_amount' => 'decimal:4',
        'change_amount' => 'decimal:4',
        'exchange_rate' => 'decimal:8',
        'sale_date' => 'date',
        'due_date' => 'date',
        'delivery_date' => 'date',
        'is_pos_sale' => 'boolean',
        'custom_fields' => 'array',
        'extra_attributes' => 'array',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function ($m) {
            // Use configurable invoice prefix from settings
            $prefix = setting('sales.invoice_prefix', 'SO-');
            $m->reference_number = $m->reference_number ?: $prefix.Str::upper(Str::random(8));
        });

        // Clear cache when sales are created, updated, or deleted (BUG-004 fix)
        static::created(function ($sale) {
            static::clearSalesStatsCache($sale->branch_id);
        });

        static::updated(function ($sale) {
            static::clearSalesStatsCache($sale->branch_id);
        });

        static::deleted(function ($sale) {
            static::clearSalesStatsCache($sale->branch_id);
        });

        // Cascading restore for soft-deleted sale items
        // Note: For sales with many items, consider using queue for large-scale restores
        static::restored(function ($sale) {
            $sale->items()->withTrashed()->restore();
            static::clearSalesStatsCache($sale->branch_id);
        });
    }

    /**
     * Clear sales statistics cache for a given branch
     */
    protected static function clearSalesStatsCache(?int $branchId): void
    {
        $cacheKey = 'sales_stats_'.($branchId ?? 'all');
        \Illuminate\Support\Facades\Cache::forget($cacheKey);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }

    public function returnNotes(): HasMany
    {
        return $this->hasMany(ReturnNote::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class);
    }

    public function scopePosted(Builder $query): Builder
    {
        return $query->where('status', 'posted');
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->where('payment_status', 'unpaid');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('payment_status', '!=', 'paid')
            ->whereNotNull('due_date')
            ->where('due_date', '<', now());
    }

    public function getTotalPaidAttribute(): float
    {
        // STILL-MEDIUM-09 FIX: Only count completed/posted payments, exclude pending/failed/cancelled
        return decimal_float($this->payments()
            ->whereIn('status', ['completed', 'posted', 'paid'])
            ->sum('amount'));
    }

    public function getRemainingAmountAttribute(): float
    {
        return max(0, decimal_float($this->total_amount) - $this->total_paid);
    }

    public function isPaid(): bool
    {
        return $this->remaining_amount <= 0 || $this->payment_status === 'paid';
    }

    public function isOverdue(): bool
    {
        return $this->due_date &&
            $this->due_date->isPast() &&
            ! $this->isPaid();
    }

    public function isDelivered(): bool
    {
        return $this->delivery_date !== null;
    }

    public function updatePaymentStatus(): void
    {
        $totalPaid = $this->total_paid;
        $totalAmount = decimal_float($this->total_amount);

        if ($totalPaid >= $totalAmount) {
            $this->payment_status = 'paid';
        } elseif ($totalPaid > 0) {
            $this->payment_status = 'partial';
        } else {
            $this->payment_status = 'unpaid';
        }

        $this->paid_amount = $totalPaid;

        $this->saveQuietly();
    }

    // Backward compatibility accessors
    public function getCodeAttribute()
    {
        return $this->reference_number;
    }

    public function getReferenceNoAttribute()
    {
        return $this->reference_number;
    }

    public function getOrderNumberAttribute()
    {
        return $this->reference_number;
    }

    public function getDiscountAttribute()
    {
        return $this->discount_amount;
    }

    public function getTaxAttribute()
    {
        return $this->tax_amount;
    }

    public function getPaymentMethodAttribute()
    {
        // Payment method is typically stored in the payments table
        // Return null as a safe default
        return null;
    }

    public function getGrandTotalAttribute()
    {
        return $this->attributes['total_amount'] ?? 0;
    }

    public function getSubTotalAttribute()
    {
        return $this->attributes['subtotal'] ?? 0;
    }

    public function getTaxTotalAttribute()
    {
        return $this->attributes['tax_amount'] ?? 0;
    }

    public function getShippingTotalAttribute()
    {
        return $this->attributes['shipping_amount'] ?? 0;
    }

    public function getPaymentDueDateAttribute()
    {
        return $this->attributes['due_date'] ?? null;
    }

    public function getAmountPaidAttribute()
    {
        return $this->attributes['paid_amount'] ?? 0;
    }

    public function getAmountDueAttribute()
    {
        return $this->remaining_amount;
    }

    public function getPaidTotalAttribute()
    {
        return $this->paid_amount;
    }

    public function getDueTotalAttribute()
    {
        return $this->remaining_amount;
    }

    public function getDiscountTotalAttribute()
    {
        return $this->discount_amount;
    }

    public function storeOrder(): BelongsTo
    {
        return $this->belongsTo(StoreOrder::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['reference_number', 'status', 'total_amount', 'paid_amount', 'customer_id', 'branch_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Sale {$this->reference_number} was {$eventName}");
    }
}
