<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Branch;
use App\Models\BranchModule;
use App\Models\Module;
use App\Services\Contracts\ModuleServiceInterface;
use App\Traits\HandlesServiceErrors;
use Illuminate\Support\Facades\Cache;

class ModuleService implements ModuleServiceInterface
{
    use HandlesServiceErrors;

    /** @return array<int, array{key:string,name:string,enabled:bool}> */
    public function allForBranch(?int $branchId = null): array
    {
        $branchId = $branchId ?? request()->attributes->get('branch_id');

        return Cache::remember('modules:b:'.$branchId, 600, function () use ($branchId) {
            if (! $branchId) {
                return [];
            }

            // Get branch modules with their associated Module records from database
            $branchModules = BranchModule::query()
                ->where('branch_id', $branchId)
                ->with('module')
                ->get();

            // Also get all active modules from the database as fallback definitions
            $allModules = Module::where('is_active', true)
                ->get()
                ->keyBy('key');

            return $branchModules->map(function (BranchModule $bm) use ($allModules) {
                // Prefer the related module, fallback to looking up by key, then to defaults
                $module = $bm->module ?? $allModules->get($bm->module_key);

                $moduleName = $module?->localized_name
                    ?? $module?->name
                    ?? ucfirst(str_replace(['_', '-'], ' ', $bm->module_key));

                return [
                    'key' => $bm->module_key,
                    'name' => (string) $moduleName,
                    'enabled' => (bool) $bm->is_enabled,
                ];
            })->all();
        });
    }

    public function isEnabled(string $key, ?int $branchId = null): bool
    {
        $mods = $this->allForBranch($branchId);

        foreach ($mods as $m) {
            if ($m['key'] === $key) {
                return $m['enabled'];
            }
        }

        return false;
    }

    public function ensureModule(string $key, array $attributes = []): Module
    {
        return $this->handleServiceOperation(
            callback: function () use ($key, $attributes) {
                /** @var Module $module */
                $module = Module::firstOrNew(['module_key' => $key]);

                $module->fill($attributes);
                $module->is_active = $module->is_active ?? true;
                $module->save();

                return $module;
            },
            operation: 'ensureModule',
            context: ['module_key' => $key, 'attributes' => $attributes]
        );
    }

    public function enableForBranch(Branch $branch, string $moduleKey, array $settings = []): void
    {
        $this->handleServiceOperation(
            callback: function () use ($branch, $moduleKey, $settings) {
                $module = Module::where('module_key', $moduleKey)->firstOrFail();

                $branch->modules()->syncWithoutDetaching([
                    $module->id => [
                        'module_key' => $moduleKey,
                        'enabled' => true,
                        'settings' => $settings,
                    ],
                ]);
            },
            operation: 'enableForBranch',
            context: ['branch_id' => $branch->id, 'module_key' => $moduleKey, 'settings' => $settings]
        );
    }

    public function disableForBranch(Branch $branch, string $moduleKey): void
    {
        $this->handleServiceOperation(
            callback: function () use ($branch, $moduleKey) {
                $module = Module::where('module_key', $moduleKey)->first();

                if (! $module) {
                    return;
                }

                /** @var BranchModule|null $pivot */
                $pivot = $branch->branchModules()
                    ->where('module_id', $module->id)
                    ->first();

                if ($pivot) {
                    $pivot->enabled = false;
                    $pivot->save();
                }
            },
            operation: 'disableForBranch',
            context: ['branch_id' => $branch->id, 'module_key' => $moduleKey]
        );
    }

    public function getBranchModulesConfig(Branch $branch): array
    {
        return $branch->branchModules()
            ->with('module')
            ->get()
            ->mapWithKeys(function (BranchModule $bm) {
                $moduleKey = $bm->module_key ?: $bm->module?->key;

                if (! $moduleKey) {
                    return [];
                }

                return [
                    $moduleKey => [
                        'enabled' => $bm->enabled,
                        'settings' => $bm->settings ?? [],
                    ],
                ];
            })
            ->all();
    }

