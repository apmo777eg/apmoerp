<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Module;
use App\Models\ModuleNavigation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Service for automatic module registration with navigation
 */
class ModuleRegistrationService
{
    /**
     * Register a new module with automatic navigation setup
     */
    public function registerModule(array $moduleData): Module
    {
        return DB::transaction(function () use ($moduleData) {
            // Create or update module
            $module = Module::updateOrCreate(
                ['module_key' => $moduleData['module_key']],
                [
                    'slug' => $moduleData['slug'] ?? str($moduleData['module_key'])->slug(),
                    'name' => $moduleData['name'],
                    'name_ar' => $moduleData['name_ar'] ?? $moduleData['name'],
                    'description' => $moduleData['description'] ?? null,
                    'description_ar' => $moduleData['description_ar'] ?? null,
                    'icon' => $moduleData['icon'] ?? '📦',
                    'color' => $moduleData['color'] ?? '#3b82f6',
                    'is_core' => $moduleData['is_core'] ?? false,
                    'is_active' => $moduleData['is_active'] ?? true,
                    'category' => $moduleData['category'] ?? 'general',
                    'module_type' => $moduleData['module_type'] ?? 'data',
                    'sort_order' => $moduleData['sort_order'] ?? 999,
                    'supports_reporting' => $moduleData['supports_reporting'] ?? true,
                    'supports_custom_fields' => $moduleData['supports_custom_fields'] ?? true,
                    'supports_items' => $moduleData['supports_items'] ?? false,
                ]
            );

            // Create navigation items if provided
            if (isset($moduleData['navigation'])) {
                $this->registerNavigation($module, $moduleData['navigation']);
            }

            // Clear caches
            $this->clearModuleCaches();

            return $module;
        });
    }

    /**
     * Register navigation items for a module
     */
    public function registerNavigation(Module $module, array $navigationData, ?int $parentId = null): void
    {
        foreach ($navigationData as $navItem) {
            $children = $navItem['children'] ?? [];
            unset($navItem['children']);

            $navigation = ModuleNavigation::updateOrCreate(
                [
                    'module_id' => $module->id,
                    'nav_key' => $navItem['nav_key'],
                ],
                [
                    'parent_id' => $parentId,
                    'nav_label' => $navItem['nav_label'],
                    'nav_label_ar' => $navItem['nav_label_ar'] ?? $navItem['nav_label'],
                    'route_name' => $navItem['route_name'] ?? null,
                    'icon' => $navItem['icon'] ?? '📄',
                    'required_permissions' => $navItem['required_permissions'] ?? [],
                    'visibility_conditions' => $navItem['visibility_conditions'] ?? [],
                    'is_active' => $navItem['is_active'] ?? true,
                    'sort_order' => $navItem['sort_order'] ?? 999,
                ]
            );

            // Register children recursively
            if (! empty($children)) {
                $this->registerNavigation($module, $children, $navigation->id);
            }
        }
    }

    /**
     * Unregister a module and its navigation
     */
    public function unregisterModule(string $moduleKey): bool
    {
        return DB::transaction(function () use ($moduleKey) {
            $module = Module::where('module_key', $moduleKey)->first();

            if (! $module) {
                return false;
            }

            // Delete navigation items
            ModuleNavigation::where('module_id', $module->id)->delete();

            // Deactivate module (don't delete to preserve data)
            $module->is_active = false;
            $module->save();

            // Clear caches
            $this->clearModuleCaches();

            return true;
        });
    }

    /**
     * Activate a module
     */
    public function activateModule(string $moduleKey): bool
    {
        $module = Module::where('module_key', $moduleKey)->first();

        if (! $module) {
            return false;
        }

        $module->is_active = true;
        $module->save();

        $this->clearModuleCaches();

        return true;
    }

    /**
     * Deactivate a module
     */
    public function deactivateModule(string $moduleKey): bool
    {
        $module = Module::where('module_key', $moduleKey)->first();

        if (! $module) {
            return false;
        }

        $module->is_active = false;
        $module->save();

        $this->clearModuleCaches();

        return true;
    }

