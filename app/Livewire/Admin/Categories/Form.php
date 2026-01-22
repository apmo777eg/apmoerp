<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Categories;

use App\Http\Requests\Traits\HasMultilingualValidation;
use App\Models\ProductCategory;
use App\Rules\BranchScopedExists;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Form extends Component
{
    use HasMultilingualValidation;

    public ?int $categoryId = null;

    public string $name = '';

    public string $nameAr = '';

    public ?int $parentId = null;

    public string $description = '';

    public int $sortOrder = 0;

    public bool $isActive = true;

    public function mount(?int $category = null): void
    {
        $user = Auth::user();
        if (! $user || ! $user->can('inventory.categories.manage')) {
            abort(403);
        }

        if ($category) {
            $this->categoryId = $category;
            $this->loadCategory();
        } else {
            $this->sortOrder = ProductCategory::max('sort_order') + 1;
        }
    }

    protected function loadCategory(): void
    {
        $category = ProductCategory::findOrFail($this->categoryId);
        $this->name = $category->name;
        $this->nameAr = $category->name_ar ?? '';
        $this->parentId = $category->parent_id;
        $this->description = $category->description ?? '';
        $this->sortOrder = $category->sort_order;
        $this->isActive = $category->is_active;
    }

    protected function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                $this->categoryId
                    ? Rule::unique('product_categories', 'name')->ignore($this->categoryId)
                    : Rule::unique('product_categories', 'name'),
            ],
            'nameAr' => 'nullable|string|max:255',
            'parentId' => [
                'nullable',
                // V57-CRITICAL-03 FIX: Use BranchScopedExists to prevent cross-branch parent references
                new BranchScopedExists('product_categories', 'id', null, true),
                function ($attribute, $value, $fail) {
                    if ($this->categoryId && $value == $this->categoryId) {
                        $fail(__('A category cannot be its own parent'));
                    }
                },
            ],
            'description' => 'nullable|string|max:1000',
            'sortOrder' => 'integer|min:0',
        ];
    }

    public function save(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->can('inventory.categories.manage')) {
            abort(403);
        }

        $this->validate();

        $data = [
            'name' => $this->name,
            'name_ar' => $this->nameAr ?: null,
            'parent_id' => $this->parentId,
            'description' => $this->description ?: null,
            'sort_order' => $this->sortOrder,
            'is_active' => $this->isActive,
            'updated_by' => $user?->id,
        ];

        try {
            if ($this->categoryId) {
                $category = ProductCategory::findOrFail($this->categoryId);
                $data['slug'] = Str::slug($this->name).'-'.Str::random(4);
                $category->update($data);
                session()->flash('success', __('Category updated successfully'));
            } else {
                $data['slug'] = Str::slug($this->name).'-'.Str::random(4);
                $data['created_by'] = $user?->id;
                $data['branch_id'] = $user?->branch_id;
                ProductCategory::create($data);
                session()->flash('success', __('Category created successfully'));
            }

            $this->redirectRoute('app.inventory.categories.index', navigate: true);
        } catch (\Exception $e) {
            $this->addError('name', __('Failed to save category. Please try again.'));
        }
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $parentCategories = ProductCategory::roots()
            ->active()
            ->when($this->categoryId, fn ($q) => $q->where('id', '!=', $this->categoryId))
            ->orderBy('name')
            ->get();

        return view('livewire.admin.categories.form', [
            'parentCategories' => $parentCategories,
        ]);
    }
}
