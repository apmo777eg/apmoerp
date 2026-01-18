<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Branch;
use App\Models\Module;
use App\Models\Product;
use App\Models\ReportDefinition;
use App\Models\User;
use App\Services\Contracts\ReportServiceInterface;
use App\Traits\HandlesServiceErrors;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ReportService - Report generation service
 *
 * SECURITY NOTE: All raw SQL expressions in this service use only hardcoded column names.
 * Parameters like $branchId and date ranges are passed through where() with proper binding.
 * No user input is interpolated into the SQL expressions.
 */
class ReportService implements ReportServiceInterface
{
    use HandlesServiceErrors;

    protected ?BranchAccessService $branchAccessService = null;

    public function __construct(?BranchAccessService $branchAccessService = null)
    {
        // Use provided instance or resolve from container
        $this->branchAccessService = $branchAccessService ?? app(BranchAccessService::class);
    }

    public function financeSummary(int $branchId, ?string $from = null, ?string $to = null): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($branchId, $from, $to) {
                $from = $from ?: now()->startOfMonth()->toDateString();
                $to = $to ?: now()->endOfMonth()->toDateString();

                // V34-CRIT-01 FIX: Use sale_date instead of created_at for accurate financial reporting
                // Also filter out non-revenue statuses (draft, cancelled)
                $sales = DB::table('sales')
                    ->where('branch_id', $branchId)
                    ->whereDate('sale_date', '>=', $from)
                    ->whereDate('sale_date', '<=', $to)
                    ->whereNotIn('status', ['draft', 'cancelled', 'void', 'voided', 'returned', 'refunded'])
                    ->select(DB::raw('COALESCE(SUM(total_amount), 0) as total'), DB::raw('COALESCE(SUM(paid_amount), 0) as paid'))
                    ->first();

                // V34-CRIT-01 FIX: Use purchase_date instead of created_at for accurate financial reporting
                // Also filter out non-relevant statuses (draft, cancelled)
                $purchases = DB::table('purchases')
                    ->where('branch_id', $branchId)
                    ->whereDate('purchase_date', '>=', $from)
                    ->whereDate('purchase_date', '<=', $to)
                    ->whereNotIn('status', ['draft', 'cancelled', 'void', 'voided', 'returned', 'refunded'])
                    ->select(DB::raw('COALESCE(SUM(total_amount), 0) as total'), DB::raw('COALESCE(SUM(paid_amount), 0) as paid'))
                    ->first();

                return [
                    'period' => [$from, $to],
                    'sales' => ['total' => (float) ($sales->total ?? 0), 'paid' => (float) ($sales->paid ?? 0)],
                    'purchases' => ['total' => (float) ($purchases->total ?? 0), 'paid' => (float) ($purchases->paid ?? 0)],
                    'pnl' => (float) ($sales->total ?? 0) - (float) ($purchases->total ?? 0),
                ];
            },
            operation: 'financeSummary',
            context: ['branch_id' => $branchId, 'from' => $from, 'to' => $to]
        );
    }

    public function topProducts(int $branchId, int $limit = 10): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($branchId, $limit) {
                $rows = DB::table('sale_items as si')
                    ->join('sales as s', 's.id', '=', 'si.sale_id')
                    ->join('products as p', 'p.id', '=', 'si.product_id')
                    ->where('s.branch_id', $branchId)
                    ->selectRaw('p.id, p.name, SUM(si.quantity*si.unit_price) as gross')
                    ->groupBy('p.id', 'p.name')
                    ->orderByDesc('gross')
                    ->limit($limit)
                    ->get();

                return $rows->map(fn ($r) => ['id' => $r->id, 'name' => $r->name, 'gross' => (float) $r->gross])->all();
            },
            operation: 'topProducts',
            context: ['branch_id' => $branchId, 'limit' => $limit]
        );
    }

    public function getAvailableReports(?User $user = null, ?int $moduleId = null, ?int $branchId = null): Collection
    {
        return $this->handleServiceOperation(
            callback: function () use ($user, $moduleId) {
                $query = ReportDefinition::active()->ordered();

                if ($moduleId) {
                    $query->where('module_id', $moduleId);
                }

                if ($user && ! $user->hasAnyRole(['Super Admin', 'super-admin'])) {
                    $query->where('is_branch_specific', true);
                }

                return $query->get();
            },
            operation: 'getAvailableReports',
            context: ['user_id' => $user?->id, 'module_id' => $moduleId, 'branch_id' => $branchId]
        );
    }

    public function generateReport(string $reportKey, array $filters = [], ?User $user = null): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($reportKey, $filters, $user) {
                $report = ReportDefinition::where('report_key', $reportKey)->first();

                $dataSource = $report?->data_source ?? $reportKey;

                $data = match (true) {
                    str_contains($dataSource, 'inventory'), str_contains($dataSource, 'products') => $this->getInventoryReportData($filters, $user),
                    str_contains($dataSource, 'sales') => $this->getSalesReportData($filters, $user),
                    str_contains($dataSource, 'purchases') => $this->getPurchasesReportData($filters, $user),
                    str_contains($dataSource, 'expenses') => $this->getExpensesReportData($filters, $user),
                    str_contains($dataSource, 'income') => $this->getIncomeReportData($filters, $user),
                    str_contains($dataSource, 'customers') => $this->getCustomersReportData($filters, $user),
                    str_contains($dataSource, 'suppliers') => $this->getSuppliersReportData($filters, $user),
                    default => ['items' => collect(), 'summary' => []],
                };

                return [
                    'report' => $report,
                    'data' => $data['items'],
                    'summary' => $data['summary'] ?? [],
                    'filters' => $filters,
                    'generated_at' => now(),
                ];
            },
            operation: 'generateReport',
            context: ['report_key' => $reportKey, 'user_id' => $user?->id]
        );
    }

    public function getInventoryReportData(array $filters, ?User $user): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($filters, $user) {
                $query = Product::query()
                    ->with(['module', 'branch', 'fieldValues.field'])
                    ->parentsOnly();

                if ($user && ! $user->hasAnyRole(['Super Admin', 'super-admin'])) {
                    $query = $this->branchAccessService->filterQueryByBranch($query, $user);
                }

                if (! empty($filters['branch_id'])) {
                    $query->where('branch_id', $filters['branch_id']);
                }

                if (! empty($filters['module_id'])) {
                    $query->where('module_id', $filters['module_id']);
                }

                if (! empty($filters['status'])) {
                    $query->where('status', $filters['status']);
                }

                if (! empty($filters['date_from'])) {
                    $query->whereDate('created_at', '>=', $filters['date_from']);
                }

                if (! empty($filters['date_to'])) {
                    $query->whereDate('created_at', '<=', $filters['date_to']);
                }

                $items = $query->get();

                // V34-MED-01 FIX: Calculate actual inventory value using stock_quantity * cost
                // Previously was computing ($p->default_price ?? 0) * 1 which is meaningless
                $summary = [
                    'total_products' => $items->count(),
                    'total_value' => $items->sum(fn ($p) => ((float) ($p->stock_quantity ?? 0)) * ((float) ($p->cost ?? $p->standard_cost ?? 0))),
                    'total_cost' => $items->sum(fn ($p) => (float) ($p->standard_cost ?? 0)),
                    'by_module' => $items->groupBy('module_id')->map(fn ($g) => $g->count()),
                    'by_status' => $items->groupBy('status')->map(fn ($g) => $g->count()),
                ];

                return ['items' => $items, 'summary' => $summary];
            },
            operation: 'getInventoryReportData',
            context: ['user_id' => $user?->id]
        );
    }

    public function getSalesReportData(array $filters, ?User $user): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($filters, $user) {
                $query = DB::table('sales')
                    ->select([
                        'sales.*',
                        'customers.name as customer_name',
                        'branches.name as branch_name',
                    ])
                    ->leftJoin('customers', 'sales.customer_id', '=', 'customers.id')
                    ->leftJoin('branches', 'sales.branch_id', '=', 'branches.id');

                if ($user && ! $user->hasAnyRole(['Super Admin', 'super-admin'])) {
                    $branchIds = $this->branchAccessService->getUserBranches($user)->pluck('id');
                    $query->whereIn('sales.branch_id', $branchIds);
                }

                // V34-CRIT-01 FIX: Use sale_date instead of created_at for accurate financial reporting
                $this->applyDateFilters($query, $filters, 'sales.sale_date');
                $this->applyBranchFilter($query, $filters, 'sales.branch_id');

                if (! empty($filters['status'])) {
                    $query->where('sales.status', $filters['status']);
                } else {
                    // V34-CRIT-01 FIX: Filter out non-revenue statuses by default
                    $query->whereNotIn('sales.status', ['draft', 'cancelled', 'void', 'voided', 'returned', 'refunded']);
                }

                $items = $query->orderBy('sales.sale_date', 'desc')->get();

                $summary = [
                    'total_sales' => $items->count(),
                    'total_amount' => $items->sum('total_amount'),
                    'total_paid' => $items->sum('paid_amount'),
                    'total_due' => $items->sum(fn ($item) => max(0, ($item->total_amount ?? 0) - ($item->paid_amount ?? 0))),
                    'by_status' => $items->groupBy('status')->map(fn ($g) => ['count' => $g->count(), 'amount' => $g->sum('total_amount')]),
                    'by_branch' => $items->groupBy('branch_name')->map(fn ($g) => ['count' => $g->count(), 'amount' => $g->sum('total_amount')]),
                ];

                return ['items' => $items, 'summary' => $summary];
            },
            operation: 'getSalesReportData',
            context: ['user_id' => $user?->id]
        );
    }

    public function getPurchasesReportData(array $filters, ?User $user): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($filters, $user) {
                $query = DB::table('purchases')
                    ->select(['purchases.*', 'suppliers.name as supplier_name', 'branches.name as branch_name'])
                    ->leftJoin('suppliers', 'purchases.supplier_id', '=', 'suppliers.id')
                    ->leftJoin('branches', 'purchases.branch_id', '=', 'branches.id');

                if ($user && ! $user->hasAnyRole(['Super Admin', 'super-admin'])) {
                    $branchIds = $this->branchAccessService->getUserBranches($user)->pluck('id');
                    $query->whereIn('purchases.branch_id', $branchIds);
                }

                // V34-CRIT-01 FIX: Use purchase_date instead of created_at for accurate financial reporting
                $this->applyDateFilters($query, $filters, 'purchases.purchase_date');
                $this->applyBranchFilter($query, $filters, 'purchases.branch_id');

                if (! empty($filters['status'])) {
                    $query->where('purchases.status', $filters['status']);
                } else {
                    // V34-CRIT-01 FIX: Filter out non-relevant statuses by default
                    $query->whereNotIn('purchases.status', ['draft', 'cancelled', 'void', 'voided', 'returned', 'refunded']);
                }

                $items = $query->orderBy('purchases.purchase_date', 'desc')->get();

                return [
                    'items' => $items,
                    'summary' => [
                        'total_purchases' => $items->count(),
                        'total_amount' => $items->sum('total_amount'),
                        'total_paid' => $items->sum('paid_amount'),
                        'total_due' => $items->sum(fn ($item) => max(0, ($item->total_amount ?? 0) - ($item->paid_amount ?? 0))),
                    ],
                ];
            },
            operation: 'getPurchasesReportData',
            context: ['user_id' => $user?->id]
        );
    }

    public function getExpensesReportData(array $filters, ?User $user): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($filters, $user) {
                $query = DB::table('expenses')
                    ->select(['expenses.*', 'expense_categories.name as category_name', 'branches.name as branch_name'])
                    ->leftJoin('expense_categories', 'expenses.category_id', '=', 'expense_categories.id')
                    ->leftJoin('branches', 'expenses.branch_id', '=', 'branches.id');

                if ($user && ! $user->hasAnyRole(['Super Admin', 'super-admin'])) {
                    $branchIds = $this->branchAccessService->getUserBranches($user)->pluck('id');
                    $query->whereIn('expenses.branch_id', $branchIds);
                }

                $this->applyDateFilters($query, $filters, 'expenses.expense_date');
                $this->applyBranchFilter($query, $filters, 'expenses.branch_id');

                if (! empty($filters['category_id'])) {
                    $query->where('expenses.category_id', $filters['category_id']);
                }

                $items = $query->orderBy('expenses.expense_date', 'desc')->get();

                return [
                    'items' => $items,
                    'summary' => [
                        'total_expenses' => $items->count(),
                        'total_amount' => $items->sum('amount'),
                        'by_category' => $items->groupBy('category_name')->map(fn ($g) => $g->sum('amount')),
                        'by_branch' => $items->groupBy('branch_name')->map(fn ($g) => $g->sum('amount')),
                    ],
                ];
            },
            operation: 'getExpensesReportData',
            context: ['user_id' => $user?->id]
        );
    }

    public function getIncomeReportData(array $filters, ?User $user): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($filters, $user) {
                $query = DB::table('incomes')
                    ->select(['incomes.*', 'income_categories.name as category_name', 'branches.name as branch_name'])
                    ->leftJoin('income_categories', 'incomes.category_id', '=', 'income_categories.id')
                    ->leftJoin('branches', 'incomes.branch_id', '=', 'branches.id');

                if ($user && ! $user->hasAnyRole(['Super Admin', 'super-admin'])) {
                    $branchIds = $this->branchAccessService->getUserBranches($user)->pluck('id');
                    $query->whereIn('incomes.branch_id', $branchIds);
                }

                $this->applyDateFilters($query, $filters, 'incomes.income_date');
                $this->applyBranchFilter($query, $filters, 'incomes.branch_id');

                if (! empty($filters['category_id'])) {
                    $query->where('incomes.category_id', $filters['category_id']);
                }

                $items = $query->orderBy('incomes.income_date', 'desc')->get();

                return [
                    'items' => $items,
                    'summary' => [
                        'total_income' => $items->count(),
                        'total_amount' => $items->sum('amount'),
                        'by_category' => $items->groupBy('category_name')->map(fn ($g) => $g->sum('amount')),
                    ],
                ];
            },
            operation: 'getIncomeReportData',
            context: ['user_id' => $user?->id]
        );
    }

    public function getCustomersReportData(array $filters, ?User $user): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($filters, $user) {
                $query = DB::table('customers')->select('customers.*');

                if ($user && ! $user->hasAnyRole(['Super Admin', 'super-admin'])) {
                    $branchIds = $this->branchAccessService->getUserBranches($user)->pluck('id');
                    $query->whereIn('customers.branch_id', $branchIds);
                }

                $this->applyBranchFilter($query, $filters, 'customers.branch_id');

                $items = $query->orderBy('customers.name')->get();

                return [
                    'items' => $items,
                    'summary' => ['total_customers' => $items->count(), 'total_balance' => $items->sum('balance')],
                ];
            },
            operation: 'getCustomersReportData',
            context: ['user_id' => $user?->id]
        );
    }

    public function getSuppliersReportData(array $filters, ?User $user): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($filters, $user) {
                $query = DB::table('suppliers')->select('suppliers.*');

                if ($user && ! $user->hasAnyRole(['Super Admin', 'super-admin'])) {
                    $branchIds = $this->branchAccessService->getUserBranches($user)->pluck('id');
                    $query->whereIn('suppliers.branch_id', $branchIds);
                }

                $this->applyBranchFilter($query, $filters, 'suppliers.branch_id');

                $items = $query->orderBy('suppliers.name')->get();

                return [
                    'items' => $items,
                    'summary' => ['total_suppliers' => $items->count(), 'total_balance' => $items->sum('balance')],
                ];
            },
            operation: 'getSuppliersReportData',
            context: ['user_id' => $user?->id]
        );
    }

    public function getAggregateReport(array $filters, User $user): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($filters, $user) {
                $branches = $user->hasAnyRole(['Super Admin', 'super-admin'])
                    ? Branch::active()->get()
                    : $this->branchAccessService->getUserBranches($user);

                // V34-MED-02 FIX: Parse filter dates into Carbon and apply startOfDay/endOfDay
                $dateFrom = isset($filters['date_from'])
                    ? Carbon::parse($filters['date_from'])->startOfDay()
                    : Carbon::now()->startOfMonth()->startOfDay();
                $dateTo = isset($filters['date_to'])
                    ? Carbon::parse($filters['date_to'])->endOfDay()
                    : Carbon::now()->endOfMonth()->endOfDay();

                // V34-MED-02 FIX: Use sale_date instead of created_at for business reporting
                // Also filter out non-revenue statuses
                $salesByBranch = DB::table('sales')
                    ->select('branch_id', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total'))
                    ->whereIn('branch_id', $branches->pluck('id'))
                    ->whereBetween('sale_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
                    ->whereNotIn('status', ['draft', 'cancelled', 'void', 'voided', 'returned', 'refunded'])
                    ->groupBy('branch_id')
                    ->get()->keyBy('branch_id');

                // V34-MED-02 FIX: Use purchase_date instead of created_at for business reporting
                // Also filter out non-relevant statuses
                $purchasesByBranch = DB::table('purchases')
                    ->select('branch_id', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total'))
                    ->whereIn('branch_id', $branches->pluck('id'))
                    ->whereBetween('purchase_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
                    ->whereNotIn('status', ['draft', 'cancelled', 'void', 'voided', 'returned', 'refunded'])
                    ->groupBy('branch_id')
                    ->get()->keyBy('branch_id');

                $expensesByBranch = DB::table('expenses')
                    ->select('branch_id', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
                    ->whereIn('branch_id', $branches->pluck('id'))
                    ->whereBetween('expense_date', [$dateFrom, $dateTo])
                    ->groupBy('branch_id')
                    ->get()->keyBy('branch_id');

                $branchData = $branches->map(function ($branch) use ($salesByBranch, $purchasesByBranch, $expensesByBranch) {
                    $sales = $salesByBranch->get($branch->id);
                    $purchases = $purchasesByBranch->get($branch->id);
                    $expenses = $expensesByBranch->get($branch->id);

                    return [
                        'branch' => $branch,
                        'sales_count' => $sales?->count ?? 0,
                        'sales_total' => $sales?->total ?? 0,
                        'purchases_count' => $purchases?->count ?? 0,
                        'purchases_total' => $purchases?->total ?? 0,
                        'expenses_count' => $expenses?->count ?? 0,
                        'expenses_total' => $expenses?->total ?? 0,
                        'profit' => ($sales?->total ?? 0) - ($purchases?->total ?? 0) - ($expenses?->total ?? 0),
                    ];
                });

                return [
                    'branches' => $branchData,
                    'totals' => [
                        'sales' => $branchData->sum('sales_total'),
                        'purchases' => $branchData->sum('purchases_total'),
                        'expenses' => $branchData->sum('expenses_total'),
                        'profit' => $branchData->sum('profit'),
                    ],
                    'period' => ['from' => $dateFrom, 'to' => $dateTo],
                ];
            },
            operation: 'getAggregateReport',
            context: ['user_id' => $user->id]
        );
    }

    public function getModuleReport(int $moduleId, array $filters, User $user): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($moduleId, $filters, $user) {
                $module = Module::findOrFail($moduleId);
                $inventoryData = $this->getInventoryReportData(array_merge($filters, ['module_id' => $moduleId]), $user);

                return ['module' => $module, 'inventory' => $inventoryData, 'generated_at' => now()];
            },
            operation: 'getModuleReport',
            context: ['module_id' => $moduleId, 'user_id' => $user->id]
        );
    }

    protected function applyDateFilters($query, array $filters, string $dateColumn): void
    {
        if (! empty($filters['date_from'])) {
            $query->whereDate($dateColumn, '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate($dateColumn, '<=', $filters['date_to']);
        }
    }

    protected function applyBranchFilter($query, array $filters, string $branchColumn): void
    {
        if (! empty($filters['branch_id'])) {
            $query->where($branchColumn, $filters['branch_id']);
        }
    }
}
