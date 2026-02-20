<?php

namespace App\Livewire\Admin\Modules;

use App\Models\Module;
use App\Models\ModuleProductField;
use App\Services\ModuleProductService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Livewire\BaseComponent as Component;
class Fields extends Component
{
    use AuthorizesRequests;

    public Module $module;

    protected ModuleProductService $productService;

    public function boot(ModuleProductService $productService): void
    {
        $this->productService = $productService;
    }

    public function mount(Module $module): void
    {
        $this->authorize('modules.manage');
        $this->module = $module;
    }

    public function delete(int $fieldId): void
    {
        $this->authorize('modules.manage');
        $this->productService->deleteField($fieldId);
        session()->flash('success', __('Field deleted successfully'));
    }

    public function toggleActive(int $fieldId): void
    {
        $this->authorize('modules.manage');
        $field = ModuleProductField::findOrFail($fieldId);
        $field->update(['is_active' => ! $field->is_active]);
    }

    public function updateOrder(array $orderedIds): void
    {
        $this->authorize('modules.manage');
        $this->productService->reorderFields($this->module->id, $orderedIds);
    }

    public function render()
    {
        $fields = $this->productService->getModuleFields($this->module->id, false);

        return view('livewire.admin.modules.fields', [
            'fields' => $fields,
            'fieldTypes' => [
                'text' => __('Text'),
                'textarea' => __('Textarea'),
                'number' => __('Number'),
                'decimal' => __('Decimal'),
                'date' => __('Date'),
                'datetime' => __('Date & Time'),
                'select' => __('Select'),
                'multiselect' => __('Multi-Select'),
                'checkbox' => __('Checkbox'),
                'radio' => __('Radio'),
                'file' => __('File'),
                'image' => __('Image'),
                'color' => __('Color Picker'),
                'url' => __('URL'),
                'email' => __('Email'),
                'phone' => __('Phone'),
            ],
        ])->layout('layouts.app');
    }
}
