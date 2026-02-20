<?php

declare(strict_types=1);

namespace App\Livewire\Helpdesk;

use App\Models\Ticket;
use App\Models\User;
use App\Services\HelpdeskService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use App\Livewire\BaseComponent as Component;
#[Layout('layouts.app')]
class TicketDetail extends Component
{
    use AuthorizesRequests;

    public Ticket $ticket;

    public string $replyMessage = '';

    public bool $isInternal = false;

    public ?int $assignToUser = null;

    public bool $hasAccess = true;

    protected HelpdeskService $helpdeskService;

    public function boot(HelpdeskService $helpdeskService): void
    {
        $this->helpdeskService = $helpdeskService;
    }

    public function mount(Ticket $ticket): void
    {
        $this->authorize('helpdesk.view');
        if (! $this->ensureSameBranch(auth()->user(), $ticket)) {
            $this->hasAccess = false;

            return;
        }
        $this->ticket = $ticket->load(['customer', 'assignedAgent', 'category', 'priority', 'slaPolicy', 'replies.user']);
        $this->assignToUser = $ticket->assigned_to;
    }

    public function addReply(): void
    {
        $this->authorize('helpdesk.reply');

        $this->validate([
            'replyMessage' => 'required|string|min:1',
        ]);

        $this->helpdeskService->addReply($this->ticket, [
            'message' => $this->replyMessage,
            'is_internal' => $this->isInternal,
        ]);

        $this->replyMessage = '';
        $this->isInternal = false;

        session()->flash('success', __('Reply added successfully'));
        $this->ticket->refresh();
    }

    public function assignTicket(): void
    {
        $this->authorize('helpdesk.assign');
        if (! $this->ensureSameBranch(auth()->user(), $this->ticket)) {
            return;
        }

        $branchId = $this->ticket->branch_id;
        $user = auth()->user();
        $isSuperAdmin = $user?->hasAnyRole(['Super Admin', 'super-admin']);

        $this->validate([
            'assignToUser' => [
                'required',
                Rule::exists('users', 'id')->where(function ($query) use ($branchId, $user, $isSuperAdmin) {
                    if (! $isSuperAdmin && $branchId) {
                        $query->where('branch_id', $branchId);
                    }

                    if (! $branchId && $user?->branch_id && ! $isSuperAdmin) {
                        $query->where('branch_id', $user->branch_id);
                    }
                }),
            ],
        ]);

        $this->helpdeskService->assignTicket($this->ticket, $this->assignToUser);

        session()->flash('success', __('Ticket assigned successfully'));
        $this->ticket->refresh();
    }

    public function closeTicket(): void
    {
        $this->authorize('helpdesk.close');

        if (! $this->ticket->canBeClosed()) {
            session()->flash('error', __('Ticket must be resolved before closing'));

            return;
        }

        $this->helpdeskService->closeTicket($this->ticket);

        session()->flash('success', __('Ticket closed successfully'));
        $this->ticket->refresh();
    }

    public function reopenTicket(): void
    {
        $this->authorize('helpdesk.edit');

        if (! $this->ticket->canBeReopened()) {
            session()->flash('error', __('Ticket cannot be reopened'));

            return;
        }

        $this->helpdeskService->reopenTicket($this->ticket);

        session()->flash('success', __('Ticket reopened successfully'));
        $this->ticket->refresh();
    }

    public function resolveTicket(): void
    {
        $this->authorize('helpdesk.edit');

        $this->ticket->resolve();

        session()->flash('success', __('Ticket resolved successfully'));
        $this->ticket->refresh();
    }

    public function render()
    {
        // Get available agents for assignment
        $user = auth()->user();
        $branchId = $this->ticket->branch_id ?? $user?->branch_id;

        $agents = User::whereHas('roles', function ($query) {
            $query->where('name', 'like', '%agent%')
                ->orWhere('name', 'like', '%support%')
                ->orWhere('name', 'Super Admin')
                ->orWhere('name', 'super-admin');
        })
            ->when(! $user?->hasAnyRole(['Super Admin', 'super-admin']) && $branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->get();

        // Calculate SLA compliance
        $slaCompliance = $this->helpdeskService->calculateSLA($this->ticket);

        return view('livewire.helpdesk.ticket-detail', [
            'agents' => $agents,
            'slaCompliance' => $slaCompliance,
        ]);
    }

    private function ensureSameBranch(?User $user, Ticket $ticket): bool
    {
        if (! $user) {
            session()->flash('error', __('You must be logged in to access this ticket.'));

            return false;
        }

        if (
            $user->branch_id
            && $ticket->branch_id
            && $user->branch_id !== $ticket->branch_id
            && ! $user->hasAnyRole(['Super Admin', 'super-admin'])
        ) {
            session()->flash('error', __('You cannot access tickets from other branches.'));

            return false;
        }

        return true;
    }
}
