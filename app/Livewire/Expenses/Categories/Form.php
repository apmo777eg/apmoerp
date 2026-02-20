<?php

declare(strict_types=1);

namespace App\Livewire\Expenses\Categories;

use App\Http\Requests\Traits\HasMultilingualValidation;
use App\Models\ExpenseCategory;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use App\Livewire\BaseComponent as Component;
#[Layout('layouts.app')]
class Form extends Component
{
    use AuthorizesRequests;
    use HasMultilingualValidation;

    public ?ExpenseCategory $category = null;

    public string $name = '';

    public string $nameAr = '';

    public string $description = '';

    public bool $isActive = true;

    public function mount(?ExpenseCategory $category = null): void
    {
        $this->authorize('expenses.manage');

        if ($category && $category->exists) {
            $this->category = $category;
            $this->name = $category->name;
            $this->nameAr = $category->name_ar ?? '';
            $this->description = $category->description ?? '';
            $this->isActive = $category->is_active;
        }
    }

    public function rules(): array
    {
        $branchId = $this->category?->branch_id ?? auth()->user()?->branch_id;

        return [
            'name' => array_merge(
                $this->multilingualString(required: true, max: 255),
                [
                    Rule::unique('expense_categories', 'name')
                        ->where(fn ($query) => $query->where('branch_id', $branchId))
                        ->ignore($this->category?->id),
                ]
            ),
            'nameAr' => $this->multilingualString(required: false, max: 255),
            'description' => $this->unicodeText(required: false, max: 1000),
            'isActive' => 'boolean',
        ];
    }

    public function save(): mixed
    {
        // V58-HIGH-01 FIX: Re-authorize on mutation to prevent direct method calls
        $this->authorize('expenses.manage');

        $data = $this->validate();

        $payload = [
            'name' => $data['name'],
            'name_ar' => $data['nameAr'] ?: null,
            'description' => $data['description'] ?: null,
            'is_active' => $data['isActive'],
            'branch_id' => $this->category?->branch_id ?? auth()->user()?->branch_id,
        ];

        if ($this->category) {
            $this->category->update($payload);
            session()->flash('success', __('Category updated successfully'));
        } else {
            ExpenseCategory::create($payload);
            session()->flash('success', __('Category created successfully'));
        }

        $this->redirectRoute('app.expenses.categories.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.expenses.categories.form');
    }
}
