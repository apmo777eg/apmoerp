<?php

namespace App\Livewire\Rental\Reports;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    public array $filters = [
        'expiring_in_days' => 30,
    ];

    public array $unitsChart = [];

    public array $contractsChart = [];

    public array $unitsSummary = [];

    public array $contractsSummary = [];

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->can('rental.view-reports')) {
            abort(403);
        }

        $this->loadData();
    }

    public function updatedFilters(): void
    {
        $this->loadData();
    }

    protected function loadData(): void
    {
        $this->loadUnitsData();
        $this->loadContractsData();
        $this->notifyExpiringContracts();
    }

    protected function loadUnitsData(): void
    {
        $model = '\\App\\Models\\RentalUnit';

        if (! class_exists($model)) {
            $this->unitsChart = [];
            $this->unitsSummary = [];

            return;
        }

        $builder = $model::query();

        $total = (clone $builder)->count();

        $units = (clone $builder)->get(['status']);

        $statusCounts = $units->groupBy('status')
            ->map->count()
            ->toArray();

        $occupied = $statusCounts['occupied'] ?? 0;
        // Status 'available' is the standard. Handle legacy 'vacant' for backwards compatibility
        $vacant = $statusCounts['available'] ?? $statusCounts['vacant'] ?? ($total - $occupied);

        $occupancyRate = $total > 0 ? decimal_float(bcdiv(bcmul((string) $occupied, '100', 4), (string) $total, 1)) : 0;

        $this->unitsSummary = [
            'total' => $total,
            'occupied' => $occupied,
            'vacant' => max($vacant, 0),
            'occupancy_rate' => $occupancyRate,
        ];

        $this->unitsChart = [
            'labels' => array_keys($statusCounts),
            'data' => array_values($statusCounts),
        ];
    }

    protected function loadContractsData(): void
    {
        $model = '\\App\\Models\\RentalContract';

        if (! class_exists($model)) {
            $this->contractsChart = [];
            $this->contractsSummary = [];

            return;
        }

        $days = (int) ($this->filters['expiring_in_days'] ?? 30);
        $threshold = now()->addDays($days)->toDateString();

        $builder = $model::query();

        $active = (clone $builder)->where('status', 'active')->count();
        $expiringSoon = (clone $builder)
            ->where('status', 'active')
            ->whereDate('end_date', '<=', $threshold)
            ->count();

        $this->contractsSummary = [
            'active' => $active,
            'expiring_soon' => $expiringSoon,
            'window_days' => $days,
        ];

        $contracts = $model::query()
            ->where('status', 'active')
            ->orderBy('end_date')
            ->limit(500)
            ->get(['end_date']);

        $series = $contracts->groupBy(function ($contract) {
            return $contract->end_date instanceof \Carbon\Carbon
                ? $contract->end_date->toDateString()
                : (string) $contract->end_date;
        })
            ->sortKeys()
            ->take(30)
            ->map(function ($group, $day) {
                return [
                    'day' => $day,
                    'total' => $group->count(),
                ];
            })
            ->values()
            ->all();

        $this->contractsChart = [
            'labels' => array_map(fn ($row) => $row['day'], $series),
            'data' => array_map(fn ($row) => $row['total'], $series),
        ];
    }

    protected function notifyExpiringContracts(): void
    {
        $model = '\\App\\Models\\RentalContract';

        if (! class_exists($model)) {
            return;
        }

        $days = (int) ($this->filters['expiring_in_days'] ?? 30);
        $threshold = now()->addDays($days)->toDateString();

        $contracts = $model::query()
            ->where('status', 'active')
            ->whereDate('end_date', '<=', $threshold)
            ->whereNull('expiration_notified_at')
            ->limit(50)
            ->get();

        if ($contracts->isEmpty()) {
            return;
        }

        $userModel = '\\App\\Models\\User';

        if (! class_exists($userModel)) {
            return;
        }

        $recipients = $userModel::where('email', 'admin@ghanem-lvju-egypt.com')->get();

        if ($recipients->isEmpty()) {
            return;
        }

        foreach ($contracts as $contract) {
            foreach ($recipients as $user) {
                $user->notify(new \App\Notifications\Rental\ContractExpiringNotification($contract));
            }

            $contract->forceFill(['expiration_notified_at' => now()])->save();
        }
    }

    public function render()
    {
        return view('livewire.rental.reports.dashboard');
    }
}
