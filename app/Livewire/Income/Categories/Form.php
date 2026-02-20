<?php

declare(strict_types=1);

namespace App\Livewire\Income\Categories;

use App\Models\IncomeCategory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use App\Livewire\BaseComponent as Component;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

#[Layout('layouts.app')]
class Form extends Component
{
    use AuthorizesRequests;
    public ?IncomeCategory $category = null;

    public string $name = '';

    public string $nameAr = '';

    public string $description = '';

    public bool $isActive = true;

    public function mount(?IncomeCategory $category = null): void
    {
        $user = Auth::user();
        if (! $user || ! $user->can('income.manage')) {
            abort(403);
        }

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
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('income_categories', 'name')
                    ->where(fn ($query) => $query->where('branch_id', $branchId))
                    ->ignore($this->category?->id),
            ],
            'nameAr' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'isActive' => 'boolean',
        ];
    }

    public function save(): mixed
    {
        // V58-HIGH-01 FIX: Re-authorize on mutation to prevent direct method calls
        $this->authorize('income.manage');

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
            IncomeCategory::create($payload);
            session()->flash('success', __('Category created successfully'));
        }

        $this->redirectRoute('app.income.categories.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.income.categories.form');
    }
}
