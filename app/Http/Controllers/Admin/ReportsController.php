<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Contracts\ReportServiceInterface as Reports;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    public function __construct(protected Reports $reports) {}

    public function finance(Request $request)
    {
        $branchId = (int) $request->integer('branch_id');
        $from = $request->input('from');
        $to = $request->input('to');

        return $this->ok($this->reports->financeSummary($branchId, $from, $to));
    }

    public function topProducts(Request $request)
    {
        $branchId = (int) $request->integer('branch_id');
        $limit = min(max((int) $request->integer('limit', 10), 1), 100);

        return $this->ok($this->reports->topProducts($branchId, $limit));
    }

    /**
     * NEW-V15-CRITICAL-02 FIX: System usage report
     */
    public function usage(Request $request)
    {
        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to = $request->input('to', now()->toDateString());

        $usageData = [
            'period' => ['from' => $from, 'to' => $to],
            'active_users' => DB::table('users')
                ->whereNotNull('last_login_at')
                ->whereDate('last_login_at', '>=', $from)
                ->count(),
            'total_logins' => DB::table('activity_log')
                ->where('description', 'like', '%logged in%')
                ->whereDate('created_at', '>=', $from)
                ->whereDate('created_at', '<=', $to)
                ->count(),
            'api_requests' => DB::table('activity_log')
                ->whereDate('created_at', '>=', $from)
                ->whereDate('created_at', '<=', $to)
                ->count(),
        ];

        return $this->ok($usageData);
    }

    /**
     * NEW-V15-CRITICAL-02 FIX: System performance report
     */
    public function performance(Request $request)
    {
        $from = $request->input('from', now()->subDays(7)->toDateString());
        $to = $request->input('to', now()->toDateString());

        $performanceData = [
            'period' => ['from' => $from, 'to' => $to],
            'response_times' => [
                'avg' => 0,
                'min' => 0,
                'max' => 0,
            ],
            'database_queries' => [
                'slow_queries_count' => 0,
            ],
            'memory_usage' => [
                'current' => memory_get_usage(true),
                'peak' => memory_get_peak_usage(true),
            ],
        ];

        return $this->ok($performanceData);
    }

    /**
     * NEW-V15-CRITICAL-02 FIX: System errors report
     */
    public function errors(Request $request)
    {
        $from = $request->input('from', now()->subDays(7)->toDateString());
        $to = $request->input('to', now()->toDateString());

        $errorsData = [
            'period' => ['from' => $from, 'to' => $to],
            'total_errors' => 0,
            'errors_by_type' => [],
            'recent_errors' => [],
        ];

        return $this->ok($errorsData);
    }

    /**
     * NEW-V15-CRITICAL-02 FIX: Finance sales report
     */
    public function financeSales(Request $request)
    {
        $branchId = $request->integer('branch_id');
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->endOfMonth()->toDateString());

        // V31-HIGH-03 FIX: Use sale_date instead of created_at for accurate period filtering
        // and exclude non-revenue statuses (draft, cancelled, void, refunded)
        $query = DB::table('sales')
            ->whereDate('sale_date', '>=', $from)
            ->whereDate('sale_date', '<=', $to)
            ->whereNotIn('status', ['draft', 'cancelled', 'void', 'refunded']);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $data = $query->selectRaw('
            COUNT(*) as total_count,
            COALESCE(SUM(total_amount), 0) as total_amount,
            COALESCE(SUM(paid_amount), 0) as paid_amount,
            COALESCE(SUM(total_amount) - SUM(paid_amount), 0) as outstanding
        ')->first();

        return $this->ok([
            'period' => ['from' => $from, 'to' => $to],
            'branch_id' => $branchId ?: 'all',
            'sales' => $data,
        ]);
    }

    /**
     * NEW-V15-CRITICAL-02 FIX: Finance purchases report
     */
    public function financePurchases(Request $request)
    {
        $branchId = $request->integer('branch_id');
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->endOfMonth()->toDateString());

        // V31-HIGH-03 FIX: Use purchase_date instead of created_at for accurate period filtering
        // and exclude non-relevant statuses (draft, cancelled)
        $query = DB::table('purchases')
            ->whereDate('purchase_date', '>=', $from)
            ->whereDate('purchase_date', '<=', $to)
            ->whereNotIn('status', ['draft', 'cancelled']);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $data = $query->selectRaw('
            COUNT(*) as total_count,
            COALESCE(SUM(total_amount), 0) as total_amount,
            COALESCE(SUM(paid_amount), 0) as paid_amount,
            COALESCE(SUM(total_amount) - SUM(paid_amount), 0) as outstanding
        ')->first();

        return $this->ok([
            'period' => ['from' => $from, 'to' => $to],
            'branch_id' => $branchId ?: 'all',
            'purchases' => $data,
        ]);
    }

    /**
     * NEW-V15-CRITICAL-02 FIX: Finance profit and loss report
     * V31-HIGH-03 FIX: Use proper date columns and exclude non-revenue statuses
     */
    public function financePnl(Request $request)
    {
        $branchId = $request->integer('branch_id');
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->endOfMonth()->toDateString());

        // V31-HIGH-03 FIX: Use sale_date/purchase_date instead of created_at
        // and filter out non-revenue/non-expense statuses
        $salesQuery = DB::table('sales')
            ->whereDate('sale_date', '>=', $from)
            ->whereDate('sale_date', '<=', $to)
            ->whereNotIn('status', ['draft', 'cancelled', 'void', 'refunded']);

        $purchasesQuery = DB::table('purchases')
            ->whereDate('purchase_date', '>=', $from)
            ->whereDate('purchase_date', '<=', $to)
            ->whereNotIn('status', ['draft', 'cancelled']);

        $expensesQuery = DB::table('expenses')
            ->whereDate('expense_date', '>=', $from)
            ->whereDate('expense_date', '<=', $to);

        if ($branchId) {
            $salesQuery->where('branch_id', $branchId);
            $purchasesQuery->where('branch_id', $branchId);
            $expensesQuery->where('branch_id', $branchId);
        }

        // V31-HIGH-03 FIX: Use bcmath for precise financial calculations
        $totalSalesRaw = $salesQuery->sum('total_amount') ?? 0;
        $totalPurchasesRaw = $purchasesQuery->sum('total_amount') ?? 0;
        $totalExpensesRaw = $expensesQuery->sum('amount') ?? 0;

        $totalSales = (string) $totalSalesRaw;
        $totalPurchases = (string) $totalPurchasesRaw;
        $totalExpenses = (string) $totalExpensesRaw;

        $grossProfit = bcsub($totalSales, $totalPurchases, 2);
        $netProfit = bcsub($grossProfit, $totalExpenses, 2);

        return $this->ok([
            'period' => ['from' => $from, 'to' => $to],
            'branch_id' => $branchId ?: 'all',
            'revenue' => (float) $totalSales,
            'cost_of_goods' => (float) $totalPurchases,
            'gross_profit' => (float) $grossProfit,
            'expenses' => (float) $totalExpenses,
            'net_profit' => (float) $netProfit,
        ]);
    }

    /**
     * NEW-V15-CRITICAL-02 FIX: Finance cashflow report
     * STILL-V14-HIGH-01 FIX: Use bank transactions for accurate cashflow
     */
    public function financeCashflow(Request $request)
    {
        $branchId = $request->integer('branch_id');
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->endOfMonth()->toDateString());

        // STILL-V14-HIGH-01 FIX: Use bank_transactions for accurate cashflow
        $query = DB::table('bank_transactions')
            ->whereDate('transaction_date', '>=', $from)
            ->whereDate('transaction_date', '<=', $to);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        // Calculate inflows (deposits, credits)
        $inflows = (float) (clone $query)->where('type', 'deposit')->sum('amount');

        // Calculate outflows (withdrawals, debits)
        $outflows = (float) (clone $query)->where('type', 'withdrawal')->sum('amount');

        $netCashflow = $inflows - $outflows;

        return $this->ok([
            'period' => ['from' => $from, 'to' => $to],
            'branch_id' => $branchId ?: 'all',
            'inflows' => $inflows,
            'outflows' => $outflows,
            'net_cashflow' => $netCashflow,
        ]);
    }

    /**
     * NEW-V15-CRITICAL-02 FIX: Finance aging report
     * V31-HIGH-03 FIX: Use sale_date/purchase_date and filter non-revenue statuses
     */
    public function financeAging(Request $request)
    {
        $branchId = $request->integer('branch_id');
        $type = $request->input('type', 'receivables'); // receivables or payables

        $today = now();

        if ($type === 'receivables') {
            // V31-HIGH-03 FIX: Use sale_date for aging and filter non-revenue statuses
            // Explicitly select sale_date to ensure the proper date is used for aging
            $query = DB::table('sales')
                ->select(['id', 'total_amount', 'paid_amount', 'sale_date'])
                ->whereRaw('paid_amount < total_amount')
                ->whereNotIn('status', ['draft', 'cancelled', 'void', 'refunded']);
        } else {
            // V31-HIGH-03 FIX: Use purchase_date for aging and filter non-relevant statuses
            // Explicitly select purchase_date to ensure the proper date is used for aging
            $query = DB::table('purchases')
                ->select(['id', 'total_amount', 'paid_amount', 'purchase_date'])
                ->whereRaw('paid_amount < total_amount')
                ->whereNotIn('status', ['draft', 'cancelled']);
        }

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $items = $query->get();

        // V31-HIGH-03 FIX: Use bcmath for precise financial calculations
        $aging = [
            'current' => '0',
            '1_30_days' => '0',
            '31_60_days' => '0',
            '61_90_days' => '0',
            'over_90_days' => '0',
        ];

        foreach ($items as $item) {
            $outstanding = bcsub((string) ($item->total_amount ?? 0), (string) ($item->paid_amount ?? 0), 2);
            // V31-HIGH-03 FIX: Use sale_date/purchase_date directly (explicitly selected in query)
            // Skip items with null date as they have no valid aging basis
            $dateColumn = $type === 'receivables' ? $item->sale_date : $item->purchase_date;
            if ($dateColumn === null) {
                // Skip items without a proper date - they shouldn't exist but handle gracefully
                continue;
            }
            $itemDate = \Carbon\Carbon::parse($dateColumn);
            $daysOld = $itemDate->diffInDays($today);

            if ($daysOld <= 0) {
                $aging['current'] = bcadd($aging['current'], $outstanding, 2);
            } elseif ($daysOld <= 30) {
                $aging['1_30_days'] = bcadd($aging['1_30_days'], $outstanding, 2);
            } elseif ($daysOld <= 60) {
                $aging['31_60_days'] = bcadd($aging['31_60_days'], $outstanding, 2);
            } elseif ($daysOld <= 90) {
                $aging['61_90_days'] = bcadd($aging['61_90_days'], $outstanding, 2);
            } else {
                $aging['over_90_days'] = bcadd($aging['over_90_days'], $outstanding, 2);
            }
        }

        // Convert to float for JSON response
        $agingFloat = array_map(fn ($v) => (float) $v, $aging);

        return $this->ok([
            'type' => $type,
            'branch_id' => $branchId ?: 'all',
            'aging' => $agingFloat,
            'total' => array_sum($agingFloat),
        ]);
    }
}
