<?php

declare(strict_types=1);

namespace App\Livewire\Helpdesk;

use App\Models\Ticket;
use App\Models\TicketPriority;
use App\Services\HelpdeskService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use App\Livewire\BaseComponent as Component;
#[Layout('layouts.app')]
class Dashboard extends Component
{
    use AuthorizesRequests;

    protected HelpdeskService $helpdeskService;

    public function boot(HelpdeskService $helpdeskService): void
    {
        $this->helpdeskService = $helpdeskService;
    }

    public function mount(): void
    {
        $this->authorize('helpdesk.view');
    }

    public function render()
    {
        $user = auth()->user();
        $branchId = $user->branch_id;

        // Get overall statistics
        $stats = $this->helpdeskService->getTicketStats($branchId);

        // Get my tickets statistics
        $myStats = $this->helpdeskService->getTicketStats($branchId, $user->id);

        // Get recent tickets
        $recentTickets = Ticket::with(['customer', 'assignedAgent', 'category', 'priority'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->latest()
            ->limit(10)
            ->get();

        // Get overdue tickets
        $overdueTickets = Ticket::with(['customer', 'assignedAgent', 'category', 'priority'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->overdue()
            ->latest()
            ->limit(10)
            ->get();

        // Get unassigned tickets
        $unassignedTickets = Ticket::with(['customer', 'category', 'priority'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->unassigned()
            ->latest()
            ->limit(10)
            ->get();

        // Get tickets by status (for chart)
        $ticketsByStatus = [
            'new' => $stats['new'] ?? 0,
            'open' => $stats['open'] ?? 0,
            'pending' => $stats['pending'] ?? 0,
            'resolved' => $stats['resolved'] ?? 0,
            'closed' => $stats['closed'] ?? 0,
        ];

        // Get tickets by priority
        // SECURITY: The DB::raw('count(*) as count') uses a hardcoded expression.
        // No user input is interpolated into the SQL.
        $ticketsByPriority = Ticket::select('priority_id', DB::raw('count(*) as count'))
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereNotIn('status', ['closed'])
            ->groupBy('priority_id')
            ->pluck('count', 'priority_id')
            ->toArray();

        $priorityNames = TicketPriority::whereIn('id', array_keys($ticketsByPriority))
            ->pluck('name', 'id');

        $ticketsByPriority = collect($ticketsByPriority)
            ->mapWithKeys(fn ($count, $priorityId) => [
                $priorityNames[$priorityId] ?? __('Unknown') => $count,
            ])
            ->toArray();

        return view('livewire.helpdesk.dashboard', [
            'stats' => $stats,
            'myStats' => $myStats,
            'recentTickets' => $recentTickets,
            'overdueTickets' => $overdueTickets,
            'unassignedTickets' => $unassignedTickets,
            'ticketsByStatus' => $ticketsByStatus,
            'ticketsByPriority' => $ticketsByPriority,
        ]);
    }
}
