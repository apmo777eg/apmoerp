<?php

declare(strict_types=1);

namespace App\Livewire\Income\Categories;

use App\Models\IncomeCategory;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    #[Url]
    public string $search = '';

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->can('income.manage')) {
            abort(403);
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        // V55-CRITICAL-05 FIX: Wrap orWhere in a nested where to preserve branch scope
        $categories = IncomeCategory::query()
            ->when($this->search, fn ($q) => $q->where(function ($query) {
                $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('name_ar', 'like', "%{$this->search}%");
            }))
            ->withCount('incomes')
            ->orderBy('name')
            ->paginate(20);

        return view('livewire.income.categories.index', [
            'categories' => $categories,
        ]);
    }

    /**
     * V55-CRITICAL-05 FIX: Added authorization check and branch-scoped lookup.
     * The find() call automatically applies branch scope via HasBranch trait.
     */
    public function delete(int $id): void
    {
        $this->authorize('income.manage');

        // Branch scope is automatically applied by HasBranch trait
        $category = IncomeCategory::find($id);
        if (! $category) {
            session()->flash('error', __('Category not found'));

            return;
        }

        if ($category->incomes()->count() > 0) {
            session()->flash('error', __('Cannot delete category with incomes'));

            return;
        }
        $category->delete();
        session()->flash('success', __('Category deleted successfully'));
    }

    /**
     * V55-CRITICAL-05 FIX: Added authorization check and branch-scoped lookup.
     * The find() call automatically applies branch scope via HasBranch trait.
     */
    public function toggleActive(int $id): void
    {
        $this->authorize('income.manage');

        // Branch scope is automatically applied by HasBranch trait
        $category = IncomeCategory::find($id);
        if ($category) {
            $category->update(['is_active' => ! $category->is_active]);
        }
    }
}
