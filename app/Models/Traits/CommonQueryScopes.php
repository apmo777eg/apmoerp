<?php

declare(strict_types=1);

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Trait for commonly used query scopes across models
 * Makes querying data easier and more consistent for branch managers
 */
trait CommonQueryScopes
{
    /**
     * Scope to filter by date range
     */
    public function scopeDateBetween(Builder $query, string $column, ?string $startDate, ?string $endDate): Builder
    {
        if ($startDate) {
            $query->where($column, '>=', $startDate);
        }
        if ($endDate) {
            $query->where($column, '<=', $endDate);
        }

        return $query;
    }

    /**
     * Scope to filter by created date
     */
    public function scopeCreatedBetween(Builder $query, ?string $startDate, ?string $endDate): Builder
    {
        return $this->scopeDateBetween($query, 'created_at', $startDate, $endDate);
    }

    /**
     * Scope to get records created today
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope to get records created this week
     */
    public function scopeThisWeek(Builder $query): Builder
    {
        return $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    /**
     * Scope to get records created this month
     */
    public function scopeThisMonth(Builder $query): Builder
    {
        return $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
    }

    /**
     * Scope to get records created this year
     */
    public function scopeThisYear(Builder $query): Builder
    {
        return $query->whereYear('created_at', now()->year);
    }

    /**
     * Scope to filter by status
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by multiple statuses
     */
    public function scopeByStatuses(Builder $query, array $statuses): Builder
    {
        return $query->whereIn('status', $statuses);
    }

    /**
     * Scope to search by keyword in multiple columns
     */
    public function scopeSearchKeyword(Builder $query, ?string $keyword, array $columns = ['name']): Builder
    {
        if (empty($keyword)) {
            return $query;
        }

        return $query->where(function ($q) use ($keyword, $columns) {
            foreach ($columns as $column) {
                $q->orWhere($column, 'like', "%{$keyword}%");
            }
        });
    }

    /**
     * Scope to order by latest created
     */
    public function scopeLatestCreated(Builder $query): Builder
    {
        return $query->orderByDesc('created_at');
    }

    /**
     * Scope to order by oldest created
     */
    public function scopeOldestCreated(Builder $query): Builder
    {
        return $query->orderBy('created_at');
    }

    /**
     * Scope for active records (generic - can be overridden)
     */
    public function scopeActive(Builder $query): Builder
    {
        if ($this->hasModelAttribute('is_active')) {
            return $query->where('is_active', true);
        }
        if ($this->hasModelAttribute('status')) {
            return $query->where('status', 'active');
        }

        return $query;
    }

    /**
     * Scope for inactive records
     */
    public function scopeInactive(Builder $query): Builder
    {
        if ($this->hasModelAttribute('is_active')) {
            return $query->where('is_active', false);
        }
        if ($this->hasModelAttribute('status')) {
            return $query->where('status', 'inactive');
        }

        return $query;
    }

    /**
     * Scope to limit results
     */
    public function scopeRecent(Builder $query, int $limit = 10): Builder
    {
        return $query->orderByDesc('created_at')->limit($limit);
    }

    /**
     * Check if model has a defined fillable/cast attribute
     * Note: Named hasModelAttribute to avoid conflict with Laravel's internal hasAttribute method
     */
    public function hasModelAttribute($key): bool
    {
        return in_array($key, $this->fillable ?? []) ||
               array_key_exists($key, $this->casts ?? []);
    }

    /**
     * Get a formatted value for display
     */
    public function getDisplayValue(string $attribute, string $default = '-'): string
    {
        $value = $this->{$attribute};

        if ($value === null || $value === '') {
            return $default;
        }

        // Handle different types
        if (is_bool($value)) {
            return $value ? __('Yes') : __('No');
        }

        if ($value instanceof \DateTime) {
            return $value->format('Y-m-d H:i');
        }

        if (is_numeric($value)) {
            // Common money-related suffixes/prefixes for attribute names
            $moneySuffixes = ['_price', '_cost', '_total', '_amount', 'price', 'cost', 'total', 'amount'];
            foreach ($moneySuffixes as $suffix) {
                // Check if attribute ends with or equals the money suffix
                if (str_ends_with($attribute, $suffix) || $attribute === $suffix) {
                    return number_format(decimal_float($value), 2);
                }
            }
        }

        return (string) $value;
    }
}
