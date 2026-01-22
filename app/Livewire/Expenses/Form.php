<?php

declare(strict_types=1);

namespace App\Livewire\Expenses;

use App\Http\Requests\Traits\HasMultilingualValidation;
use App\Livewire\Concerns\HandlesErrors;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class Form extends Component
{
    use AuthorizesRequests;
    use HandlesErrors;
    use HasMultilingualValidation;
    use WithFileUploads;

    public ?Expense $expense = null;

    public bool $editMode = false;

    public string $category_id = '';

    public string $reference_number = '';

    public string $expense_date = '';

    public float $amount = 0;

    public string $payment_method = 'cash';

    public string $description = '';

    // Changed from direct file upload to path-based selection
    public ?string $attachment = null;

    public bool $is_recurring = false;

    public string $recurrence_interval = '';

    protected function rules(): array
    {
        $branchId = auth()->user()?->branch_id;

        return [
            // V58-CRITICAL-02 FIX: Use BranchScopedExists for branch-aware validation
            'category_id' => ['nullable', new \App\Rules\BranchScopedExists('expense_categories', 'id', $branchId, allowNull: true)],
            'reference_number' => 'nullable|string|max:100',
            'expense_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'attachment' => 'nullable|string|max:500',
            'is_recurring' => 'boolean',
            'recurrence_interval' => 'nullable|string|max:50',
        ];
    }

    public function mount(?Expense $expense = null): void
    {
        $this->authorize('expenses.manage');

        $user = auth()->user();
        $isSuperAdmin = $user?->hasAnyRole(['Super Admin', 'super-admin']);

        $this->expense_date = now()->format('Y-m-d');

        if ($expense && $expense->exists) {
            if ($user?->branch_id && $expense->branch_id !== $user->branch_id && ! $isSuperAdmin) {
                abort(403);
            }
            $this->expense = $expense;
            $this->editMode = true;
            $data = $expense->toArray();
            unset($data['category_id']);
            $this->fill($data);
            $this->expense_date = $expense->expense_date->format('Y-m-d');
            $this->category_id = (string) ($expense->category_id ?? '');
            $this->reference_number = $expense->reference_number ?? '';
            $this->payment_method = $expense->payment_method ?? '';
            $this->description = $expense->description ?? '';
            $this->recurrence_interval = $expense->recurrence_interval ?? '';
            $this->attachment = $expense->attachment ?? null;
        }
    }

    #[On('file-uploaded')]
    public function handleFileUploaded(string $fieldId, string $path, array $fileInfo): void
    {
        if ($fieldId === 'expense-attachment') {
            $this->attachment = $path;
        }
    }

    #[On('file-cleared')]
    public function handleFileCleared(string $fieldId): void
    {
        if ($fieldId === 'expense-attachment') {
            $this->attachment = null;
        }
    }

    public function save(): mixed
    {
        // V58-HIGH-01 FIX: Re-authorize on mutation to prevent direct method calls
        $this->authorize('expenses.manage');

        $validated = $this->validate();
        $user = auth()->user();
        $branchId = $this->expense?->branch_id ?? $user?->branch_id ?? $user?->branches()->first()?->id;
        $isSuperAdmin = $user?->hasAnyRole(['Super Admin', 'super-admin']);

        if (! $branchId && ! $isSuperAdmin) {
            abort(403);
        }

        if ($this->expense && $branchId && $this->expense->branch_id !== $branchId && ! $isSuperAdmin) {
            abort(403);
        }

        $validated['branch_id'] = $branchId;
        $validated['reference_number'] = $validated['reference_number'] ?? 'EXP-'.now()->format('YmdHis').'-'.uniqid();
        // V23-MED-05 FIX: Don't overwrite created_by on updates
        if (! $this->editMode) {
            // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
            $validated['created_by'] = actual_user_id();
        }

        return $this->handleOperation(
            operation: function () use ($validated) {
                if ($this->editMode) {
                    $this->expense->update($validated);
                } else {
                    Expense::create($validated);
                }
            },
            successMessage: $this->editMode ? __('Expense updated successfully') : __('Expense created successfully'),
            redirectRoute: 'app.expenses.index'
        );
    }

    public function render()
    {
        $categories = ExpenseCategory::active()->get();

        return view('livewire.expenses.form', [
            'categories' => $categories,
        ])->layout('layouts.app', ['title' => $this->editMode ? __('Edit Expense') : __('Add Expense')]);
    }
}
