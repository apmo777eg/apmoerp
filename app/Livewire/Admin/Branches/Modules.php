<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Branches;

use App\Models\Branch;
use App\Models\BranchModule;
use App\Models\Module;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Modules extends Component
{
    public Branch $branch;

    public array $modules = [];

    public array $enabledModules = [];

    public array $moduleSettings = [];

    public function mount(Branch $branch): void
    {
        $user = auth()->user();
        if (! $user || ! $user->can('branches.manage')) {
            abort(403);
        }

        $this->branch = $branch;
        $this->loadModules();
    }

    protected function loadModules(): void
    {
        $allModules = Module::where('is_active', true)
            ->orderBy('name')
            ->get();

        $branchModules = BranchModule::where('branch_id', $this->branch->id)
            ->get()
            ->keyBy('module_id');

        $this->modules = [];
        $this->enabledModules = [];
        $this->moduleSettings = [];

        foreach ($allModules as $module) {
            $branchModule = $branchModules->get($module->id);

            $this->modules[] = [
                'id' => $module->id,
                'name' => $module->name,
                'name_ar' => $module->name_ar,
                'module_key' => $module->module_key,
                'description' => $module->description,
                'icon' => $module->icon,
            ];

            $this->enabledModules[$module->id] = $branchModule?->enabled ?? false;
            $this->moduleSettings[$module->id] = $branchModule?->settings ?? [];
        }
    }

    public function toggleModule(int $moduleId): void
    {
        $this->enabledModules[$moduleId] = ! ($this->enabledModules[$moduleId] ?? false);
    }

    public function save(): void
    {
        // V58-HIGH-01 FIX: Re-authorize on mutation to prevent direct method calls
        $user = auth()->user();
        if (! $user || ! $user->can('branches.manage')) {
            abort(403);
        }

        DB::transaction(function () {
            foreach ($this->modules as $module) {
                $moduleId = $module['id'];
                $enabled = $this->enabledModules[$moduleId] ?? false;
                $settings = $this->moduleSettings[$moduleId] ?? [];

                BranchModule::updateOrCreate(
                    [
                        'branch_id' => $this->branch->id,
                        'module_id' => $moduleId,
                    ],
                    [
                        'module_key' => $module['module_key'],
                        'enabled' => $enabled,
                        'settings' => $settings,
                    ]
                );
            }
        });

        session()->flash('success', __('Branch modules updated successfully'));
    }

    public function render()
    {
        return view('livewire.admin.branches.modules');
    }
}
