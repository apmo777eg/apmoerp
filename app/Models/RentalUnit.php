<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RentalUnit extends BaseModel
{
    use SoftDeletes;

    protected ?string $moduleKey = 'rentals';

    /**
     * Fillable fields aligned with migration:
     * 2026_01_04_000010_create_rental_tables.php
     */
    protected $fillable = [
        'branch_id',
        'property_id',
        'code',
        'name',
        'name_ar',
        'type',
        'floor',
        'area_sqm',
        'bedrooms',
        'bathrooms',
        'daily_rate',
        'weekly_rate',
        'monthly_rate',
        'yearly_rate',
        'deposit_amount',
        'utilities_included',
        'electricity_meter',
        'water_meter',
        'status',
        'is_active',
        'amenities',
        'images',
        'description',
    ];

    protected $casts = [
        'area_sqm' => 'decimal:2',
        'daily_rate' => 'decimal:4',
        'weekly_rate' => 'decimal:4',
        'monthly_rate' => 'decimal:4',
        'yearly_rate' => 'decimal:4',
        'deposit_amount' => 'decimal:4',
        'electricity_meter' => 'decimal:2',
        'water_meter' => 'decimal:2',
        'utilities_included' => 'boolean',
        'is_active' => 'boolean',
        'amenities' => 'array',
        'images' => 'array',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(RentalContract::class, 'unit_id');
    }

    // Backward compatibility accessors
    public function getRentAttribute()
    {
        return $this->monthly_rate;
    }

    public function setRentAttribute($value): void
    {
        $this->attributes['monthly_rate'] = $value;
    }

    public function getDepositAttribute()
    {
        return $this->deposit_amount;
    }

    public function setDepositAttribute($value): void
    {
        $this->attributes['deposit_amount'] = $value;
    }

    public function scopeForBranch(Builder $query, $branch): Builder
    {
        $id = is_object($branch) ? $branch->getKey() : $branch;

        return $query->where('branch_id', $id);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', 'available')->where('is_active', true);
    }
}
