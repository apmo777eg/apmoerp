<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Modules\ProductFields;

use App\Http\Requests\Traits\HasMultilingualValidation;
use App\Models\Module;
use App\Models\ModuleProductField;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Form extends Component
{
    use HasMultilingualValidation;

    public ?int $moduleId = null;

    public ?Module $module = null;

    public ?int $fieldId = null;

    public string $field_key = '';

    public string $field_label = '';

    public string $field_label_ar = '';

    public string $field_type = 'text';

    public array $field_options = [];

    public string $optionsText = '';

    public string $placeholder = '';

    public string $placeholder_ar = '';

    public string $default_value = '';

    public string $validation_rules = '';

    public bool $is_required = false;

    public bool $is_searchable = false;

    public bool $is_filterable = false;

    public bool $show_in_list = true;

    public bool $show_in_form = true;

    public bool $is_active = true;

    public int $sort_order = 0;

    public string $field_group = 'general';

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

    public function mount(?int $moduleId = null, ?int $field = null): void
    {
        $user = auth()->user();
        if (! $user || ! $user->can('modules.manage')) {
            abort(403);
        }

        if ($moduleId) {
            $this->moduleId = $moduleId;
            $this->module = Module::findOrFail($moduleId);
        }

        if ($field) {
            $this->fieldId = $field;
            $this->loadField();
        } else {
            $maxSort = ModuleProductField::where('module_id', $this->moduleId)->max('sort_order') ?? 0;
            $this->sort_order = $maxSort + 10;
        }
    }

    /**
     * Auto-generate field_key from field_label when creating a new field
     * This makes it easier for non-technical admins
     */
    public function updatedFieldLabel(): void
    {
        // Only auto-generate if not editing an existing field
        // Always regenerate when creating new field to keep label and key in sync
        if (! $this->fieldId) {
            $this->field_key = $this->generateFieldKey($this->field_label);
        }
    }

    protected function generateFieldKey(string $label): string
    {
        // Convert label to snake_case, lowercase, only letters and underscores
        $key = strtolower(trim($label));
        $key = preg_replace('/[^a-z0-9]+/', '_', $key);
        $key = preg_replace('/_+/', '_', $key);
        $key = trim($key, '_');
        
        return $key ?: 'custom_field';
    }

    protected function loadField(): void
    {
        $field = ModuleProductField::where('module_id', $this->moduleId)->findOrFail($this->fieldId);
        $this->field_key = $field->field_key;
        $this->field_label = $field->field_label;
        $this->field_label_ar = $field->field_label_ar ?? '';
        $this->field_type = $field->field_type;
        $this->field_options = $field->field_options ?? [];
        $this->optionsText = is_array($field->field_options) ? implode("\n", $field->field_options) : '';
        $this->placeholder = $field->placeholder ?? '';
        $this->placeholder_ar = $field->placeholder_ar ?? '';
        $this->default_value = $field->default_value ?? '';
        $this->validation_rules = $field->validation_rules ?? '';
        $this->is_required = $field->is_required;
        $this->is_searchable = $field->is_searchable;
        $this->is_filterable = $field->is_filterable;
        $this->show_in_list = $field->show_in_list;
        $this->show_in_form = $field->show_in_form;
        $this->is_active = $field->is_active;
        $this->sort_order = $field->sort_order;
        $this->field_group = $field->field_group ?? 'general';
    }

    protected function rules(): array
    {
        $keyRule = $this->fieldId
            ? 'required|string|max:100|unique:module_product_fields,field_key,'.$this->fieldId.',id,module_id,'.$this->moduleId
            : 'required|string|max:100|unique:module_product_fields,field_key,NULL,id,module_id,'.$this->moduleId;

        return [
            'moduleId' => 'required|integer|exists:modules,id',
            'field_key' => $keyRule,
            'field_label' => $this->multilingualString(required: true, max: 255),
            'field_label_ar' => $this->multilingualString(required: false, max: 255),
            'field_type' => 'required|string|in:'.implode(',', array_keys($this->fieldTypes)),
            'optionsText' => 'nullable|string',
            'placeholder' => $this->multilingualString(required: false, max: 255),
            'placeholder_ar' => $this->multilingualString(required: false, max: 255),
            'default_value' => $this->unicodeText(required: false, max: 255),
            'validation_rules' => 'nullable|string|max:500',
            'is_required' => 'boolean',
            'is_searchable' => 'boolean',
            'is_filterable' => 'boolean',
            'show_in_list' => 'boolean',
            'show_in_form' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
            'field_group' => $this->multilingualString(required: true, max: 50),
        ];
    }

    public function save(): void
    {
        $user = auth()->user();
        if (! $user || ! $user->can('modules.manage')) {
            abort(403);
        }

        $this->validate();

        if (! $this->moduleId) {
            session()->flash('error', __('Please select a module before saving a field.'));

            return;
        }

        $options = array_filter(array_map('trim', explode("\n", $this->optionsText)));

        $data = [
            'module_id' => $this->moduleId,
            'field_key' => $this->field_key,
            'field_label' => $this->field_label,
            'field_label_ar' => $this->field_label_ar ?: null,
            'field_type' => $this->field_type,
            'field_options' => ! empty($options) ? $options : null,
            'placeholder' => $this->placeholder ?: null,
            'placeholder_ar' => $this->placeholder_ar ?: null,
            'default_value' => $this->default_value ?: null,
            'validation_rules' => $this->validation_rules ?: null,
            'is_required' => $this->is_required,
            'is_searchable' => $this->is_searchable,
            'is_filterable' => $this->is_filterable,
            'show_in_list' => $this->show_in_list,
            'show_in_form' => $this->show_in_form,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'field_group' => $this->field_group,
        ];

        if ($this->fieldId) {
            ModuleProductField::where('id', $this->fieldId)->update($data);
            session()->flash('success', __('Field updated successfully'));
        } else {
            ModuleProductField::create($data);
            session()->flash('success', __('Field created successfully'));
        }

        $this->redirectRoute('admin.modules.product-fields', ['moduleId' => $this->moduleId], navigate: true);
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.admin.modules.product-fields.form', [
            'fieldTypes' => $this->fieldTypes,
            'fieldGroups' => $this->fieldGroups,
        ]);
    }
}
