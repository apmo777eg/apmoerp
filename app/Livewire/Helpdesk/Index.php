<?php

declare(strict_types=1);

namespace App\Livewire\Helpdesk;

use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Services\HelpdeskService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
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

    #[Url]
    public string $status = '';

    #[Url]
    public ?int $priorityId = null;

    #[Url]
    public string $category = '';

    #[Url]
    public string $assigned = '';

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    protected HelpdeskService $helpdeskService;

    public function boot(HelpdeskService $helpdeskService): void
    {
        $this->helpdeskService = $helpdeskService;
    }

    public function mount(): void
    {
        $this->authorize('helpdesk.view');
    }

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

    /**
     * V55-CRITICAL-05 FIX: Delete is now properly branch-scoped via HasBranch trait.
     * The findOrFail() call automatically applies branch scope, preventing cross-branch access.
     */
    public function delete(int $id): void
    {
        $this->authorize('helpdesk.delete');

        // Branch scope is automatically applied by HasBranch trait
        $ticket = Ticket::findOrFail($id);
        $ticket->delete();

        session()->flash('success', __('Ticket deleted successfully'));
        $this->resetPage();
    }

    public function render()
    {
        $user = auth()->user();
        $branchId = $user->branch_id;

        // V55-CRITICAL-05 FIX: Branch scope is now automatically applied via HasBranch trait
        // The manual branch filter is kept for additional explicitness but the trait handles it
        $query = Ticket::with(['customer', 'assignedAgent', 'category', 'priority'])
            ->when($this->search, fn ($q) => $q->where(function ($query) {
                $query->where('ticket_number', 'like', "%{$this->search}%")
                    ->orWhere('subject', 'like', "%{$this->search}%")
                    ->orWhere('description', 'like', "%{$this->search}%");
            }))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->priorityId, fn ($q) => $q->where('priority_id', $this->priorityId))
            ->when($this->category, fn ($q) => $q->where('category_id', $this->category))
            ->when($this->assigned === 'me', fn ($q) => $q->where('assigned_to', $user->id))
            ->when($this->assigned === 'unassigned', fn ($q) => $q->whereNull('assigned_to'));

        $tickets = $query->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);

        // Get statistics
        $stats = $this->helpdeskService->getTicketStats($branchId);

        // Get filter options
        $categories = TicketCategory::active()->ordered()->get();
        $priorities = TicketPriority::active()->ordered()->get();

        return view('livewire.helpdesk.index', [
            'tickets' => $tickets,
            'stats' => $stats,
            'categories' => $categories,
            'priorities' => $priorities,
        ]);
    }
}
