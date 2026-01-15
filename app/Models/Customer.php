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
        'discount_percent',
        'currency',
        // Loyalty
        'loyalty_points',
        'loyalty_tier',
        // Status
        'is_active',
        'is_blocked',
        'block_reason',
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
        'birthday' => 'date',
        'custom_fields' => 'array',
        'extra_attributes' => 'array',
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
    public function hasAvailableCredit(float $amount = 0): bool
    {
        if ($this->is_blocked) {
            return false;
        }

        // If credit_limit is null or not set, allow purchase (no credit limit)
        // If credit_limit is explicitly 0, no credit is allowed
        if ($this->credit_limit === null) {
            return true;
        }

        if ($this->credit_limit == 0) {
            return false;
        }

        $availableCredit = $this->credit_limit - $this->balance;

        return $availableCredit >= $amount;
    }

    public function getCreditUtilizationAttribute(): float
    {
        if (! $this->credit_limit || $this->credit_limit <= 0) {
            return 0;
        }

        return ($this->balance / $this->credit_limit) * 100;
    }

    public function canPurchase(float $amount): bool
    {
        return $this->hasAvailableCredit($amount) && $this->is_active && ! $this->is_blocked;
    }

    public function addBalance(float $amount): void
    {
        $this->increment('balance', $amount);
    }

    public function subtractBalance(float $amount): void
    {
        $this->decrement('balance', $amount);
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
