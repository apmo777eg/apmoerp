<?php

declare(strict_types=1);

namespace App\Livewire\Rental\Tenants;

use App\Models\RentalContract;
use App\Models\Tenant;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use App\Livewire\BaseComponent as Component;
use Livewire\WithPagination;

class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function delete(int $id): void
    {
        $this->authorize('rental.tenants.update');

        Tenant::findOrFail($id)->delete();
        Cache::forget('tenants_stats_'.(auth()->user()?->branch_id ?? 'all'));
        session()->flash('success', __('Tenant deleted successfully'));
    }

    public function getStatistics(): array
    {
        $user = auth()->user();
        $cacheKey = 'tenants_stats_'.($user?->branch_id ?? 'all');

        return Cache::remember($cacheKey, 300, function () use ($user) {
            $tenantQuery = Tenant::query();

            if ($user && $user->branch_id) {
                $tenantQuery->where('branch_id', $user->branch_id);
            }

            $activeContracts = RentalContract::query()
                ->when($user && $user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
                ->where('status', 'active')
                ->count();

            return [
                'total_tenants' => $tenantQuery->count(),
                'active_tenants' => Tenant::query()
                    ->when($user && $user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
                    ->where('is_active', true)->count(),
                'active_contracts' => $activeContracts,
                'inactive_tenants' => Tenant::query()
                    ->when($user && $user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
                    ->where('is_active', false)->count(),
            ];
        });
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $user = auth()->user();

        $tenants = Tenant::query()
            ->withCount('contracts')
            ->when($user && $user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
            ->when($this->search, fn ($q) => $q->where(function ($query) {
                $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                    ->orWhere('phone', 'like', "%{$this->search}%");
            }))
            ->when($this->status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);

        $stats = $this->getStatistics();

        return view('livewire.rental.tenants.index', [
            'tenants' => $tenants,
            'stats' => $stats,
        ]);
    }
}
