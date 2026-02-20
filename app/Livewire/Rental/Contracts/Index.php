<?php

declare(strict_types=1);

namespace App\Livewire\Rental\Contracts;

use App\Models\RentalContract;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use App\Livewire\BaseComponent as Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public ?string $search = '';

    public ?string $status = null;

    public ?string $fromDate = null;

    public ?string $toDate = null;

    public ?int $branchId = null;

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user || ! $user->can('rental.contracts.view')) {
            abort(403);
        }

        $this->branchId = $user->branch_id;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingFromDate(): void
    {
        $this->resetPage();
    }

    public function updatingToDate(): void
    {
        $this->resetPage();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $user = Auth::user();

        if (! $user || ! $user->can('rental.contracts.view')) {
            abort(403);
        }

        $query = RentalContract::query()
            ->with(['tenant', 'unit.property'])
            ->when($this->branchId, function ($q) {
                $q->where('branch_id', $this->branchId);
            })
            ->when($this->search !== null && $this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';

                $q->where(function ($inner) use ($term) {
                    $inner->whereHas('tenant', function ($tenantQuery) use ($term) {
                        $tenantQuery->where('name', 'like', $term)
                            ->orWhere('email', 'like', $term)
                            ->orWhere('phone', 'like', $term);
                    })->orWhereHas('unit', function ($unitQuery) use ($term) {
                        $unitQuery->where('code', 'like', $term);
                    });
                });
            })
            ->when($this->status, function ($q) {
                $q->where('status', $this->status);
            })
            ->when($this->fromDate, function ($q) {
                $q->whereDate('start_date', '>=', $this->fromDate);
            })
            ->when($this->toDate, function ($q) {
                $q->whereDate('end_date', '<=', $this->toDate);
            })
            ->orderByDesc('start_date')
            ->orderByDesc('id');

        $contracts = $query->paginate(20);

        return view('livewire.rental.contracts.index', [
            'contracts' => $contracts,
        ]);
    }
}
