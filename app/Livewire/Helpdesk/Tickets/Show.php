<?php

declare(strict_types=1);

namespace App\Livewire\Helpdesk\Tickets;

use App\Livewire\Concerns\AuthorizesWithFriendlyErrors;
use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use App\Livewire\BaseComponent as Component;
class Show extends Component
{
    use AuthorizesWithFriendlyErrors;

    public Ticket $ticket;

    public string $replyMessage = '';

    public bool $isInternal = false;

    public bool $hasAccess = true;

    protected function rules(): array
    {
        return [
            'replyMessage' => ['required', 'string', 'min:3'],
            'isInternal' => ['boolean'],
        ];
    }

    public function mount(Ticket $ticket): void
    {
        $user = Auth::user();

        if (! $user || ! $user->can('helpdesk.view')) {
            $this->hasAccess = false;
            session()->flash('error', __('You do not have permission to view this ticket.'));

            return;
        }

        if (! $this->checkBranchAccess($user, $ticket)) {
            $this->hasAccess = false;

            return;
        }

        $this->ticket = $ticket->load([
            'customer',
            'assignedAgent',
            'category',
            'priority',
            'slaPolicy',
            'branch',
            'replies.user',
            'creator',
        ]);
    }

    public function addReply(): void
    {
        $this->validate();

        $user = Auth::user();

        if (! $this->authorizeReply($user)) {
            return;
        }

        $this->ticket->addReply($this->replyMessage, $user->id, $this->isInternal);

        $this->replyMessage = '';
        $this->isInternal = false;

        $this->ticket->refresh();

        session()->flash('success', __('Reply added successfully.'));
    }

    public function assignToMe(): void
    {
        $user = Auth::user();

        if (! $user->can('helpdesk.manage')) {
            session()->flash('error', __('You do not have permission to assign tickets.'));

            return;
        }

        $this->ticket->assign($user->id);
        $this->ticket->refresh();

        session()->flash('success', __('Ticket assigned to you.'));
    }

    public function resolve(): void
    {
        if (! Auth::user()->can('helpdesk.manage')) {
            session()->flash('error', __('You do not have permission to resolve tickets.'));

            return;
        }

        $this->ticket->resolve();
        $this->ticket->refresh();

        session()->flash('success', __('Ticket marked as resolved.'));
    }

    public function close(): void
    {
        if (! Auth::user()->can('helpdesk.manage')) {
            session()->flash('error', __('You do not have permission to close tickets.'));

            return;
        }

        if (! $this->ticket->canBeClosed()) {
            session()->flash('error', __('Ticket must be resolved before closing.'));

            return;
        }

        $this->ticket->close();
        $this->ticket->refresh();

        session()->flash('success', __('Ticket closed.'));
    }

    public function reopen(): void
    {
        if (! Auth::user()->can('helpdesk.manage')) {
            session()->flash('error', __('You do not have permission to reopen tickets.'));

            return;
        }

        if (! $this->ticket->canBeReopened()) {
            session()->flash('error', __('Ticket cannot be reopened.'));

            return;
        }

        $this->ticket->reopen();
        $this->ticket->refresh();

        session()->flash('success', __('Ticket reopened.'));
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.helpdesk.tickets.show', [
            'hasAccess' => $this->hasAccess,
        ]);
    }

    protected function authorizeReply($user): bool
    {
        if (! $user) {
            session()->flash('error', __('You must be logged in to reply.'));

            return false;
        }

        if (! $this->checkBranchAccess($user, $this->ticket)) {
            return false;
        }

        $isAssignedAgent = (int) $this->ticket->assigned_to === (int) $user->id;

        if (! $user->can('helpdesk.manage') && ! $isAssignedAgent) {
            session()->flash('error', __('You do not have permission to reply to this ticket.'));

            return false;
        }

        $canAddInternal = $user->can('helpdesk.manage') || $isAssignedAgent;

        if ($this->isInternal && ! $canAddInternal) {
            session()->flash('error', __('You do not have permission to add internal notes.'));

            return false;
        }

        return true;
    }

    protected function checkBranchAccess($user, Ticket $ticket): bool
    {
        if ($user->can('helpdesk.manage')) {
            return true;
        }

        if ((int) $ticket->branch_id !== (int) $user->branch_id) {
            session()->flash('error', __('You cannot access tickets from other branches.'));

            return false;
        }

        return true;
    }
}
