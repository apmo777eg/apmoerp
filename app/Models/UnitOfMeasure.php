<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnitOfMeasure extends Model
{
    protected $table = 'units_of_measure';

    protected $fillable = [
        'name',
        'name_ar',
        'symbol',
        'type',
        'base_unit_id',
        'conversion_factor',
        'decimal_places',
        'is_base_unit',
        'is_active',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'conversion_factor' => 'decimal:6',
        'decimal_places' => 'integer',
        'is_base_unit' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'base_unit_id');
    }

    public function derivedUnits(): HasMany
    {
        return $this->hasMany(UnitOfMeasure::class, 'base_unit_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'unit_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeBaseUnits(Builder $query): Builder
    {
        return $query->where('is_base_unit', true);
    }

    public function getLocalizedNameAttribute(): string
    {
        $locale = app()->getLocale();

        return $locale === 'ar' && $this->name_ar ? $this->name_ar : $this->name;
    }

    public function convertTo(float $value, UnitOfMeasure $targetUnit): float
    {
        if ($this->id === $targetUnit->id) {
            return $value;
        }

        $baseValue = $value * decimal_float($this->conversion_factor);

        $targetFactor = decimal_float($targetUnit->conversion_factor);

        // Prevent division by zero - conversion factors should be positive for inventory units
        if ($targetFactor == 0) {
            throw new \InvalidArgumentException('Target unit conversion factor cannot be zero');
        }

        return $baseValue / $targetFactor;
    }
}
