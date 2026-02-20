<?php

declare(strict_types=1);

namespace App\Livewire\Rental\Units;

use App\Models\RentalUnit;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use App\Livewire\BaseComponent as Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public ?string $search = '';

    public ?string $status = null;

    public ?int $propertyId = null;

    public ?int $branchId = null;

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user || ! $user->can('rental.units.view')) {
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

    public function updatingPropertyId(): void
    {
        $this->resetPage();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $user = Auth::user();

        if (! $user || ! $user->can('rental.units.view')) {
            abort(403);
        }

        $query = RentalUnit::query()
            ->with('property')
            ->when($this->branchId, function ($q) {
                $q->whereHas('property', function ($propertyQuery) {
                    $propertyQuery->where('branch_id', $this->branchId);
                });
            })
            ->when($this->propertyId, function ($q) {
                $q->where('property_id', $this->propertyId);
            })
            ->when($this->search !== null && $this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';

                $q->where(function ($inner) use ($term) {
                    $inner->where('code', 'like', $term)
                        ->orWhere('type', 'like', $term)
                        ->orWhereHas('property', function ($propertyQuery) use ($term) {
                            $propertyQuery->where('name', 'like', $term)
                                ->orWhere('address', 'like', $term);
                        });
                });
            })
            ->when($this->status, function ($q) {
                $q->where('status', $this->status);
            })
            ->orderBy('code');

        $units = $query->paginate(20);

        // preload properties for filter dropdown
        $properties = \App\Models\Property::query()
            ->when($this->branchId, function ($q) {
                $q->where('branch_id', $this->branchId);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('livewire.rental.units.index', [
            'units' => $units,
            'properties' => $properties,
        ]);
    }
}
