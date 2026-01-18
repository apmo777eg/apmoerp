<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Reports;

use App\Models\Sale;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use App\Enums\SaleStatus;
use Livewire\Component;

class PosChartsDashboard extends Component
{
    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public ?int $branchId = null;

    #[Layout('layouts.app')]
    public function render()
    {
        $user = Auth::user();

        if (! $user || ! $user->can('reports.pos.charts')) {
            abort(403);
        }

        // V33-CRIT-01 FIX: Use sale_date (business date) instead of created_at
        // and exclude all non-valid statuses (cancelled, void, returned, refunded)
        // V35-MED-06 FIX: Include 'draft' in exclusion list for consistency
        $query = Sale::query()
            ->whereNotIn('status', SaleStatus::nonRevenueStatuses());

        if ($this->dateFrom) {
            $query->whereDate('sale_date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('sale_date', '<=', $this->dateTo);
        }

        if ($this->branchId) {
            $query->where('branch_id', $this->branchId);
        }

        $sales = $query->orderBy('sale_date')->get();

        $totalSales = $sales->count();
        // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
        $totalRevenue = decimal_float($sales->sum('grand_total'));

        // V33-CRIT-01 FIX: Group by sale_date instead of created_at for accurate daily reporting
        $groupedByDay = $sales->groupBy(function (Sale $sale): string {
            return optional($sale->sale_date)->toDateString() ?? '';
        });

        $dayLabels = [];
        $dayValues = [];

        foreach ($groupedByDay as $date => $items) {
            if (! $date) {
                continue;
            }

            $dayLabels[] = $date;
            // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
            $dayValues[] = decimal_float($items->sum('grand_total'));
        }

        $groupedByBranch = $sales->groupBy('branch_id');

        $branchLabels = [];
        $branchValues = [];

        foreach ($groupedByBranch as $branchId => $items) {
            $branchLabels[] = $branchId ? ('#'.$branchId) : __('N/A');
            // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
            $branchValues[] = decimal_float($items->sum('grand_total'));
        }

        $chartSalesByDay = [
            'labels' => $dayLabels,
            'values' => $dayValues,
        ];

        $chartSalesByBranch = [
            'labels' => $branchLabels,
            'values' => $branchValues,
        ];

        $this->dispatch('pos-charts-update', chartData: [
            'salesByDay' => $chartSalesByDay,
            'salesByBranch' => $chartSalesByBranch,
        ]);

        return view('livewire.admin.reports.pos-charts-dashboard', [
            'totalSales' => $totalSales,
            'totalRevenue' => $totalRevenue,
        ]);
    }
}
