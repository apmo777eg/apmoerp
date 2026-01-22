<?php

declare(strict_types=1);

namespace App\Livewire\Helpdesk\Tickets;

use App\Livewire\Concerns\AuthorizesWithFriendlyErrors;
use App\Models\Customer;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\TicketSLAPolicy;
use App\Models\User;
use App\Rules\BranchScopedExists;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Form extends Component
{
    use AuthorizesWithFriendlyErrors;

    public ?Ticket $ticket = null;

    public bool $isEditing = false;

    public bool $hasAccess = true;

    // Form fields
    public string $subject = '';

    public string $description = '';

    public string $status = 'new';

    public ?int $priority_id = null;

    public ?int $customer_id = null;

    public ?int $assigned_to = null;

    public ?int $category_id = null;

    public ?int $sla_policy_id = null;

    public ?string $due_date = null;

    public array $tags = [];

    protected function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'status' => ['required', 'string', 'in:new,open,pending,resolved,closed'],
            'priority_id' => ['required', 'exists:ticket_priorities,id'],
            // V57-CRITICAL-03 FIX: Use BranchScopedExists to prevent cross-branch customer references
            'customer_id' => ['nullable', new BranchScopedExists('customers', 'id', null, true)],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'category_id' => ['required', 'exists:ticket_categories,id'],
            'sla_policy_id' => ['nullable', 'exists:ticket_sla_policies,id'],
            'due_date' => ['nullable', 'date'],
            'tags' => ['nullable', 'array'],
        ];
    }

    public function mount(?Ticket $ticket = null): void
    {
        $user = Auth::user();

        if (! $user || ! $user->can('helpdesk.manage')) {
            $this->hasAccess = false;
            session()->flash('error', __('You do not have permission to manage tickets.'));

            return;
        }

        if ($ticket && $ticket->exists) {
            if (! $user->can('update', $ticket)) {
                $this->hasAccess = false;
                session()->flash('error', __('You do not have permission to edit this ticket.'));

                return;
            }

            $this->isEditing = true;
            $this->ticket = $ticket;
            $this->fill([
                'subject' => $ticket->subject,
                'description' => $ticket->description,
                'status' => $ticket->status,
                'priority_id' => $ticket->priority_id,
                'customer_id' => $ticket->customer_id,
                'assigned_to' => $ticket->assigned_to,
                'category_id' => $ticket->category_id,
                'sla_policy_id' => $ticket->sla_policy_id,
                'due_date' => $ticket->due_date?->format('Y-m-d'),
                'tags' => $ticket->tags ?? [],
            ]);
        }
    }

    public function save()
    {
        // V58-HIGH-01 FIX: Re-authorize on mutation to prevent direct method calls
        $user = Auth::user();
        if (! $user) {
            abort(403);
        }

        if ($this->isEditing && $this->ticket) {
            if (! $user->can('update', $this->ticket)) {
                session()->flash('error', __('You do not have permission to update this ticket.'));
                return;
            }
        } else {
            if (! $user->can('tickets.manage')) {
                abort(403);
            }
        }

        $this->validate();

        $data = [
            'subject' => $this->subject,
            'description' => $this->description,
            'status' => $this->status,
            'priority_id' => $this->priority_id,
            'customer_id' => $this->customer_id,
            'assigned_to' => $this->assigned_to,
            'category_id' => $this->category_id,
            'sla_policy_id' => $this->sla_policy_id,
            'due_date' => $this->due_date,
            'tags' => $this->tags,
            'branch_id' => $this->ticket?->branch_id ?? $user->branch_id,
        ];

        if ($this->isEditing) {
            $data['updated_by'] = $user->id;
            $this->ticket->update($data);
            session()->flash('success', __('Ticket updated successfully.'));

            $this->redirectRoute('app.helpdesk.tickets.index', navigate: true);
        } else {
            $data['created_by'] = $user->id;
            $ticket = Ticket::create($data);
            session()->flash('success', __('Ticket created successfully.'));

            $this->redirectRoute('app.helpdesk.tickets.show', ['ticket' => $ticket->id], navigate: true);
        }
    }

    #[Layout('layouts.app')]
    public function render()
    {
        if (! $this->hasAccess) {
            return view('livewire.helpdesk.tickets.form', [
                'categories' => collect(),
                'priorities' => collect(),
                'sla_policies' => collect(),
                'customers' => collect(),
                'agents' => collect(),
                'hasAccess' => false,
            ]);
        }

        $categories = TicketCategory::orderBy('name')->get();
        $priorities = TicketPriority::orderBy('sort_order')->get();
        $sla_policies = TicketSLAPolicy::where('is_active', true)->orderBy('name')->get();
        $customers = Customer::orderBy('name')->limit(100)->get();
        $agents = User::whereHas('roles', function ($q) {
            $q->where('name', 'agent')->orWhere('name', 'admin');
        })->orderBy('name')->get();

        return view('livewire.helpdesk.tickets.form', [
            'categories' => $categories,
            'priorities' => $priorities,
            'sla_policies' => $sla_policies,
            'customers' => $customers,
            'agents' => $agents,
            'hasAccess' => true,
        ]);
    }
}
