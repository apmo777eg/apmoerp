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

class Purchase extends BaseModel
{
    use LogsActivity, SoftDeletes;

    protected ?string $moduleKey = 'purchases';

    protected $table = 'purchases';

    protected $with = ['supplier', 'createdBy'];

    /**
     * Fillable fields aligned with migration:
     * 2026_01_04_000005_create_sales_purchases_tables.php
     */
    protected $fillable = [
        'branch_id',
        'warehouse_id',
        'supplier_id',
        'reference_number',
        'external_reference',
        'supplier_invoice',
        'type',
        'channel',
        'status',
        'payment_status',
        // Dates
        'purchase_date',
        'due_date',
        'expected_date',
        // Amounts
        'subtotal',
        'discount_amount',
        'tax_amount',
        'shipping_amount',
        'other_charges',
        'total_amount',
        'paid_amount',
        'currency',
        'exchange_rate',
        // Additional
        'notes',
        'terms_conditions',
        'custom_fields',
        // Approvals
        'created_by',
        'approved_by',
        'approved_at',
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
        'other_charges' => 'decimal:4',
        'total_amount' => 'decimal:4',
        'paid_amount' => 'decimal:4',
        'exchange_rate' => 'decimal:8',
        'purchase_date' => 'date',
        'due_date' => 'date',
        'expected_date' => 'date',
        'approved_at' => 'datetime',
        'custom_fields' => 'array',
        'extra_attributes' => 'array',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function ($m) {
            // Use configurable purchase order prefix from settings
            $prefix = setting('purchases.purchase_order_prefix', 'PO-');
            $m->reference_number = $m->reference_number ?: $prefix.Str::upper(Str::random(8));
        });
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PurchasePayment::class);
    }

    public function returnNotes(): HasMany
    {
        return $this->hasMany(ReturnNote::class);
    }

    public function requisitions(): HasMany
    {
        return $this->hasMany(PurchaseRequisition::class, 'converted_to_po_id');
    }

    public function grns(): HasMany
    {
        return $this->hasMany(GoodsReceivedNote::class, 'purchase_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopeApproved(Builder $query): Builder
    {
        return $query->whereNotNull('approved_at');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('payment_status', '!=', 'paid')
            ->whereNotNull('due_date')
            ->where('due_date', '<', now());
    }

    // Business Logic
    public function getTotalQuantityReceived(): float
    {
        return $this->grns()->where('status', 'completed')->get()->sum(function ($grn) {
            return $grn->items()->sum('accepted_quantity');
        });
    }

    public function isFullyReceived(): bool
    {
        $orderedQty = $this->items->sum('quantity');
        $receivedQty = $this->getTotalQuantityReceived();

        return $receivedQty >= $orderedQty;
    }

    public function isPartiallyReceived(): bool
    {
        $receivedQty = $this->getTotalQuantityReceived();

        return $receivedQty > 0 && ! $this->isFullyReceived();
    }

    public function getTotalPaidAttribute(): float
    {
        return decimal_float($this->paid_amount);
    }

    public function getRemainingAmountAttribute(): float
    {
        return max(0, decimal_float($this->total_amount) - decimal_float($this->paid_amount));
    }

    public function isPaid(): bool
    {
        return $this->remaining_amount <= 0 || $this->payment_status === 'paid';
    }

    public function isApproved(): bool
    {
        return $this->approved_at !== null;
    }

    public function isOverdue(): bool
    {
        return $this->due_date &&
            $this->due_date->isPast() &&
            ! $this->isPaid();
    }

    public function isReceived(): bool
    {
        return $this->status === 'received' || $this->status === 'completed';
    }

    public function approve(int $userId): void
    {
        $this->approved_by = $userId;
        $this->approved_at = now();
        $this->status = 'confirmed';
        $this->save();
    }

    public function updatePaymentStatus(): void
    {
        $paidAmount = decimal_float($this->paid_amount);
        $totalAmount = decimal_float($this->total_amount);

        if ($paidAmount >= $totalAmount) {
            $this->payment_status = 'paid';
        } elseif ($paidAmount > 0) {
            $this->payment_status = 'partial';
        } else {
            $this->payment_status = 'unpaid';
        }

        $this->saveQuietly();
    }

    public function updateReceivingStatus(): void
    {
        if ($this->isFullyReceived()) {
            $this->status = 'completed';
        } elseif ($this->isPartiallyReceived()) {
            $this->status = 'received'; // partial received
        }

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

    public function getGrandTotalAttribute()
    {
        return $this->total_amount;
    }

    public function getSubTotalAttribute()
    {
        return $this->subtotal;
    }

    public function getTaxTotalAttribute()
    {
        return $this->tax_amount;
    }

    public function getShippingTotalAttribute()
    {
        return $this->shipping_amount;
    }

    public function getPaymentDueDateAttribute()
    {
        return $this->due_date;
    }

    public function getExpectedDeliveryDateAttribute()
    {
        return $this->expected_date;
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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['reference_number', 'status', 'total_amount', 'paid_amount', 'supplier_id', 'branch_id', 'approved_by', 'approved_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Purchase {$this->reference_number} was {$eventName}");
    }
}
