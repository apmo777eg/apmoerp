<?php

namespace App\Services\Reports;

use App\Models\BankAccount;
use App\Models\Purchase;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CashFlowForecastService
{
    /**
     * Generate cash flow forecast for next N days
     */
    public function getForecast($daysAhead = 30)
    {
        $today = Carbon::now()->startOfDay();
        $endDate = $today->copy()->addDays($daysAhead);

        // Get current cash position
        $currentCash = $this->getCurrentCashPosition();

        // Get expected cash inflows (receivables)
        $expectedInflows = $this->getExpectedInflows($today, $endDate);

        // Get expected cash outflows (payables)
        $expectedOutflows = $this->getExpectedOutflows($today, $endDate);

        // Calculate daily forecast
        $dailyForecast = $this->calculateDailyForecast(
            $currentCash,
            $expectedInflows,
            $expectedOutflows,
            $daysAhead
        );

        return [
            'current_cash' => decimal_float($currentCash),
            'forecast_period_days' => $daysAhead,
            'total_expected_inflows' => decimal_float($expectedInflows->sum('amount')),
            'total_expected_outflows' => decimal_float($expectedOutflows->sum('amount')),
            'ending_cash_forecast' => decimal_float($dailyForecast->last()['ending_balance']),
            'daily_forecast' => $dailyForecast,
            'cash_shortage_dates' => $this->identifyCashShortages($dailyForecast),
            'recommendations' => $this->getRecommendations($dailyForecast),
        ];
    }

    /**
     * Get current cash position from bank accounts
     */
    private function getCurrentCashPosition()
    {
        // Use the current_balance column (not a non-existent `balance` field)
        // and normalize to a bcmath-friendly string to keep precision
        $balance = BankAccount::sum('current_balance');

        return bcadd((string) $balance, '0', 2);
    }

    /**
     * Get expected cash inflows (unpaid sales)
     */
    private function getExpectedInflows($startDate, $endDate)
    {
        // Use actual database column names: due_date (not payment_due_date accessor)
        // Calculate due_total as (total_amount - paid_amount) since it's an accessor
        return Sale::select(
            'id',
            'customer_id',
            'due_date as due_date',
            DB::raw('(total_amount - paid_amount) as amount')
        )
            ->where('payment_status', '!=', 'paid')
            ->whereRaw('(total_amount - paid_amount) > 0')
            ->whereBetween('due_date', [$startDate, $endDate])
            ->whereNull('deleted_at')
            ->orderBy('due_date')
            ->get();
    }

    /**
     * Get expected cash outflows (unpaid purchases)
     */
    private function getExpectedOutflows($startDate, $endDate)
    {
        // Use actual database column names: due_date (not payment_due_date accessor)
        // Calculate due_total as (total_amount - paid_amount) since it's an accessor
        return Purchase::select(
            'id',
            'supplier_id',
            'due_date as due_date',
            DB::raw('(total_amount - paid_amount) as amount')
        )
            ->where('payment_status', '!=', 'paid')
            ->whereRaw('(total_amount - paid_amount) > 0')
            ->whereBetween('due_date', [$startDate, $endDate])
            ->whereNull('deleted_at')
            ->orderBy('due_date')
            ->get();
    }

    /**
     * Calculate daily cash flow forecast
     */
    private function calculateDailyForecast($startingCash, $inflows, $outflows, $days)
    {
        $forecast = collect();
        $runningBalance = $startingCash;
        $today = Carbon::now()->startOfDay();

        for ($i = 0; $i < $days; $i++) {
            $date = $today->copy()->addDays($i);
            $dateStr = $date->toDateString();

            // Calculate inflows for this date
            $dailyInflows = $inflows
                ->filter(fn ($item) => Carbon::parse($item->due_date)->toDateString() === $dateStr)
                ->sum('amount');

            // Calculate outflows for this date
            $dailyOutflows = $outflows
                ->filter(fn ($item) => Carbon::parse($item->due_date)->toDateString() === $dateStr)
                ->sum('amount');

            // Calculate net flow using bcmath
            $dailyInflowsStr = (string) $dailyInflows;
            $dailyOutflowsStr = (string) $dailyOutflows;
            $netFlow = bcsub($dailyInflowsStr, $dailyOutflowsStr, 2);

            // Update running balance
            $runningBalance = bcadd($runningBalance, $netFlow, 2);

            $forecast->push([
                'date' => $dateStr,
                'day' => $i + 1,
                'inflows' => decimal_float($dailyInflowsStr),
                'outflows' => decimal_float($dailyOutflowsStr),
                'net_flow' => decimal_float($netFlow),
                'ending_balance' => decimal_float($runningBalance),
                'status' => bccomp($runningBalance, '0', 2) >= 0 ? 'healthy' : 'shortage',
            ]);
        }

        return $forecast;
    }

    /**
     * Identify dates with cash shortages
     */
    private function identifyCashShortages($forecast)
    {
        return $forecast
            ->filter(fn ($day) => $day['status'] === 'shortage')
            ->map(fn ($day) => [
                'date' => $day['date'],
                'shortage_amount' => abs($day['ending_balance']),
                'severity' => abs($day['ending_balance']) > 10000 ? 'HIGH' : 'MEDIUM',
            ])
            ->values()
            ->all();
    }

    /**
     * Get cash flow recommendations
     */
    private function getRecommendations($forecast)
    {
        $recommendations = [];

        $shortages = $forecast->filter(fn ($day) => $day['status'] === 'shortage');

        if ($shortages->count() > 0) {
            $recommendations[] = [
                'priority' => 'HIGH',
                'message' => 'Cash shortage detected on '.$shortages->count().' days',
                'action' => 'Consider arranging short-term financing or accelerating collections',
            ];
        }

        $minBalance = $forecast->min('ending_balance');
        if ($minBalance < 5000 && $minBalance >= 0) {
            $recommendations[] = [
                'priority' => 'MEDIUM',
                'message' => 'Cash balance drops below recommended minimum',
                'action' => 'Maintain higher cash reserves for emergencies',
            ];
        }

        if ($recommendations === []) {
            $recommendations[] = [
                'priority' => 'LOW',
                'message' => 'Cash flow looks healthy for forecast period',
                'action' => 'Continue monitoring receivables and payables',
            ];
        }

        return $recommendations;
    }
}
