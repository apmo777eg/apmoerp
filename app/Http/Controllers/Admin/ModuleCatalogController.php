<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ModuleCatalogController extends Controller
{
    public function index()
    {
        // V57-HIGH-01 FIX: Add authorization for module management
        $this->authorize('modules.manage');
        
        $mods = (array) config('modules.available', []);

        return $this->ok($mods);
    }

    /**
     * NEW-V15-CRITICAL-02 FIX: Store a new module in catalog
     */
    public function store(Request $request)
    {
        // V57-HIGH-01 FIX: Add authorization for module management
        $this->authorize('modules.manage');
        
        $validated = $this->validate($request, [
            'key' => ['required', 'string', 'max:100', 'unique:modules,key'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ]);

        $module = \App\Models\Module::create([
            'key' => $validated['key'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return $this->ok($module, __('Module created successfully'), 201);
    }

    /**
     * NEW-V15-CRITICAL-02 FIX: Show a specific module
     */
    public function show(int $module)
    {
        // V57-HIGH-01 FIX: Add authorization for module viewing
        $this->authorize('modules.view');
        
        $moduleRecord = \App\Models\Module::findOrFail($module);

        return $this->ok($moduleRecord);
    }

    /**
     * NEW-V15-CRITICAL-02 FIX: Update a module
     */
    public function update(Request $request, int $module)
    {
        // V57-HIGH-01 FIX: Add authorization for module management
        $this->authorize('modules.manage');
        
        $moduleRecord = \App\Models\Module::findOrFail($module);

        $validated = $this->validate($request, [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ]);

        $moduleRecord->update($validated);

        return $this->ok($moduleRecord, __('Module updated successfully'));
    }

    /**
     * NEW-V15-CRITICAL-02 FIX: Delete a module
     */
    public function destroy(int $module)
    {
        // V57-HIGH-01 FIX: Add authorization for module management
        $this->authorize('modules.manage');
        
        $moduleRecord = \App\Models\Module::findOrFail($module);
        $moduleRecord->delete();

        return $this->ok(null, __('Module deleted successfully'));
    }
}
