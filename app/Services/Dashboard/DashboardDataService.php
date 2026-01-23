<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Enums\SaleStatus;
use App\Models\DashboardWidget;
use App\Models\WidgetDataCache;
use Illuminate\Support\Facades\DB;

/**
 * DashboardDataService - Widget data generation
 *
 * Handles:
 * - Widget data generation for all widget types
 * - Widget data caching
 * - Sales, inventory, HR, helpdesk widget data
 *
 * SECURITY NOTE: All raw SQL expressions in this service use only hardcoded column names.
 * Parameters like $branchId and $userId are passed through where() with proper binding.
 * No user input is interpolated into the SQL expressions.
 */
class DashboardDataService
{
    /**
     * Get widget data with caching.
     */
    public function getWidgetData(int $userId, int $widgetId, ?int $branchId = null, bool $refresh = false): array
    {
        if (! $refresh) {
            $cached = WidgetDataCache::getCached($userId, $widgetId, $branchId);
            if ($cached !== null) {
                return $cached;
            }
        }

        $widget = DashboardWidget::findOrFail($widgetId);
        $data = $this->generateWidgetData($widget, $userId, $branchId);

        WidgetDataCache::store($userId, $widgetId, $data, $branchId, 30);

        return $data;
    }

    /**
     * Generate widget data based on widget type.
     *
     * NEW-001/NEW-003 FIX: Added unified widget key mapping and proper logging for unsupported widgets.
     * Widget keys from Livewire components (sales_today, revenue_month, etc.) are now mapped
     * to the appropriate data generators.
     */
    public function generateWidgetData(DashboardWidget $widget, int $userId, ?int $branchId): array
    {
        $data = match ($widget->key) {
            // Core sales widgets
            'sales_today' => $this->generateSalesTodayData($branchId),
            'sales_this_week' => $this->generateSalesWeekData($branchId),
            'sales_this_month', 'revenue_month' => $this->generateSalesMonthData($branchId),

            // Product/Inventory widgets
            'top_selling_products' => $this->generateTopSellingProductsData($branchId),
            'total_products' => $this->generateTotalProductsData($branchId),
            'low_stock_alerts', 'low_stock' => $this->generateLowStockAlertsData($branchId),

            // Customer widgets
            'top_customers' => $this->generateTopCustomersData($branchId),
            'total_customers' => $this->generateTotalCustomersData($branchId),

            // Order widgets
            'pending_orders' => $this->generatePendingOrdersData($branchId),

            // Finance/Accounting widgets
            'rent_invoices_due' => $this->generateRentInvoicesDueData($branchId),
            'cash_bank_balance' => $this->generateCashBankBalanceData($branchId),

            // Helpdesk widgets
            'tickets_summary' => $this->generateTicketsSummaryData($branchId),

            // HR widgets
            'attendance_snapshot' => $this->generateAttendanceSnapshotData($branchId),

            // NEW-003 FIX: Log warning for unsupported widget keys instead of silent failure
            default => $this->handleUnsupportedWidget($widget->key),
        };

        return [
            'widget_id' => $widget->id,
            'widget_key' => $widget->key,
            'data' => $data,
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Handle unsupported widget keys with proper logging
     *
     * NEW-003 FIX: Log warning when an unsupported widget key is requested
     */
    protected function handleUnsupportedWidget(string $key): array
    {
        \Illuminate\Support\Facades\Log::warning('DashboardDataService: Unsupported widget key requested', [
            'widget_key' => $key,
            'available_keys' => [
                'sales_today', 'sales_this_week', 'sales_this_month', 'revenue_month',
                'top_selling_products', 'total_products', 'low_stock_alerts', 'low_stock',
                'top_customers', 'total_customers', 'pending_orders',
                'rent_invoices_due', 'cash_bank_balance', 'tickets_summary', 'attendance_snapshot',
            ],
        ]);

        return [
            'message' => 'Widget data generator not implemented for: '.$key,
            'status' => 'unsupported',
        ];
    }

    /**
     * Generate total products count data.
     *
     * NEW-001 FIX: Added generator for 'total_products' widget key used in Livewire components
     */
    public function generateTotalProductsData(?int $branchId): array
    {
        $query = DB::table('products')
            ->whereNull('deleted_at');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $totalProducts = $query->count();
        $activeProducts = (clone $query)->where('status', 'active')->count();

        return [
            'total_products' => $totalProducts,
            'active_products' => $activeProducts,
        ];
    }

    /**
     * Generate total customers count data.
     *
     * NEW-001 FIX: Added generator for 'total_customers' widget key used in Livewire components
     */
    public function generateTotalCustomersData(?int $branchId): array
    {
        $query = DB::table('customers')
            ->whereNull('deleted_at');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $totalCustomers = $query->count();
        $newThisMonth = (clone $query)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();

        return [
            'total_customers' => $totalCustomers,
            'new_this_month' => $newThisMonth,
        ];
    }

    /**
     * Generate pending orders count data.
     *
     * NEW-001 FIX: Added generator for 'pending_orders' widget key used in Livewire components
     */
    public function generatePendingOrdersData(?int $branchId): array
    {
        $query = DB::table('sales')
            ->whereNull('deleted_at')
            ->where('status', 'pending');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $pendingCount = $query->count();
        $pendingTotal = $query->sum('total_amount') ?? 0;

        return [
            'pending_orders' => $pendingCount,
            'pending_total' => $pendingTotal,
            'currency' => setting('general.default_currency', 'EGP'),
        ];
    }

    /**
     * Generate sales today data.
     * V25-MED-01 FIX: Use sale_date instead of created_at for business reporting
     * V35-MED-06 FIX: Exclude all non-revenue statuses
     */
    public function generateSalesTodayData(?int $branchId): array
    {
        $query = DB::table('sales')
            ->whereDate('sale_date', today())
            ->whereNull('deleted_at')
            ->whereNotIn('status', SaleStatus::nonRevenueStatuses());

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $totalSales = $query->sum('total_amount') ?? 0;
        $totalOrders = $query->count();
        $averageOrder = $totalOrders > 0 ? $totalSales / $totalOrders : 0;

        return [
            'total_sales' => $totalSales,
            'total_orders' => $totalOrders,
            'average_order' => $averageOrder,
            'currency' => setting('general.default_currency', 'EGP'),
        ];
    }

    /**
     * Generate sales this week data.
     * V25-MED-01 FIX: Use sale_date instead of created_at for business reporting
     * V35-MED-06 FIX: Exclude all non-revenue statuses
     */
    public function generateSalesWeekData(?int $branchId): array
    {
        $query = DB::table('sales')
            ->whereBetween('sale_date', [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()])
            ->whereNull('deleted_at')
            ->whereNotIn('status', SaleStatus::nonRevenueStatuses());

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return [
            'total_sales' => $query->sum('total_amount') ?? 0,
            'total_orders' => $query->count(),
            'currency' => setting('general.default_currency', 'EGP'),
        ];
    }

    /**
     * Generate sales this month data.
     * V25-MED-01 FIX: Use sale_date instead of created_at for business reporting
     * V35-MED-06 FIX: Exclude all non-revenue statuses
     */
    public function generateSalesMonthData(?int $branchId): array
    {
        $query = DB::table('sales')
            ->whereYear('sale_date', now()->year)
            ->whereMonth('sale_date', now()->month)
            ->whereNull('deleted_at')
            ->whereNotIn('status', SaleStatus::nonRevenueStatuses());

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return [
            'total_sales' => $query->sum('total_amount') ?? 0,
            'total_orders' => $query->count(),
            'currency' => setting('general.default_currency', 'EGP'),
        ];
    }

    /**
     * Generate top selling products data.
     * V25-MED-01 FIX: Use sale_date instead of created_at for business reporting
     * V35-MED-06 FIX: Exclude all non-revenue statuses and soft-deleted sales
     * V50-HIGH-08 FIX: Exclude soft-deleted sale_items and products
     */
    public function generateTopSellingProductsData(?int $branchId, int $limit = 5): array
    {
        $query = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->select(
                'products.id',
                'products.name',
                DB::raw('COALESCE(SUM(sale_items.quantity), 0) as total_quantity'),
                DB::raw('COALESCE(SUM(sale_items.line_total), 0) as total_revenue')
            )
            ->whereNull('sales.deleted_at')
            ->whereNull('sale_items.deleted_at')
            ->whereNull('products.deleted_at')
            ->whereNotIn('sales.status', SaleStatus::nonRevenueStatuses())
            ->whereBetween('sales.sale_date', [now()->subDays(30)->toDateString(), now()->toDateString()])
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_quantity')
            ->limit($limit);

        if ($branchId) {
            $query->where('sales.branch_id', $branchId);
        }

        return [
            'products' => $query->get()->toArray(),
            'currency' => setting('general.default_currency', 'EGP'),
        ];
    }

    /**
     * Generate top customers data.
     * V25-MED-01 FIX: Use sale_date instead of created_at for business reporting
     * V35-MED-06 FIX: Exclude all non-revenue statuses and soft-deleted sales
     * V50-MED-01 FIX: Exclude soft-deleted customers
     */
    public function generateTopCustomersData(?int $branchId, int $limit = 5): array
    {
        $query = DB::table('sales')
            ->join('customers', 'sales.customer_id', '=', 'customers.id')
            ->select(
                'customers.id',
                'customers.name',
                DB::raw('COUNT(sales.id) as total_orders'),
                DB::raw('COALESCE(SUM(sales.total_amount), 0) as total_spent')
            )
            ->whereNull('sales.deleted_at')
            ->whereNull('customers.deleted_at')
            ->whereNotIn('sales.status', SaleStatus::nonRevenueStatuses())
            ->whereBetween('sales.sale_date', [now()->subDays(30)->toDateString(), now()->toDateString()])
            ->groupBy('customers.id', 'customers.name')
            ->orderByDesc('total_spent')
            ->limit($limit);

        if ($branchId) {
            $query->where('sales.branch_id', $branchId);
        }

        return [
            'customers' => $query->get()->toArray(),
            'currency' => setting('general.default_currency', 'EGP'),
        ];
    }

    /**
     * Generate low stock alerts data.
     * STILL-V7-CRITICAL-U02 FIX: Calculate stock from stock_movements instead of products.stock_quantity
     */
    public function generateLowStockAlertsData(?int $branchId): array
    {
        // Calculate current stock from stock_movements (source of truth)
        // V44-HIGH-03 FIX: Exclude soft-deleted stock_movements
        $stockSubquery = DB::table('stock_movements')
            ->select('product_id', DB::raw('COALESCE(SUM(quantity), 0) as current_stock'))
            ->whereNull('deleted_at')
            ->groupBy('product_id');

        $query = DB::table('products')
            ->leftJoinSub($stockSubquery, 'stock', function ($join) {
                $join->on('products.id', '=', 'stock.product_id');
            })
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                DB::raw('COALESCE(stock.current_stock, 0) as stock_quantity'),
                'products.stock_alert_threshold'
            )
            ->whereNull('products.deleted_at')
            ->whereNotNull('products.stock_alert_threshold')
            ->whereRaw('COALESCE(stock.current_stock, 0) <= products.stock_alert_threshold')
            ->where('products.status', 'active')
            ->orderByRaw('COALESCE(stock.current_stock, 0) ASC')
            ->limit(10);

        if ($branchId) {
            $query->where('products.branch_id', $branchId);
        }

        $products = $query->get()->toArray();

        // Count query for total alerts
        // V44-HIGH-03 FIX: Exclude soft-deleted stock_movements
        $countStockSubquery = DB::table('stock_movements')
            ->select('product_id', DB::raw('COALESCE(SUM(quantity), 0) as current_stock'))
            ->whereNull('deleted_at')
            ->groupBy('product_id');

        $countQuery = DB::table('products')
            ->leftJoinSub($countStockSubquery, 'stock', function ($join) {
                $join->on('products.id', '=', 'stock.product_id');
            })
            ->whereNull('products.deleted_at')
            ->whereNotNull('products.stock_alert_threshold')
            ->whereRaw('COALESCE(stock.current_stock, 0) <= products.stock_alert_threshold')
            ->where('products.status', 'active');

        if ($branchId) {
            $countQuery->where('products.branch_id', $branchId);
        }

        return [
            'products' => $products,
            'total_alerts' => $countQuery->count(),
        ];
    }

    /**
     * Generate rent invoices due data.
     * V22-CRIT-03 FIX: Filter by branchId through rental_contracts join
     */
    public function generateRentInvoicesDueData(?int $branchId): array
    {
        $query = DB::table('rental_invoices')
            ->join('rental_contracts', 'rental_invoices.contract_id', '=', 'rental_contracts.id')
            ->select(
                'rental_invoices.id',
                'rental_invoices.code',
                'rental_invoices.contract_id',
                'rental_invoices.amount',
                'rental_invoices.due_date',
                'rental_invoices.status'
            )
            ->where('rental_invoices.status', 'pending')
            ->whereBetween('rental_invoices.due_date', [now(), now()->addDays(7)])
            ->orderBy('rental_invoices.due_date', 'asc')
            ->limit(10);

        // V22-CRIT-03 FIX: Apply branch filter through rental_contracts
        if ($branchId) {
            $query->where('rental_contracts.branch_id', $branchId);
        }

        $invoices = $query->get()->toArray();
        $totalAmount = collect($invoices)->sum('amount');

        return [
            'invoices' => $invoices,
            'total_amount' => $totalAmount,
            'count' => count($invoices),
            'currency' => setting('general.default_currency', 'EGP'),
        ];
    }

    /**
     * Generate cash and bank balance data.
     * V44-MED-04 FIX: Exclude soft-deleted bank accounts
     */
    public function generateCashBankBalanceData(?int $branchId): array
    {
        $query = DB::table('bank_accounts')
            ->select([
                'id',
                'account_name as name',
                'account_type',
                'current_balance as balance',
                'currency',
            ])
            ->where('status', 'active')
            ->whereNull('deleted_at');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $accounts = $query->get()->toArray();
        $totalBalance = collect($accounts)->sum('balance');

        return [
            'accounts' => $accounts,
            'total_balance' => $totalBalance,
            'currency' => setting('general.default_currency', 'EGP'),
        ];
    }

    /**
     * Generate tickets summary data.
     * V46-MED-01 FIX: Exclude soft-deleted tickets (Ticket model uses SoftDeletes)
     */
    public function generateTicketsSummaryData(?int $branchId): array
    {
        $query = DB::table('tickets')
            ->whereNull('deleted_at');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return [
            'open' => (clone $query)->where('status', 'open')->count(),
            'in_progress' => (clone $query)->where('status', 'in_progress')->count(),
            'on_hold' => (clone $query)->where('status', 'on_hold')->count(),
            'resolved' => (clone $query)->where('status', 'resolved')->count(),
            'overdue' => (clone $query)->where('status', 'open')->where('due_date', '<', now())->count(),
        ];
    }

    /**
     * Generate attendance snapshot data.
     * V46-HIGH-03 FIX: Use canonical column names from Attendance model
     * - Use 'attendance_date' instead of 'date'
     * - Use 'late_minutes > 0' instead of 'is_late' (which is an accessor, not a column)
     * - Use 'on_leave' consistently for leave status
     * V50-HIGH-09 FIX: Exclude soft-deleted attendances and employees
     */
    public function generateAttendanceSnapshotData(?int $branchId): array
    {
        $query = DB::table('attendances')
            ->whereDate('attendance_date', today())
            ->whereNull('attendances.deleted_at');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $total = DB::table('hr_employees')
            ->where('is_active', true)
            ->whereNull('hr_employees.deleted_at');
        if ($branchId) {
            $total->where('branch_id', $branchId);
        }
        $totalEmployees = $total->count();

        return [
            'present' => (clone $query)->where('status', 'present')->count(),
            'absent' => (clone $query)->where('status', 'absent')->count(),
            // V46-HIGH-03 FIX: Use late_minutes column instead of is_late accessor
            'late' => (clone $query)->where('late_minutes', '>', 0)->count(),
            // V46-HIGH-03 FIX: Use consistent 'on_leave' status value
            'on_leave' => (clone $query)->where('status', 'on_leave')->count(),
            'total_employees' => $totalEmployees,
        ];
    }
}
