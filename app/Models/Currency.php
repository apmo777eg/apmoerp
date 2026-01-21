<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

/**
 * Currency - Supported currencies configuration
 *
 * SECURITY NOTE (V58-IDOR-01): This model intentionally does NOT use branch scoping.
 * Currency is a global configuration resource representing supported currency types.
 * Access is controlled via permission checks (settings.currencies.*) rather than branch isolation.
 * This is by design, as currencies should be consistent across all branches for financial integrity.
 */
class Currency extends Model
{
    protected $table = 'currencies';

    protected $fillable = [
        'code',
        'name',
        'name_ar',
        'symbol',
        'is_base',
        'is_active',
        'decimal_places',
        'sort_order',
        'created_by',
    ];

    protected $casts = [
        'is_base' => 'boolean',
        'is_active' => 'boolean',
        'decimal_places' => 'integer',
        'sort_order' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('code');
    }

    public static function getBaseCurrency(): ?self
    {
        return static::where('is_base', true)->first();
    }

    public static function getActiveCurrencies(): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()->ordered()->get();
    }

    public static function getCurrencyByCode(string $code): ?self
    {
        return static::where('code', strtoupper($code))->first();
    }

    public function getLocalizedName(): string
    {
        if (app()->getLocale() === 'ar' && $this->name_ar) {
            return $this->name_ar;
        }

        return $this->name;
    }

    public static function cachedBaseCurrency()
    {
        return Cache::rememberForever('currency:base', function () {
            return static::getBaseCurrency();
        });
    }

    public static function cachedByCode(string $code, int $ttlSeconds = 3600): ?self
    {
        $normalized = strtoupper($code);
        $cacheKey = sprintf('currency:code:%s', $normalized);

        return Cache::remember($cacheKey, $ttlSeconds, function () use ($normalized) {
            return static::getCurrencyByCode($normalized);
        });
    }
}
