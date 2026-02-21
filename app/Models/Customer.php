<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Customer extends BaseModel
{
    use LogsActivity, SoftDeletes;

    protected ?string $moduleKey = 'customers';

    protected $table = 'customers';

    /**
     * Fillable fields aligned with migration:
     * 2026_01_04_000004_create_crm_tables.php
     */
    protected $fillable = [
        'branch_id',
        'code',
        // Virtual UUID stored in extra_attributes for API compatibility
        'uuid',
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
        'shipping_address',
        // Business info
        'tax_number',
        'commercial_register',
        'national_id',
        'contact_person',
        'contact_position',
        // Financial
        'price_group_id',
        'credit_limit',
        'balance',
        'payment_terms_days',
        // Legacy aliases
        'payment_terms',
        'payment_due_days',
        'discount_percent',
        'currency',
        // Legacy alias
        'preferred_currency',
        // Loyalty
        'loyalty_points',
        'loyalty_tier',
        // Status
        'is_active',
        'is_blocked',
        'block_reason',
        // Portal
        'portal_enabled',
        'portal_password',
        // Additional
        'notes',
        'custom_fields',
        'source',
        'birthday',
        'gender',
        // For BaseModel compatibility
        'extra_attributes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:4',
        'balance' => 'decimal:4',
        'discount_percent' => 'decimal:2',
        'loyalty_points' => 'integer',
        'payment_terms_days' => 'integer',
        'is_active' => 'boolean',
        'is_blocked' => 'boolean',
        'portal_enabled' => 'boolean',
        'birthday' => 'date',
        'custom_fields' => 'array',
        'extra_attributes' => 'array',
    ];

    protected $hidden = [
        'portal_password',
    ];

    /**
     * Append computed/legacy attributes in JSON responses.
     */
    protected $appends = [
        'uuid',
        'payment_terms',
        'payment_due_days',
        'preferred_currency',
        'total_purchases',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function priceGroup(): BelongsTo
    {
        return $this->belongsTo(PriceGroup::class, 'price_group_id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function vehicleContracts(): HasMany
    {
        return $this->hasMany(VehicleContract::class);
    }

    public function rentalContracts(): HasMany
    {
        return $this->hasMany(RentalContract::class);
    }

    /**
     * MED-03 FIX: Use hasManyThrough to get payments via Sale relationship
     * SalePayment doesn't have customer_id column, it relates to Customer through Sale
     */
    public function payments(): HasManyThrough
    {
        return $this->hasManyThrough(
            SalePayment::class,
            Sale::class,
            'customer_id', // Foreign key on Sales table
            'sale_id',     // Foreign key on SalePayments table
            'id',          // Local key on Customers table
            'id'           // Local key on Sales table
        );
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeBlocked(Builder $query): Builder
    {
        return $query->where('is_blocked', true);
    }

    /**
     * MED-04 FIX: Handle NULL credit_limit (no limit = unlimited credit)
     */
    public function scopeWithinCreditLimit(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('credit_limit')
                ->orWhereRaw('balance <= credit_limit');
        });
    }

    // Business logic methods
    /**
     * Check if customer has available credit for a purchase.
     *
     * V48-FINANCE-02 FIX: Use string for amount and BCMath for arithmetic to avoid float precision issues.
     *
     * @param  string  $amount  Amount as a decimal string (e.g., "100.50"), defaults to "0"
     */
    public function hasAvailableCredit(string $amount = '0'): bool
    {
        if ($this->is_blocked) {
            return false;
        }

        // If credit_limit is null or not set, allow purchase (no credit limit)
        // If credit_limit is explicitly 0, no credit is allowed
        if ($this->credit_limit === null) {
            return true;
        }

        // Use bccomp to compare credit_limit to 0
        if (bccomp((string) $this->credit_limit, '0', 4) === 0) {
            return false;
        }

        // V48-FINANCE-02 FIX: Use bcsub for subtraction and bccomp for comparison
        $availableCredit = bcsub((string) $this->credit_limit, (string) $this->balance, 4);

        return bccomp($availableCredit, $amount, 4) >= 0;
    }

    /**
     * Get credit utilization percentage.
     *
     * V48-FINANCE-02 FIX: Use BCMath for arithmetic to avoid float precision issues.
     */
    public function getCreditUtilizationAttribute(): float
    {
        if ($this->credit_limit === null || bccomp((string) $this->credit_limit, '0', 4) <= 0) {
            return 0.0;
        }

        // V48-FINANCE-02 FIX: Use bcdiv and bcmul for percentage calculation
        $utilization = bcmul(
            bcdiv((string) $this->balance, (string) $this->credit_limit, 6),
            '100',
            2
        );

        return (float) $utilization;
    }

    /**
     * Check if customer can make a purchase.
     *
     * V48-FINANCE-02 FIX: Use string for amount to maintain precision consistency.
     *
     * @param  string  $amount  Amount as a decimal string (e.g., "100.50")
     */
    public function canPurchase(string $amount): bool
    {
        return $this->hasAvailableCredit($amount) && $this->is_active && ! $this->is_blocked;
    }

    /**
     * Add to customer balance.
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
     * Subtract from customer balance.
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


    /**
     * Store a virtual UUID in extra_attributes (customers table doesn't have a uuid column).
     */
    public function getUuidAttribute(): ?string
    {
        $attrs = $this->extra_attributes;

        return is_array($attrs) ? ($attrs['uuid'] ?? null) : null;
    }

    public function setUuidAttribute($value): void
    {
        $attrs = $this->extra_attributes;
        if (! is_array($attrs)) {
            $attrs = [];
        }

        if ($value === null || $value === '') {
            unset($attrs['uuid']);
        } else {
            $attrs['uuid'] = (string) $value;
        }

        $this->extra_attributes = $attrs;
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

    public function setPaymentDueDaysAttribute($value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $this->attributes['payment_terms_days'] = (int) $value;
    }

    public function getPreferredCurrencyAttribute(): ?string
    {
        return $this->currency;
    }

    public function setPreferredCurrencyAttribute($value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $this->attributes['currency'] = (string) $value;
    }

    public function getTotalPurchasesAttribute(): ?float
    {
        // If selected as an alias, prefer it.
        if (array_key_exists('total_purchases', $this->attributes)) {
            return decimal_float($this->attributes['total_purchases']);
        }

        // If sales are eager-loaded, compute without extra queries.
        if ($this->relationLoaded('sales') && $this->sales) {
            return decimal_float($this->sales->sum('total_amount'));
        }

        return null;
    }


    // Backward compatibility accessors
    public function getStatusAttribute(): string
    {
        if ($this->is_blocked) {
            return 'blocked';
        }

        return $this->is_active ? 'active' : 'inactive';
    }

    public function getCreditHoldAttribute(): bool
    {
        return $this->is_blocked;
    }

    public function getCustomerTierAttribute(): ?string
    {
        return $this->loyalty_tier;
    }

    public function getDiscountPercentageAttribute()
    {
        return $this->discount_percent;
    }

    public function getPaymentDueDaysAttribute()
    {
        return $this->payment_terms_days;
    }

    public function getBillingAddressAttribute()
    {
        return $this->address;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'phone', 'is_active', 'is_blocked', 'loyalty_points', 'loyalty_tier'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Customer {$this->name} was {$eventName}");
    }
}
