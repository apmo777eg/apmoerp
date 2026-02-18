<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * SupplierQuotation (DB-first, no alias columns)
 *
 * Canonical identifier: reference_number
 * Canonical totals: subtotal / discount_amount / tax_amount / shipping_amount / total_amount
 */
class SupplierQuotation extends BaseModel
{
    protected ?string $moduleKey = 'purchases';

    protected $table = 'supplier_quotations';

    /**
     * Keep fillable aligned with migration:
     * database/migrations/2026_01_04_000002_create_purchases_tables.php
     */
    protected $fillable = [
        'branch_id',
        'supplier_id',
        'requisition_id',

        'reference_number',
        'quotation_date',
        'valid_until',
        'status',

        'currency',
        'exchange_rate',

        'subtotal',
        'discount_amount',
        'tax_amount',
        'shipping_amount',
        'total_amount',

        'lead_time_days',
        'payment_terms',
        'delivery_terms',
        'delivery_days',
        'terms_conditions',

        'rejection_reason',
        'notes',
        'custom_fields',
        'extra_attributes',

        'accepted_by',
        'accepted_at',
        'rejected_by',
        'rejected_at',

        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'quotation_date' => 'date',
        'valid_until' => 'date',

        'exchange_rate' => 'decimal:8',
        'subtotal' => 'decimal:4',
        'discount_amount' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'shipping_amount' => 'decimal:4',
        'total_amount' => 'decimal:4',

        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',

        'custom_fields' => 'array',
        'extra_attributes' => 'array',
    ];

    protected static function booted(): void
    {
        parent::booted();

        // Generate stable human-friendly reference_number if missing.
        static::creating(function (self $model): void {
            if ($model->reference_number) {
                return;
            }

            $model->reference_number = DB::transaction(function () use ($model) {
                $prefix = 'QT-'.date('Ymd').'-';

                $last = static::query()
                    ->when($model->branch_id, fn (Builder $q) => $q->where('branch_id', $model->branch_id))
                    ->where('reference_number', 'like', $prefix.'%')
                    ->lockForUpdate()
                    ->orderByDesc('id')
                    ->first();

                $seq = 1;
                if ($last && preg_match('/QT-\d{8}-(\d{5})$/', (string) $last->reference_number, $matches)) {
                    $seq = ((int) $matches[1]) + 1;
                }

                return $prefix.str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
            });
        });
    }

    // Relationships
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class, 'requisition_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SupplierQuotationItem::class, 'quotation_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->whereNotIn('status', ['expired', 'rejected'])
            ->where(function (Builder $q) {
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', now());
            });
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query
            ->whereNotNull('valid_until')
            ->where('valid_until', '<', now())
            ->where('status', '!=', 'accepted');
    }

    public function scopeAccepted(Builder $query): Builder
    {
        return $query->where('status', 'accepted');
    }

    // Business logic
    public function accept(?int $userId = null): void
    {
        $this->status = 'accepted';
        $this->accepted_by = $userId;
        $this->accepted_at = now();
        $this->save();
    }

    public function reject(?string $reason = null, ?int $userId = null): void
    {
        $this->status = 'rejected';
        $this->rejected_by = $userId;
        $this->rejected_at = now();
        $this->rejection_reason = $reason;
        $this->save();
    }


    public function isExpired(): bool
    {
        if (! $this->valid_until) {
            return false;
        }

        return $this->valid_until->isPast() && $this->status !== 'accepted';
    }
}
