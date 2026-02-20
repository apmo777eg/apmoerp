<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Stock;

use App\Models\LowStockAlert;
use App\Services\StockAlertService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use App\Livewire\BaseComponent as Component;
use Livewire\WithPagination;

class LowStockAlerts extends Component
{
    use WithPagination;

    public string $status = 'active';

    public string $search = '';

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->can('inventory.stock.alerts.view')) {
            abort(403);
        }
    }

    public function acknowledgeAlert(int $alertId): void
    {
        $alert = LowStockAlert::findOrFail($alertId);
        $alert->acknowledge(Auth::id());

        $this->dispatch('alert-acknowledged');
        session()->flash('success', __('Alert acknowledged'));
    }

    public function resolveAlert(int $alertId): void
    {
        $alert = LowStockAlert::findOrFail($alertId);
        $alert->resolve(Auth::id());

        $this->dispatch('alert-resolved');
        session()->flash('success', __('Alert resolved'));
    }

    public function refreshAlerts(): void
    {
        $service = app(StockAlertService::class);
        $service->checkAllProducts(Auth::user()?->branch_id);

        session()->flash('success', __('Stock alerts refreshed'));
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $user = Auth::user();
        $branchId = $user?->branch_id;

        $query = LowStockAlert::with(['product', 'warehouse', 'branch'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($this->status !== 'all', fn ($q) => $q->where('status', $this->status))
            ->when($this->search, function ($q) {
                $q->whereHas('product', fn ($p) => $p->where('name', 'like', "%{$this->search}%"));
            })
            ->orderByDesc('created_at');

        $stats = app(StockAlertService::class)->getAlertStats($branchId);

        return view('livewire.admin.stock.low-stock-alerts', [
            'alerts' => $query->paginate(15),
            'stats' => $stats,
        ]);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }
}
