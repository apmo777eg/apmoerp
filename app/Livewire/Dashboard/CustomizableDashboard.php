<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Livewire\Concerns\LoadsDashboardData;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use App\Enums\SaleStatus;
use Livewire\Component;

/**
 * Customizable Dashboard with Drag-and-Drop Widget System
 *
 * Features:
 * - Reorderable widgets via drag-and-drop
 * - Show/hide widgets per user preference
 * - Multiple layout options
 * - Saved user preferences
 *
 * Uses shared LoadsDashboardData trait for optimized data loading.
 */
class CustomizableDashboard extends Component
{
    use LoadsDashboardData;

    #[Layout('layouts.app')]

    // Dashboard configuration
    public array $widgets = [];

    public array $widgetOrder = [];

    public array $hiddenWidgets = [];

    public string $layoutMode = 'default'; // default, compact, expanded

    /**
     * Period selector used by the dashboard filters.
     * Supported: today, week, month
     */
    public string $selectedPeriod = 'week';

    // Data
    public array $stats = [];

    public array $salesChartData = [];

    public array $inventoryChartData = [];

    public array $paymentMethodsData = [];

    public array $lowStockProducts = [];

    public array $recentSales = [];

    public array $recentActivities = [];

    public array $trendIndicators = [];

    public array $moduleStatsData = [];

    // UI state
    public bool $isEditing = false;

    public bool $isLoading = false;

