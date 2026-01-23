<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Models\DashboardWidget;
use App\Models\UserDashboardLayout;
use App\Models\UserDashboardWidget;
use App\Models\WidgetDataCache;
use Illuminate\Support\Facades\DB;

/**
 * DashboardWidgetService - Widget layout and management
 *
 * Handles:
 * - User dashboard layouts
 * - Widget positioning and sizing
 * - Widget visibility and settings
 * - Dashboard reset functionality
 */
class DashboardWidgetService
{
    /**
     * Get or create user's default dashboard layout.
     */
    public function getUserDashboard(int $userId, ?int $branchId = null): UserDashboardLayout
    {
        $layout = UserDashboardLayout::where('user_id', $userId)
            ->where('branch_id', $branchId)
            ->where('is_default', true)
            ->first();

        if (! $layout) {
            $layout = $this->createDefaultDashboard($userId, $branchId);
        }

        return $layout->load(['widgets.widget']);
    }

    /**
     * Create default dashboard for user.
     */
    public function createDefaultDashboard(int $userId, ?int $branchId = null): UserDashboardLayout
    {
        return DB::transaction(function () use ($userId, $branchId) {
            $layout = UserDashboardLayout::create([
                'user_id' => $userId,
                'branch_id' => $branchId,
                'name' => __('My Dashboard'),
                'is_default' => true,
                'layout_config' => [
                    'columns' => 12,
                    'row_height' => 100,
                    'gap' => 16,
                ],
            ]);

            $this->addDefaultWidgets($layout);

            return $layout;
        });
    }

    /**
     * Add default widgets to layout.
     */
    private function addDefaultWidgets(UserDashboardLayout $layout): void
    {
        $user = $layout->user;
        $widgets = DashboardWidget::active()->ordered()->get();

        $positionY = 0;
        $positionX = 0;

        foreach ($widgets as $widget) {
            if (! $widget->userCanView($user)) {
                continue;
            }

            if ($positionX + $widget->default_width > 12) {
                $positionX = 0;
                $positionY += 4;
            }

            UserDashboardWidget::create([
                'user_dashboard_layout_id' => $layout->id,
                'dashboard_widget_id' => $widget->id,
                'position_x' => $positionX,
                'position_y' => $positionY,
                'width' => $widget->default_width,
                'height' => $widget->default_height,
                'settings' => $widget->default_settings,
                'is_visible' => true,
                'sort_order' => $widget->sort_order,
            ]);

            $positionX += $widget->default_width;
        }
    }

    /**
     * Add widget to user's dashboard.
     */
    public function addWidget(int $layoutId, int $widgetId, array $options = []): UserDashboardWidget
    {
        $layout = UserDashboardLayout::findOrFail($layoutId);
        $widget = DashboardWidget::findOrFail($widgetId);

        $existing = UserDashboardWidget::where('user_dashboard_layout_id', $layoutId)
            ->where('dashboard_widget_id', $widgetId)
            ->first();

        if ($existing) {
            return $existing;
        }

        return UserDashboardWidget::create([
            'user_dashboard_layout_id' => $layoutId,
            'dashboard_widget_id' => $widgetId,
            'position_x' => $options['position_x'] ?? 0,
            'position_y' => $options['position_y'] ?? 0,
            'width' => $options['width'] ?? $widget->default_width,
            'height' => $options['height'] ?? $widget->default_height,
            'settings' => $options['settings'] ?? $widget->default_settings,
            'is_visible' => $options['is_visible'] ?? true,
            'sort_order' => $options['sort_order'] ?? 999,
        ]);
    }

    /**
     * Remove widget from dashboard.
     */
    public function removeWidget(int $userWidgetId): void
    {
        UserDashboardWidget::findOrFail($userWidgetId)->delete();
    }

    /**
     * Update widget position/size.
     */
    public function updateWidget(int $userWidgetId, array $data): UserDashboardWidget
    {
        $userWidget = UserDashboardWidget::findOrFail($userWidgetId);

        $userWidget->update(array_filter([
            'position_x' => $data['position_x'] ?? null,
            'position_y' => $data['position_y'] ?? null,
            'width' => $data['width'] ?? null,
            'height' => $data['height'] ?? null,
            'settings' => $data['settings'] ?? null,
            'is_visible' => $data['is_visible'] ?? null,
        ], fn ($value) => ! is_null($value)));

        return $userWidget;
    }

    /**
     * Toggle widget visibility.
     */
    public function toggleWidget(int $userWidgetId): bool
    {
        $userWidget = UserDashboardWidget::findOrFail($userWidgetId);
        $userWidget->update(['is_visible' => ! $userWidget->is_visible]);

        return $userWidget->is_visible;
    }

    /**
     * Update dashboard layout.
     */
    public function updateLayout(int $layoutId, array $widgets): void
    {
        DB::transaction(function () use ($layoutId, $widgets) {
            foreach ($widgets as $widgetData) {
                UserDashboardWidget::where('id', $widgetData['id'])
                    ->where('user_dashboard_layout_id', $layoutId)
                    ->update([
                        'position_x' => $widgetData['position_x'],
                        'position_y' => $widgetData['position_y'],
                        'width' => $widgetData['width'],
                        'height' => $widgetData['height'],
                    ]);
            }
        });
    }

    /**
     * Reset dashboard to default.
     */
    public function resetToDefault(int $layoutId): UserDashboardLayout
    {
        return DB::transaction(function () use ($layoutId) {
            $layout = UserDashboardLayout::findOrFail($layoutId);
            $layout->widgets()->delete();
            $this->addDefaultWidgets($layout);

            return $layout->fresh(['widgets.widget']);
        });
    }

    /**
     * Get available widgets for user.
     */
    public function getAvailableWidgets($user): array
    {
        return DashboardWidget::active()
            ->ordered()
            ->get()
            ->filter(fn ($widget) => $widget->userCanView($user))
            ->map(fn ($widget) => [
                'id' => $widget->id,
                'widget_key' => $widget->widget_key,
                'name' => $widget->localized_name,
                'description' => $widget->description,
                'icon' => $widget->icon,
                'category' => $widget->category,
                'default_width' => $widget->default_width,
                'default_height' => $widget->default_height,
                'configurable_options' => $widget->configurable_options,
            ])
            ->groupBy('category')
            ->toArray();
    }

    /**
     * Register a new widget type.
     */
    public function registerWidget(array $data): DashboardWidget
    {
        return DashboardWidget::create($data);
    }

    /**
     * Get dashboard statistics.
     */
    public function getDashboardStats(int $userId, ?int $branchId = null): array
    {
        $layout = $this->getUserDashboard($userId, $branchId);

        return [
            'total_widgets' => $layout->widgets()->count(),
            'visible_widgets' => $layout->widgets()->where('is_visible', true)->count(),
            'available_widgets' => DashboardWidget::active()->count(),
            'layout' => [
                'columns' => $layout->layout_config['columns'] ?? 12,
                'row_height' => $layout->layout_config['row_height'] ?? 100,
            ],
        ];
    }

    /**
     * Clear widget cache.
     */
    public function clearWidgetCache(int $userId, ?int $widgetId = null): void
    {
        $query = WidgetDataCache::where('user_id', $userId);

        if ($widgetId) {
            $query->where('dashboard_widget_id', $widgetId);
        }

        $query->delete();
    }
}
