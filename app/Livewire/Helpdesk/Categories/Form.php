<?php

declare(strict_types=1);

namespace App\Livewire\Helpdesk\Categories;

use App\Models\TicketCategory;
use App\Models\TicketSLAPolicy;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use App\Livewire\BaseComponent as Component;
class Form extends Component
{
    use AuthorizesRequests;

    public ?int $categoryId = null;

    public string $name = '';

    public string $name_ar = '';

    public string $description = '';

    public ?int $parent_id = null;

    public ?int $default_assignee_id = null;

    public ?int $sla_policy_id = null;

    public string $color = '#3B82F6';

    public string $icon = '';

    public bool $is_active = true;

    public int $sort_order = 0;

    public function mount(?int $category = null): void
    {
        $this->authorize('helpdesk.manage');

        if ($category) {
            $this->categoryId = $category;
            $this->loadCategory();
        }
    }

    protected function loadCategory(): void
    {
        $category = TicketCategory::findOrFail($this->categoryId);

        $this->name = $category->name;
        $this->name_ar = $category->name_ar ?? '';
        $this->description = $category->description ?? '';
        $this->parent_id = $category->parent_id;
        $this->default_assignee_id = $category->default_assignee_id;
        $this->sla_policy_id = $category->sla_policy_id;
        $this->color = $category->color ?? '#3B82F6';
        $this->icon = $category->icon ?? '';
        $this->is_active = $category->is_active;
        $this->sort_order = $category->sort_order;
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:ticket_categories,id',
            'default_assignee_id' => 'nullable|exists:users,id',
            'sla_policy_id' => 'nullable|exists:ticket_sla_policies,id',
            'color' => 'nullable|string|max:20',
            'icon' => 'nullable|string|max:50',
            'sort_order' => 'integer|min:0',
        ];
    }

    public function save(): mixed
    {
        $this->authorize('helpdesk.manage');

        $this->validate();

        $data = [
            'name' => $this->name,
            'name_ar' => $this->name_ar,
            'description' => $this->description,
            'parent_id' => $this->parent_id,
            'default_assignee_id' => $this->default_assignee_id,
            'sla_policy_id' => $this->sla_policy_id,
            'color' => $this->color,
            'icon' => $this->icon,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];

        if ($this->categoryId) {
            $category = TicketCategory::findOrFail($this->categoryId);
            // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
            $data['updated_by'] = actual_user_id();
            $category->update($data);
            session()->flash('success', __('Category updated successfully'));
        } else {
            // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
            $data['created_by'] = actual_user_id();
            TicketCategory::create($data);
            session()->flash('success', __('Category created successfully'));
        }

        $this->redirectRoute('app.helpdesk.categories.index', navigate: true);
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $parentCategories = TicketCategory::whereNull('parent_id')
            ->when($this->categoryId, fn ($q) => $q->where('id', '!=', $this->categoryId))
            ->active()
            ->ordered()
            ->get();

        $agents = User::whereHas('roles', function ($query) {
            $query->where('name', 'like', '%agent%')
                ->orWhere('name', 'like', '%support%')
                ->orWhere('name', 'Super Admin');
        })->get();

        $slaPolicies = TicketSLAPolicy::active()->get();

        return view('livewire.helpdesk.categories.form', [
            'parentCategories' => $parentCategories,
            'agents' => $agents,
            'slaPolicies' => $slaPolicies,
        ]);
    }
}
