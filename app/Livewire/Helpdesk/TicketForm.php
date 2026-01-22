<?php

declare(strict_types=1);

namespace App\Livewire\Helpdesk;

use App\Models\Customer;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\TicketSLAPolicy;
use App\Models\User;
use App\Rules\BranchScopedExists;
use App\Services\HelpdeskService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportRedirects\Redirector;

#[Layout('layouts.app')]
class TicketForm extends Component
{
    use AuthorizesRequests;

    public ?Ticket $ticket = null;

    public bool $isEdit = false;

    public string $subject = '';

    public string $description = '';

    public ?int $customer_id = null;

    public ?int $category_id = null;

    public ?int $priority_id = null;

    public ?int $assigned_to = null;

    public ?int $sla_policy_id = null;

    public string $due_date = '';

    public string $status = 'new';

    public array $tags = [];

    public string $tagInput = '';

    protected HelpdeskService $helpdeskService;

    public function boot(HelpdeskService $helpdeskService): void
    {
        $this->helpdeskService = $helpdeskService;
    }

    public function mount(?Ticket $ticket = null): void
    {
        $user = auth()->user();
        $isSuperAdmin = $user?->hasAnyRole(['Super Admin', 'super-admin']);

        if ($ticket && $ticket->exists) {
            $this->authorize('helpdesk.edit');
            if ($user && $user->branch_id && $ticket->branch_id !== $user->branch_id && ! $isSuperAdmin) {
                session()->flash('error', __('You cannot edit tickets from other branches.'));
                $this->redirectRoute('app.helpdesk.tickets.index', navigate: true);

                return;
            }
            $this->isEdit = true;
            $this->ticket = $ticket;
            $this->fill($ticket->only([
                'subject',
                'description',
                'customer_id',
                'category_id',
                'priority_id',
                'assigned_to',
                'sla_policy_id',
                'status',
            ]));
            $this->tags = $ticket->tags ?? [];
            $this->due_date = $ticket->due_date ? $ticket->due_date->format('Y-m-d\TH:i') : '';
        } else {
            $this->authorize('helpdesk.create');
        }
    }

    public function addTag(): void
    {
        if (empty(trim($this->tagInput))) {
            return;
        }

        $tag = trim($this->tagInput);
        if (! in_array($tag, $this->tags)) {
            $this->tags[] = $tag;
        }

        $this->tagInput = '';
    }

    public function removeTag(string $tag): void
    {
        $this->tags = array_values(array_filter($this->tags, fn ($t) => $t !== $tag));
    }

    public function save(): RedirectResponse|Redirector
    {
        $data = [
            'subject' => $this->subject,
            'description' => $this->description,
            'customer_id' => $this->customer_id,
            'category_id' => $this->category_id,
            'priority_id' => $this->priority_id,
            'assigned_to' => $this->assigned_to,
            'sla_policy_id' => $this->sla_policy_id,
            'tags' => array_values(array_filter(array_unique(array_map('trim', $this->tags)))),
            'status' => $this->status ?: 'new',
        ];

        $user = auth()->user();
        $branchId = $this->ticket?->branch_id ?? $user?->branch_id;
        $isSuperAdmin = $user?->hasAnyRole(['Super Admin', 'super-admin']);

        if (! $branchId && ! $isSuperAdmin) {
            session()->flash('error', __('You must be assigned to a branch to manage tickets.'));

            return $this->redirectRoute('app.helpdesk.tickets.index', navigate: true);
        }

        if ($this->ticket && $branchId && $this->ticket->branch_id !== $branchId && ! $isSuperAdmin) {
            session()->flash('error', __('You cannot modify tickets from other branches.'));

            return $this->redirectRoute('app.helpdesk.tickets.index', navigate: true);
        }

        $data['branch_id'] = $branchId;

        if (! empty($this->due_date)) {
            $data['due_date'] = $this->due_date;
        }

        if ($this->isEdit) {
            $this->validate([
                'subject' => 'required|string|max:255',
                'description' => 'required|string',
                'status' => 'required|in:new,open,pending,resolved,closed',
                'category_id' => 'required|exists:ticket_categories,id',
                'priority_id' => 'required|exists:ticket_priorities,id',
                // V57-CRITICAL-03 FIX: Use BranchScopedExists to prevent cross-branch customer references
                'customer_id' => ['nullable', new BranchScopedExists('customers', 'id', null, true)],
                'assigned_to' => 'nullable|exists:users,id',
                'sla_policy_id' => 'nullable|exists:ticket_sla_policies,id',
                'tags' => 'array',
                'tags.*' => 'string|max:50',
                'due_date' => 'nullable|date_format:Y-m-d\TH:i',
            ]);

            $data['status'] = $this->status;

            $this->ticket = $this->helpdeskService->updateTicket($this->ticket, $data);

            session()->flash('success', __('Ticket updated successfully'));
        } else {
            $this->validate([
                'subject' => 'required|string|max:255',
                'description' => 'required|string',
                'category_id' => 'required|exists:ticket_categories,id',
                'priority_id' => 'required|exists:ticket_priorities,id',
                // V57-CRITICAL-03 FIX: Use BranchScopedExists to prevent cross-branch customer references
                'customer_id' => ['nullable', new BranchScopedExists('customers', 'id', null, true)],
                'assigned_to' => 'nullable|exists:users,id',
                'sla_policy_id' => 'nullable|exists:ticket_sla_policies,id',
                'tags' => 'array',
                'tags.*' => 'string|max:50',
                'due_date' => 'nullable|date_format:Y-m-d\TH:i',
                'status' => 'required|in:new,open,pending,resolved,closed',
            ]);

            $this->ticket = $this->helpdeskService->createTicket($data);

            session()->flash('success', __('Ticket created successfully'));
        }

        $this->redirectRoute('app.helpdesk.tickets.show', ['ticket' => $this->ticket->id], navigate: true);
    }

    public function render()
    {
        $user = auth()->user();
        $branchId = $user?->branch_id;
        $isSuperAdmin = $user?->hasAnyRole(['Super Admin', 'super-admin']);

        $customers = Customer::query()
            ->when(! $isSuperAdmin && $branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderBy('name')
            ->get();
        $categories = TicketCategory::active()->ordered()->get();
        $priorities = TicketPriority::active()->ordered()->get();
        $slaPolicies = TicketSLAPolicy::active()->get();
        $agents = User::whereHas('roles', function ($query) {
            $query->where('name', 'like', '%agent%')
                ->orWhere('name', 'like', '%support%')
                ->orWhere('name', 'Super Admin')
                ->orWhere('name', 'super-admin');
        })
            ->when(! $isSuperAdmin && $branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->where('is_active', true)
            ->get();

        return view('livewire.helpdesk.ticket-form', [
            'customers' => $customers,
            'categories' => $categories,
            'priorities' => $priorities,
            'slaPolicies' => $slaPolicies,
            'agents' => $agents,
        ]);
    }
}
