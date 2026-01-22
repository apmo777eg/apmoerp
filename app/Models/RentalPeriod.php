<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalPeriod extends BaseModel
{
    protected $fillable = [
        'module_id',
        'branch_id',
        'period_key',
        'period_name',
        'period_name_ar',
        'period_type',
        'duration_value',
        'duration_unit',
        'price_multiplier',
        'is_default',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'duration_value' => 'integer',
        'price_multiplier' => 'decimal:4',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function getLocalizedNameAttribute(): string
    {
        $locale = app()->getLocale();

        return $locale === 'ar' && $this->period_name_ar ? $this->period_name_ar : $this->period_name;
    }

    public function calculateDays(): int
    {
        return match ($this->duration_unit) {
            'hours' => ceil($this->duration_value / 24),
            'days' => $this->duration_value,
            'weeks' => $this->duration_value * 7,
            'months' => $this->duration_value * 30,
            'years' => $this->duration_value * 365,
            default => $this->duration_value,
        };
    }

    public function calculatePrice(float $basePrice): float
    {
        return $basePrice * $this->price_multiplier;
    }

    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_default', true);
    }

    public function scopeOrdered(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->orderBy('sort_order')->orderBy('duration_value');
    }
}
