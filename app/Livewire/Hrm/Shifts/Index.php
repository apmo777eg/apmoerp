<?php

declare(strict_types=1);

namespace App\Livewire\Hrm\Shifts;

use App\Models\Shift;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';

    public ?string $status = null;

    public ?int $branchId = null;

    protected array $daysOfWeek = [
        'sunday' => 'Sunday',
        'monday' => 'Monday',
        'tuesday' => 'Tuesday',
        'wednesday' => 'Wednesday',
        'thursday' => 'Thursday',
        'friday' => 'Friday',
        'saturday' => 'Saturday',
    ];

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user || ! $user->can('hrm.view')) {
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

    public function toggleActive(int $id): void
    {
        $this->authorize('hrm.manage');
        $shift = Shift::findOrFail($id);
        $shift->update(['is_active' => ! $shift->is_active]);
    }

    public function delete(int $id): void
    {
        $this->authorize('hrm.manage');
        Shift::findOrFail($id)->delete();
        session()->flash('success', __('Shift deleted successfully'));
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $user = Auth::user();

        if (! $user || ! $user->can('hrm.view')) {
            abort(403);
        }

        $query = Shift::query()
            ->with(['branch'])
            ->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))
            ->when($this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term);
                });
            })
            ->when($this->status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('name');

        $shifts = $query->paginate(20);

        return view('livewire.hrm.shifts.index', [
            'shifts' => $shifts,
            'daysOfWeek' => $this->daysOfWeek,
        ]);
    }
}
