<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ModuleProductField;
use App\Services\Contracts\FieldSchemaServiceInterface as Fields;
use Illuminate\Http\Request;

class ModuleFieldController extends Controller
{
    public function __construct(protected Fields $fields) {}

    public function index(Request $request, string $module)
    {
        // V57-HIGH-01 FIX: Add authorization for field viewing
        $this->authorize('modules.fields.view');
        
        $branchId = $request->integer('branch_id') ?: null;

        return $this->ok($this->fields->for($module, $branchId));
    }

    /**
     * NEW-V15-CRITICAL-02 FIX: Store a new module field
     */
    public function store(Request $request, string $module)
    {
        // V57-HIGH-01 FIX: Add authorization for field management
        $this->authorize('modules.fields.manage');
        
        $validated = $this->validate($request, [
            'field_key' => ['required', 'string', 'max:100'],
            'label' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:text,textarea,number,select,checkbox,date,datetime,file'],
            'options' => ['nullable', 'array'],
            'is_required' => ['boolean'],
            'is_filterable' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ]);

        $moduleRecord = \App\Models\Module::where('key', $module)->firstOrFail();

        $field = ModuleProductField::create([
            'module_id' => $moduleRecord->id,
            'field_key' => $validated['field_key'],
            'label' => $validated['label'],
            'type' => $validated['type'],
            'options' => $validated['options'] ?? null,
            'is_required' => $validated['is_required'] ?? false,
            'is_filterable' => $validated['is_filterable'] ?? false,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return $this->ok($field, __('Field created successfully'), 201);
    }

    /**
     * NEW-V15-CRITICAL-02 FIX: Show a specific field
     */
    public function show(string $module, int $field)
    {
        // V57-HIGH-01 FIX: Add authorization for field viewing
        $this->authorize('modules.fields.view');
        
        $fieldRecord = ModuleProductField::findOrFail($field);

        return $this->ok($fieldRecord);
    }

    /**
     * NEW-V15-CRITICAL-02 FIX: Update a module field
     */
    public function update(Request $request, string $module, int $field)
    {
        // V57-HIGH-01 FIX: Add authorization for field management
        $this->authorize('modules.fields.manage');
        
        $fieldRecord = ModuleProductField::findOrFail($field);

        $validated = $this->validate($request, [
            'label' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'string', 'in:text,textarea,number,select,checkbox,date,datetime,file'],
            'options' => ['nullable', 'array'],
            'is_required' => ['boolean'],
            'is_filterable' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ]);

        $fieldRecord->update($validated);

        return $this->ok($fieldRecord, __('Field updated successfully'));
    }

    /**
     * NEW-V15-CRITICAL-02 FIX: Delete a module field
     */
    public function destroy(string $module, int $field)
    {
        // V57-HIGH-01 FIX: Add authorization for field management
        $this->authorize('modules.fields.manage');
        
        $fieldRecord = ModuleProductField::findOrFail($field);
        $fieldRecord->delete();

        return $this->ok(null, __('Field deleted successfully'));
    }

    /**
     * NEW-V15-CRITICAL-02 FIX: Reorder module fields
     */
    public function reorder(Request $request, string $module)
    {
        // V57-HIGH-01 FIX: Add authorization for field management
        $this->authorize('modules.fields.manage');
        
        $validated = $this->validate($request, [
            'fields' => ['required', 'array'],
            'fields.*.id' => ['required', 'integer', 'exists:module_product_fields,id'],
            'fields.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($validated['fields'] as $fieldData) {
            ModuleProductField::where('id', $fieldData['id'])
                ->update(['sort_order' => $fieldData['sort_order']]);
        }

        return $this->ok(null, __('Fields reordered successfully'));
    }

    public function validatePayload(Request $request, string $module)
    {
        // V57-HIGH-01 FIX: Add authorization for field validation
        $this->authorize('modules.fields.view');
        
        $branchId = $request->integer('branch_id') ?: null;
        $validator = $this->fields->validate($module, $request->all(), $branchId);
        $validator->validate();

        return $this->ok(['valid' => true]);
    }
}
