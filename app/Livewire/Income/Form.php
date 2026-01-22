<?php

declare(strict_types=1);

namespace App\Livewire\Income;

use App\Livewire\Concerns\HandlesErrors;
use App\Models\Income;
use App\Models\IncomeCategory;
use App\Services\BranchAccessService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class Form extends Component
{
    use AuthorizesRequests;
    use HandlesErrors;
    use WithFileUploads;

    public ?Income $income = null;

    public bool $editMode = false;

    public string $category_id = '';

    public string $reference_number = '';

    public string $income_date = '';

    public float $amount = 0;

    public string $payment_method = 'cash';

    public string $description = '';

    // Changed from direct file upload to path-based selection
    public ?string $attachment = null;

    protected function rules(): array
    {
        $branchId = $this->determineBranchId($this->income);

        return [
            // V58-CRITICAL-02 FIX: Use BranchScopedExists for branch-aware validation
            'category_id' => ['nullable', new \App\Rules\BranchScopedExists('income_categories', 'id', $branchId, allowNull: true)],
            'reference_number' => 'nullable|string|max:100',
            'income_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'attachment' => 'nullable|string|max:500',
        ];
    }

    /**
     * MED-03 FIX: Unified branch determination logic used by both mount and save
     * Returns the branch ID to use, checking in order: income record, user branch_id, user branches pivot
     */
    protected function determineBranchId(?Income $income = null): ?int
    {
        $user = auth()->user();

        // If editing an existing income, use its branch
        if ($income && $income->exists && $income->branch_id) {
            return (int) $income->branch_id;
        }

        if (! $user) {
            return null;
        }

        // Check direct branch_id assignment
        if ($user->branch_id) {
            return (int) $user->branch_id;
        }

        // Fallback to first branch from pivot relationship
        $firstBranch = $user->branches()->first();

        return $firstBranch ? (int) $firstBranch->id : null;
    }

    public function mount(?Income $income = null): void
    {
        $this->authorize('income.manage');

        $user = auth()->user();
        $isSuperAdmin = $user?->hasAnyRole(['Super Admin', 'super-admin']);
        $this->income_date = now()->format('Y-m-d');

        if ($income && $income->exists) {
            // MED-03 FIX: Use BranchAccessService for consistent multi-branch access check
            $branchAccessService = app(BranchAccessService::class);
            if (! $isSuperAdmin && $income->branch_id && ! $branchAccessService->canAccessBranch($user, $income->branch_id)) {
                abort(403, __('You cannot access income records from other branches.'));
            }

            $this->income = $income;
            $this->editMode = true;
            $this->category_id = (string) ($income->category_id ?? '');
            $this->reference_number = $income->reference_number ?? '';
            $this->income_date = $income->income_date->format('Y-m-d');
            $this->amount = decimal_float($income->amount);
            $this->payment_method = $income->payment_method ?? 'cash';
            $this->description = $income->description ?? '';
            $this->attachment = $income->attachment ?? null;
        }
    }

    #[On('file-uploaded')]
    public function handleFileUploaded(string $fieldId, string $path, array $fileInfo): void
    {
        if ($fieldId === 'income-attachment') {
            $this->attachment = $path;
        }
    }

    #[On('file-cleared')]
    public function handleFileCleared(string $fieldId): void
    {
        if ($fieldId === 'income-attachment') {
            $this->attachment = null;
        }
    }

    public function save(): mixed
    {
        // V58-HIGH-01 FIX: Re-authorize on mutation to prevent direct method calls
        $this->authorize('incomes.manage');

        $validated = $this->validate();
        $user = auth()->user();
        $isSuperAdmin = $user?->hasAnyRole(['Super Admin', 'super-admin']);

        // MED-03 FIX: Use unified branch determination logic
        $branchId = $this->determineBranchId($this->income);

        if (! $branchId && ! $isSuperAdmin) {
            abort(403, __('Unable to determine a branch for this income record.'));
        }

        // MED-03 FIX: Use BranchAccessService for consistent access check
        if ($this->income && $this->income->branch_id && ! $isSuperAdmin) {
            $branchAccessService = app(BranchAccessService::class);
            if (! $branchAccessService->canAccessBranch($user, $this->income->branch_id)) {
                abort(403, __('You cannot modify income records from another branch.'));
            }
        }

        // V23-MED-05 FIX: Don't overwrite created_by on updates
        $validated['branch_id'] = $this->income?->branch_id ?? $branchId;
        if (! $this->editMode) {
            // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
            $validated['created_by'] = actual_user_id();
        }

        return $this->handleOperation(
            operation: function () use ($validated) {
                if ($this->editMode) {
                    $this->income->update($validated);
                } else {
                    Income::create($validated);
                }
            },
            successMessage: $this->editMode ? __('Income updated successfully') : __('Income created successfully'),
            redirectRoute: 'app.income.index'
        );
    }

    public function render()
    {
        $categories = IncomeCategory::active()->get();

        return view('livewire.income.form', [
            'categories' => $categories,
        ])->layout('layouts.app', ['title' => $this->editMode ? __('Edit Income') : __('Add Income')]);
    }
}
