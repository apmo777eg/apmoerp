<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RentalContract extends BaseModel
{
    use SoftDeletes;

    protected ?string $moduleKey = 'rentals';

    /**
     * Fillable fields aligned with migration:
     * 2026_01_04_000010_create_rental_tables.php
     */
    protected $fillable = [
        'branch_id',
        'unit_id',
        'tenant_id',
        'contract_number',
        'type',
        'status',
        'start_date',
        'end_date',
        'actual_end_date',
        'expiration_notified_at',
        'rent_amount',
        // Legacy alias
        'rent',
        'rent_frequency',
        'deposit_amount',
        // Legacy alias
        'deposit',
        'deposit_paid',
        'payment_day',
        'late_fee_amount',
        'late_fee_percent',
        'grace_period_days',
        'utilities_included',
        'electricity_opening',
        'water_opening',
        'terms_conditions',
        'special_conditions',
        'notes',
        'documents',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'actual_end_date' => 'date',
        'rent_amount' => 'decimal:4',
        'deposit_amount' => 'decimal:4',
        'deposit_paid' => 'decimal:4',
        'late_fee_amount' => 'decimal:4',
        'late_fee_percent' => 'decimal:2',
        'electricity_opening' => 'decimal:2',
        'water_opening' => 'decimal:2',
        'utilities_included' => 'boolean',
        'expiration_notified_at' => 'datetime',
        'documents' => 'array',
    ];

    protected $appends = ['rent', 'deposit'];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(RentalUnit::class, 'unit_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(RentalInvoice::class, 'contract_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(RentalPayment::class, 'contract_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Backward compatibility accessors
    public function getRentAttribute()
    {
        return $this->rent_amount;
    }

    public function setRentAttribute($value): void
    {
        $this->attributes['rent_amount'] = $value;
    }

    public function getDepositAttribute()
    {
        return $this->deposit_amount;
    }

    public function setDepositAttribute($value): void
    {
        $this->attributes['deposit_amount'] = $value;
    }

    public function getRentalPeriodAttribute()
    {
        return null; // No longer supported in new schema
    }

    public function getDepositRefundedAttribute()
    {
        return $this->deposit_paid;
    }

    public function getAutoRenewAttribute(): bool
    {
        return false; // Not in new schema
    }

    public function calculateEndDate(): ?string
    {
        if (! $this->start_date) {
            return null;
        }

        // Calculate based on rent_frequency
        return match ($this->rent_frequency) {
            'daily' => $this->start_date->addDay()->format('Y-m-d'),
            'weekly' => $this->start_date->addWeek()->format('Y-m-d'),
            'monthly' => $this->start_date->addMonth()->format('Y-m-d'),
            'quarterly' => $this->start_date->addMonths(3)->format('Y-m-d'),
            'yearly' => $this->start_date->addYear()->format('Y-m-d'),
            default => $this->start_date->addMonth()->format('Y-m-d'),
        };
    }

    // Scopes
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeExpired(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', 'expired');
    }
}
