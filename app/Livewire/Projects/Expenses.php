<?php

declare(strict_types=1);

namespace App\Livewire\Projects;

use App\Models\Project;
use App\Models\ProjectExpense;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Expenses extends Component
{
    use AuthorizesRequests, WithPagination;

    public Project $project;

    public ?ProjectExpense $editingExpense = null;

    public bool $showModal = false;

    public ?int $editingExpenseId = null;

    public array $form = [];

    // Form fields
    public string $category = '';

    public float $amount = 0;

    public ?string $expense_date = null;

    public ?string $vendor = null;

    public ?string $description = null;

    public bool $billable = true;

    public ?int $user_id = null;

    public ?int $task_id = null;

    public function mount(int $projectId): void
    {
        $this->authorize('projects.expenses.view');
        $this->project = Project::query()
            ->forUserBranches(auth()->user())
            ->findOrFail($projectId);
        $this->expense_date = now()->format('Y-m-d');
        $this->user_id = auth()->id();
    }

    /**
     * Get array of branch IDs accessible by the current user.
     */
    protected function getUserBranchIds(): array
    {
        $user = auth()->user();
        if (! $user) {
            return [];
        }

        $branchIds = [];

        // Check if branches relation exists
        if (method_exists($user, 'branches')) {
            // Force load the relation if not already loaded
            if (! $user->relationLoaded('branches')) {
                $user->load('branches');
            }
            $branchIds = $user->branches->pluck('id')->toArray();
        }

        if ($user->branch_id && ! in_array($user->branch_id, $branchIds)) {
            $branchIds[] = $user->branch_id;
        }

        return $branchIds;
    }

    public function rules(): array
    {
        $userBranchIds = $this->getUserBranchIds();

        return [
            'category' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'expense_date' => ['required', 'date'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'billable' => ['boolean'],
            // BUG-009 FIX: Scope user_id validation to user's branches
            'user_id' => [
                'required',
                Rule::exists('users', 'id')->whereIn('branch_id', $userBranchIds),
            ],
            'task_id' => [
                'nullable',
                Rule::exists('project_tasks', 'id')->where('project_id', $this->project->id),
            ],
        ];
    }

    public function createExpense(): void
    {
        $this->authorize('projects.expenses.manage');
        $this->resetForm();
        $this->editingExpense = null;
    }

    public function editExpense(int $id): void
    {
        $this->authorize('projects.expenses.manage');
        $this->editingExpense = $this->project->expenses()->findOrFail($id);
        $this->fill($this->editingExpense->only([
            'category', 'amount', 'expense_date', 'vendor', 'description',
            'billable', 'user_id', 'task_id',
        ]));
    }

    public function save(): void
    {
        $this->authorize('projects.expenses.manage');
        $this->validate();

        $data = $this->only([
            'category', 'amount', 'expense_date', 'vendor', 'description',
            'billable', 'user_id', 'task_id',
        ]);

        if ($this->editingExpense) {
            $this->editingExpense->update($data);
        } else {
            $this->project->expenses()->create(array_merge(
                $data,
                ['status' => 'pending']
            ));
        }

        session()->flash('success', __('Expense saved successfully'));
        $this->resetForm();
        $this->editingExpense = null;
    }

    public function approve(int $id): void
    {
        $this->authorize('projects.expenses.approve');
        $expense = $this->project->expenses()->findOrFail($id);
        // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
        $expense->approve(actual_user_id());
        session()->flash('success', __('Expense approved successfully'));
    }

    public function reject(int $id, string $reason): void
    {
        $this->authorize('projects.expenses.approve');
        $expense = $this->project->expenses()->findOrFail($id);
        // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
        $expense->reject(actual_user_id(), $reason);
        session()->flash('success', __('Expense rejected'));
    }

    public function deleteExpense(int $id): void
    {
        $this->authorize('projects.expenses.manage');
        $expense = $this->project->expenses()->findOrFail($id);
        $expense->delete();
        session()->flash('success', __('Expense deleted successfully'));
    }

    public function resetForm(): void
    {
        $this->reset([
            'category', 'amount', 'vendor', 'description', 'billable', 'task_id',
        ]);
        $this->expense_date = now()->format('Y-m-d');
        $this->user_id = auth()->id();
    }

    public function render()
    {
        $expenses = $this->project->expenses()
            ->with(['requestedBy'])
            ->orderBy('expense_date', 'desc')
            ->paginate(15);

        // BUG-009 FIX: Scope users to user's branches
        $userBranchIds = $this->getUserBranchIds();
        $users = User::whereIn('branch_id', $userBranchIds)
            ->orderBy('name')
            ->get();

        // Statistics
        $stats = [
            'total_expenses' => $this->project->expenses()->sum('amount'),
            'approved_expenses' => $this->project->expenses()->approved()->sum('amount'),
            'pending_expenses' => $this->project->expenses()->pending()->sum('amount'),
            'needs_reimbursement' => $this->project->expenses()->needsReimbursement()->sum('amount'),
        ];

        return view('livewire.projects.expenses', [
            'expenses' => $expenses,
            'users' => $users,
            'stats' => $stats,
            'totalExpenses' => $stats['total_expenses'],
        ]);
    }
}
