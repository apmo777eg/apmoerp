<?php

declare(strict_types=1);

namespace App\Livewire\Admin\UnitsOfMeasure;

use App\Models\UnitOfMeasure;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    #[Url]
    public string $search = '';

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->can('inventory.units.view')) {
            abort(403);
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $units = UnitOfMeasure::query()
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('name_ar', 'like', "%{$this->search}%")
                ->orWhere('symbol', 'like', "%{$this->search}%"))
            ->with('baseUnit')
            ->withCount('products')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20);

        $baseUnits = UnitOfMeasure::baseUnits()->active()->orderBy('name')->get();

        $unitTypes = [
            'unit' => __('Unit'),
            'weight' => __('Weight'),
            'length' => __('Length'),
            'volume' => __('Volume'),
            'area' => __('Area'),
            'time' => __('Time'),
            'other' => __('Other'),
        ];

        return view('livewire.admin.units-of-measure.index', [
            'units' => $units,
            'baseUnits' => $baseUnits,
            'unitTypes' => $unitTypes,
        ]);
    }

    public function delete(int $id): void
    {
        // V57-HIGH-01 FIX: Explicit authorization inside mutation method
        $this->authorize('inventory.units.delete');
        
        $unit = UnitOfMeasure::find($id);
        if ($unit) {
            if ($unit->products()->count() > 0) {
                session()->flash('error', __('Cannot delete unit with products'));

                return;
            }
            if ($unit->derivedUnits()->count() > 0) {
                session()->flash('error', __('Cannot delete base unit with derived units'));

                return;
            }
            $unit->delete();
            session()->flash('success', __('Unit deleted successfully'));
        }
    }

    public function toggleActive(int $id): void
    {
        $this->authorize('inventory.units.manage');

        $unit = UnitOfMeasure::find($id);
        if ($unit) {
            $unit->update(['is_active' => ! $unit->is_active]);
        }
    }
}
