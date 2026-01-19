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

    public function addBalance(float $amount): void
    {
        $this->increment('balance', $amount);
    }

    public function subtractBalance(float $amount): void
    {
        $this->decrement('balance', $amount);
    }

    public function canReceiveOrders(): bool
    {
        return $this->is_active && ! $this->is_blocked;
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