    /**
     * Available widgets configuration
     */
    protected array $availableWidgets = [
        'quick_actions' => [
            'title' => 'Quick Actions',
            'icon' => 'zap',
            'size' => 'full',
            'default_enabled' => true,
            'permission' => null,
        ],
        'stats_cards' => [
            'title' => 'Stats Overview',
            'icon' => 'bar-chart-2',
            'size' => 'full',
            'default_enabled' => true,
            'permission' => 'dashboard.view',
        ],
        'performance' => [
            'title' => 'Performance Insights',
            'icon' => 'trending-up',
            'size' => 'full',
            'default_enabled' => true,
            'permission' => 'dashboard.view',
        ],
        'sales_chart' => [
            'title' => 'Sales Trend',
            'icon' => 'line-chart',
            'size' => 'large',
            'default_enabled' => true,
            'permission' => 'sales.view',
        ],
        'inventory_chart' => [
            'title' => 'Inventory Status',
            'icon' => 'pie-chart',
            'size' => 'medium',
            'default_enabled' => true,
            'permission' => 'inventory.products.view',
        ],
        'payment_mix' => [
            'title' => 'Payment Methods',
            'icon' => 'credit-card',
            'size' => 'medium',
            'default_enabled' => true,
            'permission' => 'sales.view',
        ],
        'low_stock' => [
            'title' => 'Low Stock Alerts',
            'icon' => 'alert-triangle',
            'size' => 'half',
            'default_enabled' => true,
            'permission' => 'inventory.products.view',
        ],
        'recent_sales' => [
            'title' => 'Recent Sales',
            'icon' => 'shopping-cart',
            'size' => 'half',
            'default_enabled' => true,
            'permission' => 'sales.view',
        ],
        'recent_activity' => [
            'title' => 'Recent Activity',
            'title_ar' => 'النشاط الأخير',
            'icon' => 'clock',
            'size' => 'half',
            'default_enabled' => true,
            'permission' => 'logs.audit.view',
        ],
        'quick_stats' => [
            'title' => 'Quick Stats',
            'icon' => 'activity',
            'size' => 'full',
            'default_enabled' => true,
            'permission' => 'dashboard.view',
        ],
        // Module-specific widgets
        'motorcycle_stats' => [
            'title' => 'Motorcycle Inventory',
            'title_ar' => 'مخزون الدراجات',
            'icon' => '🏍️',
            'size' => 'medium',
            'default_enabled' => true,
            'permission' => 'inventory.products.view',
            'module' => 'motorcycle',
        ],
        'spares_stats' => [
            'title' => 'Spare Parts Overview',
            'title_ar' => 'نظرة عامة على قطع الغيار',
            'icon' => '🔧',
            'size' => 'medium',
            'default_enabled' => true,
            'permission' => 'inventory.products.view',
            'module' => 'spares',
        ],
        'rental_stats' => [
            'title' => 'Rental Overview',
            'title_ar' => 'نظرة عامة على الإيجارات',
            'icon' => '🏠',
            'size' => 'medium',
            'default_enabled' => true,
            'permission' => 'rental.contracts.view',
            'module' => 'rental',
        ],
        'manufacturing_stats' => [
            'title' => 'Manufacturing Overview',
            'title_ar' => 'نظرة عامة على التصنيع',
            'icon' => '🏭',
            'size' => 'medium',
            'default_enabled' => true,
            'permission' => 'manufacturing.view',
            'module' => 'manufacturing',
        ],
        'wood_stats' => [
            'title' => 'Wood Inventory',
            'title_ar' => 'مخزون الأخشاب',
            'icon' => '🪵',
            'size' => 'medium',
            'default_enabled' => true,
            'permission' => 'inventory.products.view',
            'module' => 'wood',
        ],
    ];

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->can('dashboard.view')) {
            abort(403);
        }

        $this->initializeDashboardContext();

        // Load user's dashboard preferences
        $this->loadUserPreferences();

        // Load all data using the shared trait
        $this->loadAllDashboardData();

        // Load recent activities if user has permission
        if ($user->can('logs.audit.view')) {
            $this->loadRecentActivities();
        }

        // Load module-specific statistics
        $this->loadModuleStats();
    }

    /**
     * Override the shared chart builder to respect the dashboard period selector.
     */
    protected function buildSalesChartData(): array
    {
        $days = match ($this->selectedPeriod) {
            'today' => 1,
            'month' => 30,
            default => 7,
        };

        $start = now()->subDays(max($days - 1, 0))->startOfDay();

        $totalsByDate = $this->scopeQueryToBranch(\App\Models\Sale::query())
            ->whereNotIn('status', SaleStatus::nonRevenueStatuses())
            ->whereDate('sale_date', '>=', $start)
            ->selectRaw('DATE(sale_date) as date, SUM(grand_total) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->map(fn ($v) => (float) $v)
            ->toArray();

        $labels = [];
        $values = [];

        // Build a continuous range (even if there were no sales on some days)
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $key = $date->format('Y-m-d');

            $labels[] = $days === 1
                ? __('Today')
                : $date->format('M d');

            $values[] = $totalsByDate[$key] ?? 0.0;
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    /**
     * Load statistics for module-specific widgets
     */
    protected function loadModuleStats(): void
    {
        $user = Auth::user();
        $branch = $user->branch ?? $user->getCurrentBranch() ?? null;

        if (! $branch) {
            return;
        }

        $moduleKeys = ['motorcycle', 'spares', 'rental', 'manufacturing', 'wood'];

        foreach ($moduleKeys as $moduleKey) {
            if ($branch->hasModule($moduleKey)) {
                $this->moduleStatsData[$moduleKey] = $this->getModuleStatistics($moduleKey, $branch->id);
            }
        }
    }

    /**
     * Get statistics for a specific module
     */
    protected function getModuleStatistics(string $moduleKey, int $branchId): array
    {
        $cacheKey = "module_stats:{$moduleKey}:branch_{$branchId}";

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 300, function () use ($moduleKey, $branchId) {
            // Query by module_key first (more common), then fallback to slug
            $module = \App\Models\Module::where('module_key', $moduleKey)->first()
                ?? \App\Models\Module::where('slug', $moduleKey)->first();

            if (! $module) {
                return [];
            }

            $productsQuery = \App\Models\Product::where('module_id', $module->id)
                ->where('branch_id', $branchId);

            $totalProducts = (clone $productsQuery)->count();
            // SECURITY: The selectRaw and DB::raw use only hardcoded column names
            $totalValue = (clone $productsQuery)->sum(\Illuminate\Support\Facades\DB::raw('COALESCE(default_price, 0) * COALESCE(stock_quantity, 0)'));

            $lowStock = (clone $productsQuery)
                ->whereNotNull('min_stock')
                ->where('min_stock', '>', 0)
                ->whereColumn('stock_quantity', '<=', 'min_stock')
                ->count();

            // V35-HIGH-02 FIX: Use sale_date instead of created_at for accurate sales reporting
            // V35-MED-06 FIX: Exclude non-revenue statuses
            // SECURITY: The selectRaw uses hardcoded column names only. $module->id is an integer
            // from the database (not user input), validated when fetched via findOrFail-like queries.
            $thisMonthSales = \App\Models\Sale::where('branch_id', $branchId)
                ->whereMonth('sale_date', now()->month)
                ->whereYear('sale_date', now()->year)
                ->whereNotIn('status', SaleStatus::nonRevenueStatuses())
                ->whereHas('items', function ($q) use ($module) {
                    $q->whereHas('product', function ($pq) use ($module) {
                        $pq->where('module_id', $module->id);
                    });
                })
                ->count();

            return [
                'total_products' => $totalProducts,
                'total_value' => $totalValue,
                'low_stock' => $lowStock,
                'this_month_sales' => $thisMonthSales,
            ];
        });
    }

    /**
     * Load recent activities from audit log
     */
    protected function loadRecentActivities(): void
    {
        $this->recentActivities = \Spatie\Activitylog\Models\Activity::with('causer')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($activity) => [
                'id' => $activity->id,
                'action' => $activity->event ?? 'unknown',
                'model' => class_basename($activity->subject_type ?? 'Unknown'),
                'description' => $this->formatActivityDescription($activity),
                'user' => $activity->causer?->name ?? __('System'),
                'time' => $activity->created_at->diffForHumans(),
            ])
            ->toArray();
    }

    /**
     * Format activity description for display
     */
    protected function formatActivityDescription(\Spatie\Activitylog\Models\Activity $activity): string
    {
        $model = class_basename($activity->subject_type ?? 'Unknown');
        $action = __($activity->event ?? 'unknown');

        $identifier = $this->getActivityIdentifier($activity);

        return "{$action} {$model}: {$identifier}";
    }

    /**
     * Extract an identifier from activity properties
     */
    protected function getActivityIdentifier(\Spatie\Activitylog\Models\Activity $activity): string
    {
        $properties = $activity->properties?->toArray() ?? [];
        $attributes = $properties['attributes'] ?? [];
        $old = $properties['old'] ?? [];

        // Try common identifier fields in order of preference
        $identifierFields = ['name', 'reference_number', 'code', 'title', 'email'];

        foreach ($identifierFields as $field) {
            if (! empty($attributes[$field])) {
                return $attributes[$field];
            }
            if (! empty($old[$field])) {
                return $old[$field];
            }
        }

        return '#'.($activity->subject_id ?? 'N/A');
    }

    /**
     * Load user's dashboard preferences
     */
    protected function loadUserPreferences(): void
    {
        $user = Auth::user();
        $preferences = $user->preferences ?? [];
        $branch = $user->getCurrentBranch() ?? null;

        // Get saved widget order or use defaults
        $this->widgetOrder = $preferences['dashboard_widget_order'] ?? array_keys($this->availableWidgets);
        $this->hiddenWidgets = $preferences['dashboard_hidden_widgets'] ?? [];
        $this->layoutMode = $preferences['dashboard_layout_mode'] ?? 'default';
        $period = $preferences['dashboard_period'] ?? $this->selectedPeriod;
        if (in_array($period, ['today', 'week', 'month'], true)) {
            $this->selectedPeriod = $period;
        }

        // Build widgets array with visibility
        $this->widgets = [];
        foreach ($this->widgetOrder as $widgetKey) {
            if (isset($this->availableWidgets[$widgetKey])) {
                $widget = $this->availableWidgets[$widgetKey];
                $widget['key'] = $widgetKey;
                $widget['visible'] = ! in_array($widgetKey, $this->hiddenWidgets);

                // Check permission
                if ($widget['permission'] && ! Auth::user()->can($widget['permission'])) {
                    continue; // Skip widgets user doesn't have permission for
                }

                // Check if widget requires a specific module and if branch has it enabled
                if (isset($widget['module']) && $branch) {
                    if (! $branch->hasModule($widget['module'])) {
                        continue; // Skip module-specific widgets if module is not enabled
                    }
                }

                $this->widgets[] = $widget;
            }
        }

        // Add any new widgets not in saved order
        foreach ($this->availableWidgets as $key => $widget) {
            if (! in_array($key, $this->widgetOrder)) {
                if ($widget['permission'] && ! Auth::user()->can($widget['permission'])) {
                    continue;
                }
                // Check module availability for new widgets too
                if (isset($widget['module']) && $branch) {
                    if (! $branch->hasModule($widget['module'])) {
                        continue;
                    }
                }
                $widget['key'] = $key;
                $widget['visible'] = $widget['default_enabled'];
                $this->widgets[] = $widget;
            }
        }
    }

    /**
     * Toggle edit mode for dashboard customization
     */
    public function toggleEditMode(): void
    {
        $this->isEditing = ! $this->isEditing;

        // Let the frontend enable/disable SortableJS without needing a full reinit.
        $this->dispatch('dashboard-edit-mode', editing: $this->isEditing);
    }

    /**
     * Update widget order (called from drag-drop JS)
     */
    public function updateWidgetOrder(array $order): void
    {
        $this->widgetOrder = $order;
        $this->saveUserPreferences();
        $this->loadUserPreferences();
    }

    /**
     * Toggle widget visibility
     */
    public function toggleWidget(string $widgetKey): void
    {
        if (in_array($widgetKey, $this->hiddenWidgets)) {
            $this->hiddenWidgets = array_values(array_diff($this->hiddenWidgets, [$widgetKey]));
        } else {
            $this->hiddenWidgets[] = $widgetKey;
        }

        $this->saveUserPreferences();
        $this->loadUserPreferences();
    }

    /**
     * Change layout mode
     */
    public function setLayoutMode(string $mode): void
    {
        if (in_array($mode, ['default', 'compact', 'expanded'])) {
            $this->layoutMode = $mode;
            $this->saveUserPreferences();

            $this->dispatch('dashboard-layout-mode', mode: $this->layoutMode);
        }
    }

    /**
     * React to period filter changes.
     *
     * Important: Charts use JS (Chart.js) and are marked as wire:ignore, so we
     * must explicitly push updated datasets to the frontend.
     */
    public function updatedSelectedPeriod(): void
    {
        if (! in_array($this->selectedPeriod, ['today', 'week', 'month'], true)) {
            $this->selectedPeriod = 'week';
        }

        $this->isLoading = true;

        // Persist selection for this user and refresh cached data
        $this->saveUserPreferences();
        $this->refreshDashboardData();

        // Reload recent activities if user has permission
        $user = Auth::user();
        if ($user && $user->can('logs.audit.view')) {
            $this->loadRecentActivities();
        }

        $this->isLoading = false;

        $this->dispatch('dashboard-charts-update',
            sales: $this->salesChartData,
            inventory: $this->inventoryChartData,
        );
    }

    /**
     * Reset dashboard to defaults
     */
    public function resetDashboard(): void
    {
        $this->widgetOrder = array_keys($this->availableWidgets);
        $this->hiddenWidgets = [];
        $this->layoutMode = 'default';
        $this->saveUserPreferences();
        $this->loadUserPreferences();
    }

    /**
     * Save user preferences
     */
    protected function saveUserPreferences(): void
    {
        $user = Auth::user();
        $preferences = $user->preferences ?? [];

        $preferences['dashboard_widget_order'] = $this->widgetOrder;
        $preferences['dashboard_hidden_widgets'] = $this->hiddenWidgets;
        $preferences['dashboard_layout_mode'] = $this->layoutMode;
        $preferences['dashboard_period'] = $this->selectedPeriod;

        $user->preferences = $preferences;
        $user->save();
    }

    /**
     * Refresh data (clear cache and reload)
     */
    public function refreshData(): void
    {
        $this->isLoading = true;
        $this->refreshDashboardData();

        // Also reload recent activities if user has permission
        $user = Auth::user();
        if ($user && $user->can('logs.audit.view')) {
            $this->loadRecentActivities();
        }

        $this->isLoading = false;

        $this->dispatch('dashboard-charts-update',
            sales: $this->salesChartData,
            inventory: $this->inventoryChartData,
        );
    }

    public function render(): View
    {
        return view('livewire.dashboard.customizable-dashboard');
    }
}
