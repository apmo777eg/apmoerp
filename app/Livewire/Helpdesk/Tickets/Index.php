<?php

declare(strict_types=1);

namespace App\Livewire\Helpdesk\Tickets;

use App\Livewire\Concerns\AuthorizesWithFriendlyErrors;
use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use App\Livewire\BaseComponent as Component;
use Livewire\WithPagination;

class Index extends Component
{
    use AuthorizesWithFriendlyErrors;
    use WithPagination;

    public string $search = '';

    public ?string $status = null;

    public ?int $priorityId = null;

    public ?int $branchId = null;

    public bool $hasAccess = true;

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user || ! $user->can('helpdesk.view')) {
            $this->hasAccess = false;
            session()->flash('error', __('You do not have permission to view helpdesk tickets.'));

            return;
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

    public function updatingPriority(): void
    {
        $this->resetPage();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $user = Auth::user();

        if (! $this->hasAccess || ! $user || ! $user->can('helpdesk.view')) {
            return view('livewire.helpdesk.tickets.index', [
                'tickets' => collect(),
                'stats' => ['new' => 0, 'open' => 0, 'pending' => 0, 'overdue' => 0],
                'hasAccess' => false,
            ]);
        }

        $query = Ticket::query()
            ->with(['customer', 'assignedAgent', 'category', 'branch'])
            ->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))
            ->when($this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('ticket_number', 'like', $term)
                        ->orWhere('subject', 'like', $term)
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', $term));
                });
            })
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->priorityId, fn ($q) => $q->where('priority_id', $this->priorityId))
            ->orderByDesc('created_at');

        $tickets = $query->paginate(20);

        // Get ticket statistics
        $stats = [
            'new' => Ticket::new()->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))->count(),
            'open' => Ticket::open()->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))->count(),
            'pending' => Ticket::pending()->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))->count(),
            'overdue' => Ticket::overdue()->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))->count(),
        ];

        return view('livewire.helpdesk.tickets.index', [
            'tickets' => $tickets,
            'stats' => $stats,
            'hasAccess' => true,
        ]);
    }
}
