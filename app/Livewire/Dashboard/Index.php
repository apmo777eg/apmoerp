<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Livewire\Concerns\LoadsDashboardData;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Dashboard Index Component
 *
 * Main dashboard view with statistics, charts, and recent data.
 * Uses shared LoadsDashboardData trait for data loading logic.
 */
#[Layout('layouts.app')]
class Index extends Component
{
    use LoadsDashboardData;

    public array $stats = [];

    public array $salesChartData = [];

    public array $inventoryChartData = [];

    public array $paymentMethodsData = [];

    public array $lowStockProducts = [];

    public array $recentSales = [];

    public array $trendIndicators = [];

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->can('dashboard.view')) {
            abort(403);
        }

        $this->initializeDashboardContext();
        $this->loadAllDashboardData();
    }

    public function refreshData(): void
    {
        $this->refreshDashboardData();
    }

    public function render()
    {
        return view('livewire.dashboard.index');
    }
}
