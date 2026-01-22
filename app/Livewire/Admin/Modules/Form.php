<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Modules;

use App\Livewire\Concerns\HandlesErrors;
use App\Models\Module;
use App\Models\ModuleCustomField;
use Livewire\Component;

class Form extends Component
{
    use HandlesErrors;

    public ?Module $module = null;

    public bool $editMode = false;

    public string $key = '';

    public string $name = '';

    public string $name_ar = '';

    public string $description = '';

    public string $description_ar = '';

    public string $icon = '📦';

    public string $color = 'emerald';

    public bool $is_active = true;

    public int $sort_order = 0;

    public array $customFields = [];

    protected function rules(): array
    {
        $unique = $this->editMode ? '|unique:modules,key,'.$this->module->id : '|unique:modules,key';

        return [
            'key' => 'required|string|max:50'.$unique,
            'name' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'description_ar' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
            'customFields' => 'array',
        ];
    }

    public function mount(?Module $module = null): void
    {
        // Authorization check - must have modules.manage permission
        $user = auth()->user();
        if (! $user || ! $user->can('modules.manage')) {
            abort(403, __('Unauthorized access to module management'));
        }

        if ($module && $module->exists) {
            $this->module = $module;
            $this->editMode = true;
            $this->key = $module->key ?? '';
            $this->name = $module->name;
            $this->name_ar = $module->name_ar ?? '';
            $this->description = $module->description ?? '';
            $this->description_ar = $module->description_ar ?? '';
            $this->icon = $module->icon ?? '📦';
            $this->color = $module->color ?? 'emerald';
            $this->is_active = $module->is_active;
            $this->sort_order = $module->sort_order ?? 0;
            $this->customFields = $module->customFields->map(fn ($f) => [
                'id' => $f->id,
                'field_key' => $f->field_key,
                'field_label' => $f->field_label,
                'field_label_ar' => $f->field_label_ar,
                'field_type' => $f->field_type,
                'is_required' => $f->is_required,
                'is_active' => $f->is_active,
            ])->toArray();
        }
    }

    public function addCustomField(): void
    {
        $this->customFields[] = [
            'id' => null,
            'field_key' => '',
            'field_label' => '',
            'field_label_ar' => '',
            'field_type' => 'text',
            'is_required' => false,
            'is_active' => true,
        ];
    }

    public function removeCustomField(int $index): void
    {
        unset($this->customFields[$index]);
        $this->customFields = array_values($this->customFields);
    }

    public function save(): mixed
    {
        // V58-HIGH-01 FIX: Re-authorize on mutation to prevent direct method calls
        $user = auth()->user();
        if (! $user || ! $user->can('modules.manage')) {
            abort(403, __('Unauthorized access to module management'));
        }

        $validated = $this->validate();
        $moduleData = collect($validated)->except('customFields')->toArray();
        $customFields = $this->customFields;
        $editMode = $this->editMode;
        $existingModule = $this->module;

        return $this->handleOperation(
            operation: function () use ($moduleData, $customFields, $editMode, $existingModule) {
                if ($editMode) {
                    $existingModule->update($moduleData);
                    $module = $existingModule;
                } else {
                    $module = Module::create($moduleData);
                }

                $existingIds = [];
                foreach ($customFields as $field) {
                    if (! empty($field['field_key']) && ! empty($field['field_label'])) {
                        if ($field['id']) {
                            ModuleCustomField::where('id', $field['id'])->update([
                                'field_key' => $field['field_key'],
                                'field_label' => $field['field_label'],
                                'field_label_ar' => $field['field_label_ar'] ?? null,
                                'field_type' => $field['field_type'],
                                'is_required' => $field['is_required'],
                                'is_active' => $field['is_active'],
                            ]);
                            $existingIds[] = $field['id'];
                        } else {
                            $newField = $module->customFields()->create([
                                'field_key' => $field['field_key'],
                                'field_label' => $field['field_label'],
                                'field_label_ar' => $field['field_label_ar'] ?? null,
                                'field_type' => $field['field_type'],
                                'is_required' => $field['is_required'],
                                'is_active' => $field['is_active'],
                            ]);
                            $existingIds[] = $newField->id;
                        }
                    }
                }

                $module->customFields()->whereNotIn('id', $existingIds)->delete();
            },
            successMessage: $editMode ? __('Module updated successfully') : __('Module created successfully'),
            redirectRoute: 'admin.modules.index'
        );
    }

    public function render()
    {
        return view('livewire.admin.modules.form')
            ->layout('layouts.app', ['title' => $this->editMode ? __('Edit Module') : __('Add Module')]);
    }
}
