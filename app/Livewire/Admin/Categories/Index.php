<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Categories;

use App\Models\ProductCategory;
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
        if (! $user || ! $user->can('inventory.categories.view')) {
            abort(403);
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $categories = ProductCategory::query()
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('name_ar', 'like', "%{$this->search}%"))
            ->with('parent:id,name') // Eager load parent to prevent N+1
            ->withCount('products')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20);

        $parentCategories = ProductCategory::roots()->active()->orderBy('name')->get();

        return view('livewire.admin.categories.index', [
            'categories' => $categories,
            'parentCategories' => $parentCategories,
        ]);
    }

    public function delete(int $id): void
    {
        // V57-HIGH-01 FIX: Explicit authorization inside mutation method
        $this->authorize('inventory.categories.delete');
        
        $category = ProductCategory::find($id);
        if ($category) {
            if ($category->products()->count() > 0) {
                session()->flash('error', __('Cannot delete category with products'));

                return;
            }
            if ($category->children()->count() > 0) {
                session()->flash('error', __('Cannot delete category with subcategories'));

                return;
            }
            $category->delete();
            session()->flash('success', __('Category deleted successfully'));
        }
    }

    public function toggleActive(int $id): void
    {
        // V57-HIGH-01 FIX: Explicit authorization inside mutation method
        $this->authorize('inventory.categories.edit');
        
        $category = ProductCategory::find($id);
        if ($category) {
            $category->update(['is_active' => ! $category->is_active]);
        }
    }
}
