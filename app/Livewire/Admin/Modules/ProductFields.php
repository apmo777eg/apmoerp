<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Modules;

use App\Models\Module;
use App\Models\ModuleProductField;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ProductFields extends Component
{
    public ?int $moduleId = null;

    public ?Module $module = null;

    public array $fields = [];

    public array $modules = [];

    protected array $fieldTypes = [
        'text' => 'Text',
        'textarea' => 'Textarea',
        'number' => 'Number',
        'decimal' => 'Decimal',
        'date' => 'Date',
        'datetime' => 'Date & Time',
        'select' => 'Dropdown',
        'multiselect' => 'Multi-Select',
        'checkbox' => 'Checkbox',
        'radio' => 'Radio Buttons',
        'file' => 'File Upload',
        'color' => 'Color Picker',
        'url' => 'URL',
        'email' => 'Email',
    ];

    protected array $fieldGroups = [
        'general' => 'General',
        'specifications' => 'Specifications',
        'dimensions' => 'Dimensions',
        'pricing' => 'Pricing',
        'inventory' => 'Inventory',
        'custom' => 'Custom',
    ];

    public function mount(?int $moduleId = null): void
    {
        $user = auth()->user();
        if (! $user || ! $user->can('modules.manage')) {
            abort(403);
        }

        $this->modules = Module::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'name_ar'])
            ->toArray();

        if ($moduleId) {
            $this->moduleId = $moduleId;
            $this->loadModule();
        } elseif (count($this->modules) > 0) {
            $this->moduleId = $this->modules[0]['id'];
            $this->loadModule();
        }
    }

    public function updatedModuleId(): void
    {
        $this->loadModule();
    }

    protected function loadModule(): void
    {
        if (! $this->moduleId) {
            $this->module = null;
            $this->fields = [];

            return;
        }

        $this->module = Module::find($this->moduleId);
        $this->loadFields();
    }

    protected function loadFields(): void
    {
        if (! $this->moduleId) {
            $this->fields = [];

            return;
        }

        $this->fields = ModuleProductField::where('module_id', $this->moduleId)
            ->orderBy('sort_order')
            ->orderBy('field_label')
            ->get()
            ->toArray();
    }

    public function toggleActive(int $fieldId): void
    {
        $this->authorize('modules.manage');

        $field = ModuleProductField::where('module_id', $this->moduleId)->findOrFail($fieldId);
        $field->update(['is_active' => ! $field->is_active]);
        $this->loadFields();
    }

    public function delete(int $fieldId): void
    {
        $this->authorize('modules.manage');

        ModuleProductField::where('module_id', $this->moduleId)->findOrFail($fieldId)->delete();
        session()->flash('success', __('Field deleted successfully'));
        $this->loadFields();
    }

    public function reorder(array $orderedIds): void
    {
        if (! $this->moduleId) {
            return;
        }

        DB::transaction(function () use ($orderedIds) {
            foreach ($orderedIds as $index => $id) {
                ModuleProductField::where('id', $id)
                    ->where('module_id', $this->moduleId)
                    ->update(['sort_order' => ($index + 1) * 10]);
            }
        });
        $this->loadFields();
    }

    public function render()
    {
        return view('livewire.admin.modules.product-fields', [
            'fieldTypes' => $this->fieldTypes,
            'fieldGroups' => $this->fieldGroups,
        ]);
    }
}
