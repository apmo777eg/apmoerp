<?php

declare(strict_types=1);

namespace App\Livewire\Expenses\Categories;

use App\Models\ExpenseCategory;
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
        if (! $user || ! $user->can('expenses.manage')) {
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
        $categories = ExpenseCategory::query()
            ->when($this->search, fn ($q) => $q->where(function ($query) {
                $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('name_ar', 'like', "%{$this->search}%");
            }))
            ->withCount('expenses')
            ->orderBy('name')
            ->paginate(20);

        return view('livewire.expenses.categories.index', [
            'categories' => $categories,
        ]);
    }

    /**
     * V55-CRITICAL-05 FIX: Delete is now properly branch-scoped via HasBranch trait.
     * The find() call automatically applies branch scope, preventing cross-branch access.
     */
    public function delete(int $id): void
    {
        $this->authorize('expenses.manage');

        // Branch scope is automatically applied by HasBranch trait
        $category = ExpenseCategory::find($id);
        if (! $category) {
            session()->flash('error', __('Category not found'));

            return;
        }

        if ($category->expenses()->count() > 0) {
            session()->flash('error', __('Cannot delete category with expenses'));

            return;
        }
        $category->delete();
        session()->flash('success', __('Category deleted successfully'));
    }

    /**
     * V55-CRITICAL-05 FIX: Toggle is now properly branch-scoped via HasBranch trait.
     * The find() call automatically applies branch scope, preventing cross-branch access.
     */
    public function toggleActive(int $id): void
    {
        $this->authorize('expenses.manage');

        // Branch scope is automatically applied by HasBranch trait
        $category = ExpenseCategory::find($id);
        if ($category) {
            $category->update(['is_active' => ! $category->is_active]);
        }
    }
}