    /**
     * Get module registration template
     */
    public function getRegistrationTemplate(): array
    {
        return [
            'key' => 'example_module',
            'slug' => 'example-module',
            'name' => 'Example Module',
            'name_ar' => 'موديول تجريبي',
            'description' => 'An example module for demonstration',
            'description_ar' => 'موديول تجريبي للعرض',
            'icon' => '📦',
            'color' => '#3b82f6',
            'is_core' => false,
            'is_active' => true,
            'category' => 'general',
            'module_type' => 'data',
            'sort_order' => 100,
            'supports_reporting' => true,
            'supports_custom_fields' => true,
            'supports_items' => false,
            'navigation' => [
                [
                    'nav_key' => 'example_module',
                    'nav_label' => 'Example Module',
                    'nav_label_ar' => 'موديول تجريبي',
                    'route_name' => 'app.example.index',
                    'icon' => '📦',
                    'required_permissions' => ['example.view'],
                    'is_active' => true,
                    'sort_order' => 100,
                    'children' => [
                        [
                            'nav_key' => 'example_list',
                            'nav_label' => 'List',
                            'nav_label_ar' => 'القائمة',
                            'route_name' => 'app.example.index',
                            'icon' => '📋',
                            'required_permissions' => ['example.view'],
                            'is_active' => true,
                            'sort_order' => 10,
                        ],
                        [
                            'nav_key' => 'example_create',
                            'nav_label' => 'Create New',
                            'nav_label_ar' => 'إضافة جديد',
                            'route_name' => 'app.example.create',
                            'icon' => '➕',
                            'required_permissions' => ['example.create'],
                            'is_active' => true,
                            'sort_order' => 20,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Clear module-related caches
     */
    protected function clearModuleCaches(): void
    {
        Cache::tags(['modules', 'navigation'])->flush();
        Cache::forget('modules_list');
    }

    /**
     * Validate module registration data
     */
    public function validateModuleData(array $moduleData): array
    {
        $errors = [];

        // Required fields
        $required = ['key', 'name'];
        foreach ($required as $field) {
            if (empty($moduleData[$field])) {
                $errors[] = "Field '{$field}' is required";
            }
        }

        // Validate key format
        if (isset($moduleData['key']) && ! preg_match('/^[a-z_]+$/', $moduleData['key'])) {
            $errors[] = 'Module key must contain only lowercase letters and underscores';
        }

        // Validate navigation structure
        if (isset($moduleData['navigation'])) {
            $navErrors = $this->validateNavigationStructure($moduleData['navigation']);
            $errors = array_merge($errors, $navErrors);
        }

        return $errors;
    }

    /**
     * Validate navigation structure
     */
    protected function validateNavigationStructure(array $navigation, string $path = ''): array
    {
        $errors = [];

        foreach ($navigation as $index => $navItem) {
            $currentPath = $path ? "{$path}.{$index}" : (string) $index;

            if (empty($navItem['nav_key'])) {
                $errors[] = "Navigation item at {$currentPath} is missing 'nav_key'";
            }

            if (empty($navItem['nav_label'])) {
                $errors[] = "Navigation item at {$currentPath} is missing 'nav_label'";
            }

            // Validate children
            if (isset($navItem['children']) && is_array($navItem['children'])) {
                $childErrors = $this->validateNavigationStructure(
                    $navItem['children'],
                    "{$currentPath}.children"
                );
                $errors = array_merge($errors, $childErrors);
            }
        }

        return $errors;
    }

    /**
     * Get all registered modules with navigation
     */
    public function getAllModulesWithNavigation(): array
    {
        return Module::active()
            ->with(['navigation' => function ($query) {
                $query->active()->rootItems()->ordered()->with(['children' => function ($q) {
                    $q->active()->ordered();
                }]);
            }])
            ->ordered()
            ->get()
            ->map(function ($module) {
                return [
                    'id' => $module->id,
                    'module_key' => $module->module_key,
                    'name' => $module->localized_name,
                    'description' => $module->localized_description,
                    'icon' => $module->icon,
                    'color' => $module->color,
                    'category' => $module->category,
                    'is_active' => $module->is_active,
                    'navigation' => $module->navigation->map(function ($nav) {
                        return $this->formatNavigationForExport($nav);
                    })->all(),
                ];
            })
            ->all();
    }

    /**
     * Format navigation for export
     */
    protected function formatNavigationForExport(ModuleNavigation $nav): array
    {
        return [
            'id' => $nav->id,
            'key' => $nav->nav_key,
            'label' => $nav->nav_label,
            'label_ar' => $nav->nav_label_ar,
            'route' => $nav->route_name,
            'icon' => $nav->icon,
            'permissions' => $nav->required_permissions,
            'children' => $nav->children->map(function ($child) {
                return $this->formatNavigationForExport($child);
            })->all(),
        ];
    }
}
