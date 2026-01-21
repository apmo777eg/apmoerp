<?php

declare(strict_types=1);

namespace App\Livewire\Helpdesk\Priorities;

use App\Models\TicketPriority;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public function mount(): void
    {
        $this->authorize('helpdesk.manage');
    }

    public function delete(int $id): void
    {
        // V57-HIGH-01 FIX: Explicit authorization inside mutation method
        $this->authorize('helpdesk.manage');
        
        $priority = TicketPriority::findOrFail($id);

        if ($priority->tickets()->exists()) {
            session()->flash('error', __('Cannot delete priority with existing tickets'));

            return;
        }

        $priority->delete();
        session()->flash('success', __('Priority deleted successfully'));
        $this->resetPage();
    }

    public function toggleActive(int $id): void
    {
        // V57-HIGH-01 FIX: Explicit authorization inside mutation method
        $this->authorize('helpdesk.manage');
        
        $priority = TicketPriority::findOrFail($id);
        $priority->is_active = ! $priority->is_active;
        $priority->save();

        session()->flash('success', __('Priority status updated'));
    }

    public function render()
    {
        $priorities = TicketPriority::withCount('tickets')
            ->orderBy('level')
            ->paginate(20);

        return view('livewire.helpdesk.priorities.index', [
            'priorities' => $priorities,
        ]);
    }
}
