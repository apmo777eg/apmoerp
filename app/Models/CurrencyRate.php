<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;

class CurrencyRate extends Model
{
    protected $fillable = [
        'from_currency',
        'to_currency',
        'rate',
        'effective_date',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'rate' => 'decimal:6',
        'effective_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForPair(Builder $query, string $from, string $to): Builder
    {
        return $query->where('from_currency', strtoupper($from))
            ->where('to_currency', strtoupper($to));
    }

    public function scopeEffectiveOn(Builder $query, $date = null): Builder
    {
        $date = $date ?? now()->toDateString();

        return $query->where('effective_date', '<=', $date)
            ->orderByDesc('effective_date');
    }

    public static function getRate(string $from, string $to, $date = null): ?float
    {
        $from = strtoupper($from);
        $to = strtoupper($to);

        // Same currency, no conversion needed
        if ($from === $to) {
            return 1.0;
        }

        $dateObject = null;
        if ($date !== null) {
            $dateObject = Date::parse($date);
        }

        $dateKey = $dateObject ? $dateObject->format('Y-m-d') : 'latest';
        $cacheKey = sprintf('currency_rate:%s:%s:%s', $from, $to, $dateKey);

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $query = static::query()
            ->where('from_currency', $from)
            ->where('to_currency', $to)
            ->where('is_active', true);

        if ($dateObject) {
            $query->whereDate('effective_date', '<=', $dateObject->format('Y-m-d'));
        }

        $rate = $query->orderByDesc('effective_date')->first();

        if ($rate === null) {
            return null;
        }

        // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
        $rateValue = decimal_float($rate->rate, 6);
        Cache::put($cacheKey, $rateValue, 300);

        return $rateValue;
    }

    public static function convert(float $amount, string $from, string $to, $date = null): ?float
    {
        $rate = static::getRate($from, $to, $date);

        if ($rate === null) {
            return null;
        }

        return round($amount * $rate, 2);
    }
}
