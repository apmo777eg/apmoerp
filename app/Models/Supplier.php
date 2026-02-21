<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Supplier extends BaseModel
{
    use LogsActivity, SoftDeletes;

    protected ?string $moduleKey = 'suppliers';

    /**
     * Fillable fields aligned with migration:
     * 2026_01_04_000004_create_crm_tables.php
     */
    protected $fillable = [
        'branch_id',
        'code',
        'name',
        'name_ar',
        'type',
        // Contact info
        'email',
        'phone',
        'mobile',
        'fax',
        'website',
        // Address
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        // Business info
        'tax_number',
        'commercial_register',
        'contact_person',
        'contact_position',
        'bank_name',
        'bank_account',
        'bank_iban',
        'bank_swift',
        // Financial
        'balance',
        'payment_terms_days',
        'currency',
        'credit_limit',
        // Rating & Status
        'rating',
        'is_active',
        'is_preferred',
        'is_blocked',
        // Delivery
        'lead_time_days',
        'minimum_order_amount',
        'shipping_cost',
        // Additional
        'notes',
        'custom_fields',
        'product_categories',
        // Legacy / virtual fields (stored in custom_fields)
        'company_name',
        'contact_person_phone',
        'contact_person_email',
        'payment_terms',
        'payment_due_days',
        'minimum_order_value',
        'supplier_rating',
        'quality_rating',
        'delivery_rating',
        'service_rating',
        // For BaseModel compatibility
        'extra_attributes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'balance' => 'decimal:4',
        'credit_limit' => 'decimal:4',
        'minimum_order_amount' => 'decimal:4',
        'shipping_cost' => 'decimal:4',
        'rating' => 'integer',
        'payment_terms_days' => 'integer',
        'lead_time_days' => 'integer',
        'is_active' => 'boolean',
        'is_preferred' => 'boolean',
        'is_blocked' => 'boolean',
        'custom_fields' => 'array',
        'product_categories' => 'array',
        'extra_attributes' => 'array',
    ];


    /**
     * Append computed/legacy attributes in JSON responses.
     */
    protected $appends = [
        'company_name',
        'contact_person_phone',
        'contact_person_email',
        'payment_terms',
        'payment_due_days',
        'minimum_order_value',
        'supplier_rating',
        'quality_rating',
        'delivery_rating',
        'service_rating',
        'last_purchase_date',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(SupplierQuotation::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopePreferred(Builder $query): Builder
    {
        return $query->where('is_preferred', true);
    }

    public function scopeBlocked(Builder $query): Builder
    {
        return $query->where('is_blocked', true);
    }

    public function scopeNotBlocked(Builder $query): Builder
    {
        return $query->where('is_blocked', false);
    }

    // Business logic methods
    public function getOverallRatingAttribute(): float
    {
        return decimal_float($this->rating ?? 0);
    }

    public function updateRating(float $newRating): void
    {
        // Validate rating is within acceptable range (1-5)
        $validatedRating = max(1, min(5, round($newRating)));
        $this->rating = (int) $validatedRating;
        $this->save();
    }

    /**
     * Add to supplier balance.
     *
     * V48-FINANCE-02 FIX: Use string for amount to maintain precision consistency.
     *
     * @param  string  $amount  Amount as a decimal string (e.g., "100.50")
     */
    public function addBalance(string $amount): void
    {
        $newBalance = bcadd((string) ($this->balance ?? '0'), $amount, 4);
        $this->update(['balance' => $newBalance]);
    }

    /**
     * Subtract from supplier balance.
     *
     * V48-FINANCE-02 FIX: Use string for amount to maintain precision consistency.
     *
     * @param  string  $amount  Amount as a decimal string (e.g., "100.50")
     */
    public function subtractBalance(string $amount): void
    {
        $newBalance = bcsub((string) ($this->balance ?? '0'), $amount, 4);
        $this->update(['balance' => $newBalance]);
    }

    public function canReceiveOrders(): bool
    {
        return $this->is_active && ! $this->is_blocked;
    }


    /**
     * Internal helper to read/write supplier custom_fields safely.
     */
    protected function getCustomField(string $key, $default = null)
    {
        $fields = $this->custom_fields;
        if (! is_array($fields)) {
            $fields = [];
        }

        return $fields[$key] ?? $default;
    }

    protected function setCustomField(string $key, $value): void
    {
        $fields = $this->custom_fields;
        if (! is_array($fields)) {
            $fields = [];
        }

        if ($value === null || $value === '') {
            unset($fields[$key]);
        } else {
            $fields[$key] = $value;
        }

        $this->custom_fields = $fields;
    }

    protected function mapPaymentTermsToDays(?string $terms): ?int
    {
        return match ($terms) {
            'immediate' => 0,
            'net15' => 15,
            'net30' => 30,
            'net60' => 60,
            'net90' => 90,
            default => null,
        };
    }

    protected function mapDaysToPaymentTerms(?int $days): ?string
    {
        if ($days === null) {
            return null;
        }

        return match ((int) $days) {
            0 => 'immediate',
            15 => 'net15',
            30 => 'net30',
            60 => 'net60',
            90 => 'net90',
            default => null,
        };
    }

    // Virtual / legacy attributes stored in custom_fields
    public function getCompanyNameAttribute(): ?string
    {
        return $this->getCustomField('company_name');
    }

    public function setCompanyNameAttribute($value): void
    {
        $this->setCustomField('company_name', $value);
    }

    public function getContactPersonPhoneAttribute(): ?string
    {
        return $this->getCustomField('contact_person_phone');
    }

    public function setContactPersonPhoneAttribute($value): void
    {
        $this->setCustomField('contact_person_phone', $value);
    }

    public function getContactPersonEmailAttribute(): ?string
    {
        return $this->getCustomField('contact_person_email');
    }

    public function setContactPersonEmailAttribute($value): void
    {
        $this->setCustomField('contact_person_email', $value);
    }

    public function getQualityRatingAttribute(): ?float
    {
        $v = $this->getCustomField('quality_rating');

        return $v !== null ? decimal_float($v) : null;
    }

    public function setQualityRatingAttribute($value): void
    {
        $this->setCustomField('quality_rating', $value);
    }

    public function getDeliveryRatingAttribute(): ?float
    {
        $v = $this->getCustomField('delivery_rating');

        return $v !== null ? decimal_float($v) : null;
    }

    public function setDeliveryRatingAttribute($value): void
    {
        $this->setCustomField('delivery_rating', $value);
    }

    public function getServiceRatingAttribute(): ?float
    {
        $v = $this->getCustomField('service_rating');

        return $v !== null ? decimal_float($v) : null;
    }

    public function setServiceRatingAttribute($value): void
    {
        $this->setCustomField('service_rating', $value);
    }

    // Payment terms virtual attribute (stored as payment_terms_days)
    public function getPaymentTermsAttribute(): ?string
    {
        return $this->mapDaysToPaymentTerms($this->payment_terms_days);
    }

    public function setPaymentTermsAttribute($value): void
    {
        $days = $this->mapPaymentTermsToDays(is_string($value) ? $value : (string) $value);
        if ($days !== null) {
            $this->attributes['payment_terms_days'] = $days;
        }
    }

    // Backward-compat: accept payment_due_days as alias for payment_terms_days
    public function setPaymentDueDaysAttribute($value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $this->attributes['payment_terms_days'] = (int) $value;
    }

    // Backward-compat: accept supplier_rating as alias for rating
    public function getSupplierRatingAttribute(): ?int
    {
        return $this->rating;
    }

    public function setSupplierRatingAttribute($value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $this->attributes['rating'] = (int) $value;
    }

    public function setMinimumOrderValueAttribute($value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $this->attributes['minimum_order_amount'] = $value;
    }

    public function getLastPurchaseDateAttribute()
    {
        // If selected as an alias (e.g., withMax), return it.
        if (array_key_exists('last_purchase_date', $this->attributes)) {
            return $this->attributes['last_purchase_date'];
        }

        // If purchases are eager-loaded, compute without extra queries.
        if ($this->relationLoaded('purchases') && $this->purchases) {
            $row = $this->purchases->sortByDesc('purchase_date')->first();

            return $row?->purchase_date;
        }

        return null;
    }


    // Backward compatibility accessors
    public function getIsApprovedAttribute(): bool
    {
        return $this->is_active && ! $this->is_blocked;
    }

    public function getPaymentDueDaysAttribute()
    {
        return $this->payment_terms_days;
    }

    public function getAverageLeadTimeDaysAttribute()
    {
        return $this->lead_time_days;
    }

    public function getMinimumOrderValueAttribute()
    {
        return $this->minimum_order_amount;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'phone', 'is_active', 'is_preferred', 'is_blocked', 'rating'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Supplier {$this->name} was {$eventName}");
    }
}
