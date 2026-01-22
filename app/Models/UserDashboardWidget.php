<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDashboardWidget extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'user_dashboard_layout_id',
        'branch_id',
        'dashboard_widget_id',
        'position_x',
        'position_y',
        'width',
        'height',
        'settings',
        'is_visible',
        'sort_order',
    ];

    protected $casts = [
        'position_x' => 'integer',
        'position_y' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'settings' => 'array',
        'is_visible' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the layout.
     */
    public function layout(): BelongsTo
    {
        return $this->belongsTo(UserDashboardLayout::class, 'user_dashboard_layout_id');
    }

    /**
     * Get the widget definition.
     */
    public function widget(): BelongsTo
    {
        return $this->belongsTo(DashboardWidget::class, 'dashboard_widget_id');
    }

    /**
     * Get merged settings (defaults + user overrides).
     */
    public function getMergedSettingsAttribute(): array
    {
        $defaults = $this->widget->default_settings ?? [];
        $userSettings = $this->settings ?? [];

        return array_merge($defaults, $userSettings);
    }

    /**
     * Scope: Visible widgets.
     */
    public function scopeVisible(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_visible', true);
    }

    /**
     * Scope: Ordered.
     */
    public function scopeOrdered(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->orderBy('sort_order')->orderBy('position_y')->orderBy('position_x');
    }
}