    public function getModulesByType(string $type, ?int $branchId = null): array
    {
        $branchId = $branchId ?? request()->attributes->get('branch_id');

        return Cache::remember("modules:type:{$type}:b:{$branchId}", 600, function () use ($type, $branchId) {
            $modules = Module::byType($type)->active()->get();

            if (! $branchId) {
                return $modules->map(fn ($m) => $this->mapModuleToArray($m))->all();
            }

            $enabledKeys = BranchModule::where('branch_id', $branchId)
                ->where('enabled', true)
                ->with('module')
                ->get()
                ->map(fn (BranchModule $bm) => $bm->module_key ?: $bm->module?->key)
                ->filter()
                ->values()
                ->toArray();

            return $modules->filter(fn ($m) => in_array($m->key, $enabledKeys))
                ->map(fn ($m) => $this->mapModuleToArray($m))
                ->values()
                ->all();
        });
    }

    /**
     * Map module to array representation
     */
    protected function mapModuleToArray(Module $module): array
    {
        return [
            'id' => $module->id,
            'module_key' => $module->module_key,
            'name' => $module->localized_name,
            'type' => $module->module_type,
        ];
    }

    public function getNavigationForUser($user, ?int $branchId = null): array
    {
        $branchId = $branchId ?? request()->attributes->get('branch_id');

        // Get enabled modules for branch
        $enabledModuleIds = [];
        if ($branchId) {
            $enabledModuleIds = BranchModule::where('branch_id', $branchId)
                ->where('enabled', true)
                ->with('module')
                ->get()
                ->pluck('module_id')
                ->filter()
                ->toArray();
        } else {
            $enabledModuleIds = Module::active()->pluck('id')->toArray();
        }

        if (empty($enabledModuleIds)) {
            return [];
        }

        $navigation = \App\Models\ModuleNavigation::query()
            ->whereIn('module_id', $enabledModuleIds)
            ->active()
            ->rootItems()
            ->with(['children' => fn ($q) => $q->active()->ordered()])
            ->ordered()
            ->get();

        return $navigation->filter(fn ($nav) => $nav->userHasAccess($user, $branchId))
            ->map(function ($nav) use ($user, $branchId) {
                return $this->formatNavigationItem($nav, $user, $branchId);
            })
            ->values()
            ->all();
    }

    protected function formatNavigationItem($nav, $user, ?int $branchId): array
    {
        $children = $nav->children
            ->filter(fn ($child) => $child->userHasAccess($user, $branchId))
            ->map(fn ($child) => $this->formatNavigationItem($child, $user, $branchId))
            ->values()
            ->all();

        return [
            'id' => $nav->id,
            'key' => $nav->nav_key,
            'label' => $nav->localized_label,
            'route' => $nav->route_name,
            'icon' => $nav->icon,
            'children' => $children,
        ];
    }

    public function userCanPerformOperation($user, string $moduleKey, string $operationKey): bool
    {
        $module = Module::where('module_key', $moduleKey)->first();
        if (! $module) {
            return false;
        }

        $operation = \App\Models\ModuleOperation::query()
            ->where('module_id', $module->id)
            ->where('operation_key', $operationKey)
            ->active()
            ->first();

        if (! $operation) {
            return false;
        }

        return $operation->userCanExecute($user);
    }

    public function getActivePolicies(int $moduleId, ?int $branchId = null): array
    {
        return Cache::remember("module_policies:{$moduleId}:b:{$branchId}", 600, function () use ($moduleId, $branchId) {
            return \App\Models\ModulePolicy::query()
                ->forModule($moduleId)
                ->forBranch($branchId)
                ->active()
                ->ordered()
                ->get()
                ->map(fn ($policy) => [
                    'key' => $policy->policy_key,
                    'name' => $policy->policy_name,
                    'description' => $policy->policy_description,
                    'rules' => $policy->policy_rules,
                    'scope' => $policy->scope,
                ])
                ->all();
        });
    }
}
