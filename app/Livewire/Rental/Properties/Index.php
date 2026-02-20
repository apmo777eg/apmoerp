<?php

declare(strict_types=1);

namespace App\Livewire\Rental\Properties;

use App\Models\Property;
use App\Models\RentalUnit;
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
        $this->authorize('rental.properties.update');

        Property::findOrFail($id)->delete();
        Cache::forget('properties_stats_'.(auth()->user()?->branch_id ?? 'all'));
        session()->flash('success', __('Property deleted successfully'));
    }

    public function getStatistics(): array
    {
        $user = auth()->user();
        $cacheKey = 'properties_stats_'.($user?->branch_id ?? 'all');

        return Cache::remember($cacheKey, 300, function () use ($user) {
            $propertyQuery = Property::query();

            if ($user && $user->branch_id) {
                $propertyQuery->where('branch_id', $user->branch_id);
            }

            $propertyIds = Property::query()
                ->when($user && $user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
                ->pluck('id');

            $totalUnits = RentalUnit::whereIn('property_id', $propertyIds)->count();
            $availableUnits = RentalUnit::whereIn('property_id', $propertyIds)->where('status', 'available')->count();
            $occupiedUnits = RentalUnit::whereIn('property_id', $propertyIds)->where('status', 'occupied')->count();

            return [
                'total_properties' => $propertyQuery->count(),
                'total_units' => $totalUnits,
                'available_units' => $availableUnits,
                'occupied_units' => $occupiedUnits,
            ];
        });
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $user = auth()->user();

        $properties = Property::query()
            ->withCount('units')
            ->when($user && $user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
            ->when($this->search, fn ($q) => $q->where(function ($query) {
                $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('address', 'like', "%{$this->search}%");
            }))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);

        $stats = $this->getStatistics();

        return view('livewire.rental.properties.index', [
            'properties' => $properties,
            'stats' => $stats,
        ]);
    }
}
