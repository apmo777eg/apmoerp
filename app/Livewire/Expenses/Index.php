<?php

declare(strict_types=1);

namespace App\Livewire\Expenses;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Traits\HasExport;
use App\Traits\HasSortableColumns;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Url;
use App\Livewire\BaseComponent as Component;
use Livewire\WithPagination;

class Index extends Component
{
    use AuthorizesRequests;
    use HasExport;
    use HasSortableColumns;
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $categoryId = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    /**
     * Default sort field - overrides trait default to match expense_date.
     */
    public string $sortField = 'expense_date';

    public string $sortDirection = 'desc';

    public function mount(): void
    {
        $this->authorize('expenses.view');
        $this->initializeExport('expenses');
    }

    /**
     * Define allowed sort columns to prevent SQL injection.
     */
    protected function allowedSortColumns(): array
    {
        return ['id', 'expense_date', 'amount', 'description', 'reference_number', 'created_at', 'updated_at'];
    }

    /**
     * Get the default sort column for expenses.
     */
    protected function defaultSortColumn(): string
    {
        return 'expense_date';
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        $this->authorize('expenses.manage');

        $expense = Expense::findOrFail($id);
        $this->ensureBranchAccess($expense);

        $expense->delete();
        session()->flash('success', __('Expense deleted successfully'));
    }

    public function export()
    {
        $sortField = $this->getSortField();
        $sortDirection = $this->getSortDirection();
        $branchId = $this->userBranchId();

        $data = Expense::query()
            ->leftJoin('expense_categories', 'expenses.category_id', '=', 'expense_categories.id')
            ->leftJoin('branches', 'expenses.branch_id', '=', 'branches.id')
            ->when($branchId, fn ($q) => $q->where('expenses.branch_id', $branchId))
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('expenses.description', 'like', "%{$this->search}%")
                        ->orWhere('expenses.reference_number', 'like', "%{$this->search}%");
                });
            })
            ->when($this->categoryId, fn ($q) => $q->where('expenses.category_id', $this->categoryId))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('expenses.expense_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('expenses.expense_date', '<=', $this->dateTo))
            ->orderBy('expenses.'.$sortField, $sortDirection)
            ->select([
                'expenses.id',
                'expenses.expense_date',
                'expense_categories.name as category_name',
                'expenses.description',
                'expenses.amount',
                'expenses.reference_number',
                'branches.name as branch_name',
                'expenses.created_at',
            ])
            ->get();

        return $this->performExport('expenses', $data, __('Expenses Export'));
    }

    public function render()
    {
        $branchId = $this->userBranchId();

        $expenses = Expense::query()
            ->with(['category', 'branch', 'creator'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($this->search, fn ($q) => $q->where(function ($query) {
                $query->where('description', 'like', "%{$this->search}%")
                    ->orWhere('reference_number', 'like', "%{$this->search}%");
            }))
            ->when($this->categoryId, fn ($q) => $q->where('category_id', $this->categoryId))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('expense_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('expense_date', '<=', $this->dateTo))
            ->orderBy($this->getSortField(), $this->getSortDirection())
            ->paginate(15);

        $categories = ExpenseCategory::active()->get();

        return view('livewire.expenses.index', [
            'expenses' => $expenses,
            'categories' => $categories,
        ])->layout('layouts.app', ['title' => __('Expenses')]);
    }

    private function userBranchId(): ?int
    {
        $user = auth()->user();

        return $user?->branch_id;
    }

    private function ensureBranchAccess(Expense $expense): void
    {
        $user = auth()->user();
        $isSuperAdmin = $user?->hasAnyRole(['Super Admin', 'super-admin']);

        if ($user?->branch_id && $expense->branch_id && $expense->branch_id !== $user->branch_id && ! $isSuperAdmin) {
            abort(403, __('You cannot manage expenses from another branch.'));
        }
    }
}
