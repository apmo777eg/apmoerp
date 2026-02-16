<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\UserDashboardWidget;
use App\Services\BranchContextManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class DashboardWidgets extends Component
{
    public array $widgets = [];

    public array $widgetData = [];

    protected int $cacheTtl = 300;

    public function mount(): void
    {
        $this->loadUserWidgets();
        $this->loadWidgetData();
    }

    public function loadUserWidgets(): void
    {
        // NOTE:
        // The project has a newer, database-backed customizable dashboard (Dashboard\CustomizableDashboard).
        // This component is kept for backward compatibility and stores preferences in the session to avoid
        // schema mismatches causing 500s.
        $stored = session()->get('dashboard_widgets');

        if (is_array($stored) && !empty($stored)) {
            $this->widgets = $stored;
            return;
        }

        $this->widgets = $this->defaultWidgets;
    }
    public function loadWidgetData(): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        // Cache MUST include current branch context to prevent cross-branch data leakage.
        $branchKey = branch_context_cache_key();
        $cacheKey = "dashboard_widgets:user_{$user->id}:branch_{$branchKey}";

        $this->widgetData = Cache::remember($cacheKey, $this->cacheTtl, function () use ($user) {
            // Determine effective branch filter:
            // - If a specific branch context is selected, always filter to it (even for admins)
            // - If user can view-all and context is "All Branches", don't filter
            // - Otherwise, fall back to user's primary branch
            $branchId = current_branch_id() ?? ($user->branch_id ? (int) $user->branch_id : null);

            if (BranchContextManager::canViewAllBranches($user) && current_branch_id() === null) {
                $branchId = null;
            }

            $salesQuery = Sale::query();
            $productsQuery = Product::query();
            $customersQuery = Customer::query();

            if ($branchId) {
                $salesQuery->where('branch_id', $branchId);
                $productsQuery->where('branch_id', $branchId);
                $customersQuery->where('branch_id', $branchId);
            }

            // V30-CRIT-01 FIX: Use sale_date (business date) instead of created_at
            // This ensures synced/backdated sales appear on the correct business day
            return [
                'total_sales_today' => (clone $salesQuery)
                    ->whereDate('sale_date', today())
                    ->sum('total_amount') ?? 0,
                'total_sales_week' => (clone $salesQuery)
                    ->whereBetween('sale_date', [now()->startOfWeek(), now()->endOfWeek()])
                    ->sum('total_amount') ?? 0,
                'total_sales_month' => (clone $salesQuery)
                    ->whereYear('sale_date', now()->year)
                    ->whereMonth('sale_date', now()->month)
                    ->sum('total_amount') ?? 0,
                'total_revenue_month' => (clone $salesQuery)
                    ->whereYear('sale_date', now()->year)
                    ->whereMonth('sale_date', now()->month)
                    ->where('status', 'completed')
                    ->sum('total_amount') ?? 0,
                'total_products' => (clone $productsQuery)->count(),
                // FIX N-06: Product model uses 'status' column, not 'is_active' boolean
                'active_products' => (clone $productsQuery)->where('status', 'active')->count(),
                'total_customers' => (clone $customersQuery)->count(),
                // Fix: Add whereYear to prevent counting same month from different years
                'new_customers_month' => (clone $customersQuery)
                    ->whereYear('created_at', now()->year)
                    ->whereMonth('created_at', now()->month)
                    ->count(),
                // FIX U-07: Use subquery to correctly count grouped results
                // Laravel's count() on a grouped query returns the count of the first group
                // Use a subquery and count the rows instead
                // V46-HIGH-01 FIX: Exclude soft-deleted stock_movements
                'low_stock_count' => DB::table(
                    DB::table('products')
                        ->leftJoin('stock_movements', function ($join) {
                            $join->on('stock_movements.product_id', '=', 'products.id')
                                ->whereNull('stock_movements.deleted_at');
                        })
                        ->whereNull('products.deleted_at')
                        ->whereNotNull('products.min_stock')
                        ->where('products.min_stock', '>', 0)
                        ->when($branchId, fn ($q) => $q->where('products.branch_id', $branchId))
                        ->select('products.id')
                        ->selectRaw('COALESCE(SUM(stock_movements.quantity), 0) as current_stock')
                        ->selectRaw('products.min_stock')
                        ->groupBy('products.id', 'products.min_stock')
                        ->havingRaw('COALESCE(SUM(stock_movements.quantity), 0) <= products.min_stock'),
                    'low_stock_products'
                )->count(),
                'pending_orders' => $salesQuery->where('status', 'pending')->count(),
            ];
        });
    }

    public function toggleWidget(string $widgetId): void
    {
        $widgets = collect($this->widgets)->map(function ($widget) use ($widgetId) {
            if ($widget['id'] === $widgetId) {
                $widget['visible'] = !($widget['visible'] ?? true);
            }
            return $widget;
        })->toArray();

        $this->widgets = $widgets;
        session()->put('dashboard_widgets', $this->widgets);
    }




    public function refreshData(): void
    {
        $user = Auth::user();
        if ($user) {
            Cache::forget("dashboard_widgets:user_{$user->id}:branch_".branch_context_cache_key());
        }
        $this->loadWidgetData();
    }

    protected function getDefaultWidgets(): array
    {
        return [
            ['id' => 'sales_today', 'title' => __("Today's Sales"), 'visible' => true, 'position' => 1],
            ['id' => 'revenue_month', 'title' => __('Monthly Revenue'), 'visible' => true, 'position' => 2],
            ['id' => 'total_products', 'title' => __('Total Products'), 'visible' => true, 'position' => 3],
            ['id' => 'total_customers', 'title' => __('Total Customers'), 'visible' => true, 'position' => 4],
            ['id' => 'low_stock', 'title' => __('Low Stock Items'), 'visible' => true, 'position' => 5],
            ['id' => 'pending_orders', 'title' => __('Pending Orders'), 'visible' => true, 'position' => 6],
        ];
    }

    public function render()
    {
        return view('livewire.components.dashboard-widgets');
    }
}
