<?php

namespace App\Livewire\Admin\Modules\Fields;

use App\Models\Module;
use App\Models\ModuleProductField;
use App\Services\ModuleProductService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Form extends Component
{
    use AuthorizesRequests;

    public Module $module;

    public ?int $fieldId = null;

    public string $field_key = '';

    public string $field_label = '';

    public string $field_label_ar = '';

    public string $field_type = 'text';

    public array $field_options = [];

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

    public string $field_group = '';

    public string $newOptionKey = '';

    public string $newOptionValue = '';

    protected ModuleProductService $productService;

    use \App\Http\Requests\Traits\HasMultilingualValidation;

    public function getRules(): array
    {
        return [
            'field_key' => 'required|string|max:100|regex:/^[a-z_]+$/',
            'field_label' => $this->multilingualString(required: true, max: 255),
            'field_label_ar' => $this->multilingualString(required: false, max: 255),
            'field_type' => 'required|in:text,textarea,number,decimal,date,datetime,select,multiselect,checkbox,radio,file,image,color,url,email,phone',
            'placeholder' => $this->multilingualString(required: false, max: 255),
            'placeholder_ar' => $this->multilingualString(required: false, max: 255),
            'default_value' => $this->unicodeText(required: false),
            'validation_rules' => 'nullable|string|max:500',
            'field_group' => $this->multilingualString(required: false, max: 100),
        ];
    }

    protected $rules = [];

    public function boot(ModuleProductService $productService): void
    {
        $this->productService = $productService;
    }

    public function mount(Module $module, ?int $field = null): void
    {
        $this->authorize('modules.manage');
        $this->module = $module;

        if ($field) {
            $this->fieldId = $field;
            $this->loadField();
        } else {
            $maxOrder = ModuleProductField::where('module_id', $this->module->id)->max('sort_order') ?? 0;
            $this->sort_order = $maxOrder + 1;
        }
    }

    protected function loadField(): void
    {
        $field = ModuleProductField::findOrFail($this->fieldId);

        $this->field_key = $field->field_key;
        $this->field_label = $field->field_label;
        $this->field_label_ar = $field->field_label_ar ?? '';
        $this->field_type = $field->field_type;
        $this->field_options = $field->field_options ?? [];
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
        $this->field_group = $field->field_group ?? '';
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
        // First lowercase the label
        $key = strtolower(trim($label));
        // Replace any non-lowercase letter with underscore (matching pattern [a-z_]+)
        $key = preg_replace('/[^a-z]+/', '_', $key);
        $key = preg_replace('/_+/', '_', $key); // Remove consecutive underscores
        $key = trim($key, '_'); // Remove leading/trailing underscores
        
        // Ensure it's not empty
        return $key ?: 'custom_field';
    }

    public function addOption(): void
    {
        if (! empty($this->newOptionKey) && ! empty($this->newOptionValue)) {
            $this->field_options[$this->newOptionKey] = $this->newOptionValue;
            $this->newOptionKey = '';
            $this->newOptionValue = '';
        }
    }

    public function removeOption(string $key): void
    {
        unset($this->field_options[$key]);
    }

    public function save(): void
    {
        $this->authorize('modules.manage');
        $this->validate($this->getRules());

        $data = [
            'field_key' => $this->field_key,
            'field_label' => $this->field_label,
            'field_label_ar' => $this->field_label_ar ?: null,
            'field_type' => $this->field_type,
            'field_options' => in_array($this->field_type, ['select', 'multiselect', 'radio']) ? $this->field_options : null,
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
            'field_group' => $this->field_group ?: null,
        ];

        if ($this->fieldId) {
            $this->productService->updateField($this->fieldId, $data);
            session()->flash('success', __('Field updated successfully'));
        } else {
            $this->productService->createField($this->module->id, $data);
            session()->flash('success', __('Field created successfully'));
        }

        $this->redirectRoute('admin.modules.fields', ['module' => $this->module->id], navigate: true);
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.admin.modules.fields.form', [
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
        ]);
    }
}
