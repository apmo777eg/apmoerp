<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DashboardWidget extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'key',
        'name',
        'name_ar',
        'description',
        'component',
        'icon',
        'category',
        'default_settings',
        'configurable_options',
        'default_width',
        'default_height',
        'min_width',
        'min_height',
        'requires_permission',
        'permission_key',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'default_settings' => 'array',
        'configurable_options' => 'array',
        'default_width' => 'integer',
        'default_height' => 'integer',
        'min_width' => 'integer',
        'min_height' => 'integer',
        'requires_permission' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get user widget instances.
     */
    public function userWidgets(): HasMany
    {
        return $this->hasMany(UserDashboardWidget::class);
    }

    /**
     * Get cached data.
     */
    public function cachedData(): HasMany
    {
        return $this->hasMany(WidgetDataCache::class);
    }

    /**
     * Scope: Active widgets only.
     */
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: By category.
     */
    public function scopeCategory(\Illuminate\Database\Eloquent\Builder $query, string $category): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: Ordered by sort order.
     */
    public function scopeOrdered(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Check if user has permission to view this widget.
     */
    public function userCanView($user): bool
    {
        if (! $this->requires_permission) {
            return true;
        }

        if (! $this->permission_key) {
            return true;
        }

        return $user->can($this->permission_key);
    }

    /**
     * Get localized name.
     */
    public function getLocalizedNameAttribute(): string
    {
        if (app()->getLocale() === 'ar' && ! empty($this->name_ar)) {
            return $this->name_ar;
        }

        return $this->name;
    }
}
