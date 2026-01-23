<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Modules;

use App\Models\Branch;
use App\Models\BranchModule;
use App\Models\Module;
use App\Services\ModuleNavigationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ManagementCenter extends Component
{
    use AuthorizesRequests;

    public ?int $selectedModuleId = null;

    public ?int $selectedBranchId = null;

    public array $modules = [];

    public array $branches = [];

    public ?array $selectedModuleData = null;

    public ?array $branchModuleSettings = null;

    public function mount(): void
    {
        $this->authorize('modules.manage');

        $this->loadModules();
        $this->loadBranches();
    }

    public function loadModules(): void
    {
        $this->modules = Module::query()
            ->select(['id', 'module_key', 'name', 'name_ar', 'is_active', 'is_core', 'module_type', 'icon', 'description'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(function ($module) {
                return [
                    'id' => $module->id,
                    'module_key' => $module->module_key,
                    'name' => $module->localized_name,
                    'is_active' => $module->is_active,
                    'is_core' => $module->is_core,
                    'module_type' => $module->module_type,
                    'icon' => $module->icon ?? '📦',
                    'description' => $module->localized_description ?? '',
                ];
            })
            ->toArray();
    }

    public function loadBranches(): void
    {
        $this->branches = Branch::query()
            ->select(['id', 'name', 'is_main'])
            ->orderBy('is_main', 'desc')
            ->orderBy('name')
            ->get()
            ->map(function ($branch) {
                return [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'is_main' => $branch->is_main,
                ];
            })
            ->toArray();
    }

    public function selectModule(int $moduleId): void
    {
        $this->selectedModuleId = $moduleId;
        $this->loadModuleDetails();
    }

    public function loadModuleDetails(): void
    {
        if (! $this->selectedModuleId) {
            $this->selectedModuleData = null;

            return;
        }

        $module = Module::with([
            'navigation',
            'customFields',
            'settings',
            'operations',
            'policies',
            'reportDefinitions',
        ])->find($this->selectedModuleId);

        if (! $module) {
            $this->selectedModuleData = null;

            return;
        }

        $this->selectedModuleData = [
            'id' => $module->id,
            'module_key' => $module->module_key,
            'name' => $module->localized_name,
            'description' => $module->localized_description ?? '',
            'is_active' => $module->is_active,
            'is_core' => $module->is_core,
            'module_type' => $module->module_type ?? 'functional',
            'icon' => $module->icon ?? '📦',
            'navigation_count' => $module->navigation->count(),
            'custom_fields_count' => $module->customFields->count(),
            'settings_count' => $module->settings->count(),
            'operations_count' => $module->operations->count(),
            'policies_count' => $module->policies->count(),
            'reports_count' => $module->reportDefinitions->count(),
        ];

        // Load branch settings if branch is selected
        if ($this->selectedBranchId) {
            $this->loadBranchModuleSettings();
        }
    }

    public function selectBranch(int $branchId): void
    {
        $this->selectedBranchId = $branchId;
        $this->loadBranchModuleSettings();
    }

    public function loadBranchModuleSettings(): void
    {
        if (! $this->selectedModuleId || ! $this->selectedBranchId) {
            $this->branchModuleSettings = null;

            return;
        }

        $branchModule = BranchModule::query()
            ->where('branch_id', $this->selectedBranchId)
            ->where('module_id', $this->selectedModuleId)
            ->first();

        $this->branchModuleSettings = [
            'enabled' => $branchModule?->enabled ?? false,
            'settings' => $branchModule?->settings ?? [],
            'activated_at' => $branchModule?->activated_at?->format('Y-m-d H:i') ?? null,
        ];
    }

    public function toggleModuleForBranch(): void
    {
        if (! $this->selectedModuleId || ! $this->selectedBranchId) {
            return;
        }

        $branchModule = BranchModule::query()
            ->where('branch_id', $this->selectedBranchId)
            ->where('module_id', $this->selectedModuleId)
            ->first();

        if ($branchModule) {
            $branchModule->enabled = ! $branchModule->enabled;
            if ($branchModule->enabled) {
                $branchModule->activated_at = now();
            }
            $branchModule->save();
        } else {
            $module = Module::find($this->selectedModuleId);
            BranchModule::create([
                'branch_id' => $this->selectedBranchId,
                'module_id' => $this->selectedModuleId,
                'module_key' => $module->module_key,
                'enabled' => true,
                'activated_at' => now(),
            ]);
        }

        $this->loadBranchModuleSettings();

        session()->flash('success', __('Module status updated successfully'));
    }

    public function toggleModuleActive(): void
    {
        if (! $this->selectedModuleId) {
            return;
        }

        $module = Module::find($this->selectedModuleId);
        if (! $module) {
            return;
        }

        // Prevent deactivating core modules
        if ($module->is_core && $module->is_active) {
            session()->flash('error', __('Core modules cannot be deactivated'));

            return;
        }

        $module->is_active = ! $module->is_active;
        $module->save();

        $this->loadModules();
        $this->loadModuleDetails();

        session()->flash('success', __('Module status updated successfully'));
    }

    public function syncNavigation(): void
    {
        $navigationService = app(ModuleNavigationService::class);
        $navigationService->syncNavigationFromModules();

        session()->flash('success', __('Navigation synchronized successfully'));
    }

    public function render()
    {
        return view('livewire.admin.modules.management-center', [
            'modules' => $this->modules,
            'branches' => $this->branches,
            'selectedModule' => $this->selectedModuleData,
            'branchSettings' => $this->branchModuleSettings,
        ]);
    }
}
