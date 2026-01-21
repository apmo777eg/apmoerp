<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Modules;

use App\Models\Module;
use App\Services\ModuleRegistrationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ModuleManager extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';

    public string $filterCategory = '';

    public string $filterStatus = '';

    public bool $showRegistrationModal = false;

    public bool $showActivationModal = false;

    public ?int $selectedModuleId = null;

    // Form fields
    public string $formKey = '';

    public string $formName = '';

    public string $formNameAr = '';

    public string $formDescription = '';

    public string $formIcon = '📦';

    public string $formColor = '#3b82f6';

    public string $formCategory = 'general';

    public bool $formIsActive = true;

    public bool $formSupportsReporting = true;

    public bool $formSupportsCustomFields = true;

    public bool $formSupportsItems = false;
    
    public function mount(): void
    {
        // V57-HIGH-01 FIX: Add authorization for modules management
        $user = Auth::user();
        if (! $user || ! $user->can('modules.manage')) {
            abort(403);
        }
    }

    #[Computed]
    public function modules()
    {
        return Module::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('name_ar', 'like', '%'.$this->search.'%')
                        ->orWhere('key', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->filterCategory, fn ($q) => $q->where('category', $this->filterCategory))
            ->when($this->filterStatus !== '', fn ($q) => $q->where('is_active', $this->filterStatus === 'active'))
            ->with(['navigation' => fn ($q) => $q->rootItems()->ordered()])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20);
    }

    #[Computed]
    public function categories(): array
    {
        return [
            'general' => __('General'),
            'sales' => __('Sales'),
            'inventory' => __('Inventory'),
            'financial' => __('Financial'),
            'hr' => __('Human Resources'),
            'operations' => __('Operations'),
            'admin' => __('Administration'),
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterCategory(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function openRegistrationModal(): void
    {
        $this->resetForm();
        $this->showRegistrationModal = true;
    }

    public function closeRegistrationModal(): void
    {
        $this->showRegistrationModal = false;
        $this->resetForm();
    }

    public function registerModule(ModuleRegistrationService $service): void
    {
        $this->validate([
            'formKey' => 'required|string|regex:/^[a-z_]+$/|unique:modules,key',
            'formName' => 'required|string|max:255',
            'formNameAr' => 'required|string|max:255',
            'formIcon' => 'required|string|max:10',
            'formColor' => 'required|string|max:20',
            'formCategory' => 'required|string|in:general,sales,inventory,financial,hr,operations,admin',
        ], [
            'formKey.regex' => 'Module key must contain only lowercase letters and underscores',
        ]);

        try {
            $moduleData = [
                'key' => $this->formKey,
                'name' => $this->formName,
                'name_ar' => $this->formNameAr,
                'description' => $this->formDescription ?: null,
                'icon' => $this->formIcon,
                'color' => $this->formColor,
                'category' => $this->formCategory,
                'is_core' => false,
                'is_active' => $this->formIsActive,
                'module_type' => 'data',
                'sort_order' => 999,
                'supports_reporting' => $this->formSupportsReporting,
                'supports_custom_fields' => $this->formSupportsCustomFields,
                'supports_items' => $this->formSupportsItems,
            ];

            $module = $service->registerModule($moduleData);

            $this->dispatch('module-registered', moduleId: $module->id);
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => __('Module registered successfully!'),
            ]);

            $this->closeRegistrationModal();
            $this->resetPage();
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => __('Failed to register module: ').$e->getMessage(),
            ]);
        }
    }

    public function toggleModuleStatus(int $moduleId, ModuleRegistrationService $service): void
    {
        try {
            $module = Module::findOrFail($moduleId);
            $newStatus = ! $module->is_active;

            if ($newStatus) {
                $service->activateModule($module->key);
                $message = __('Module activated successfully');
            } else {
                $service->deactivateModule($module->key);
                $message = __('Module deactivated successfully');
            }

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => $message,
            ]);

            $this->resetPage();
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => __('Failed to update module status'),
            ]);
        }
    }

    public function deleteModule(int $moduleId, ModuleRegistrationService $service): void
    {
        try {
            $module = Module::findOrFail($moduleId);

            if ($module->is_core) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => __('Cannot delete core modules'),
                ]);

                return;
            }

            $service->unregisterModule($module->key);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => __('Module unregistered successfully'),
            ]);

            $this->resetPage();
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => __('Failed to delete module'),
            ]);
        }
    }

    protected function resetForm(): void
    {
        $this->formKey = '';
        $this->formName = '';
        $this->formNameAr = '';
        $this->formDescription = '';
        $this->formIcon = '📦';
        $this->formColor = '#3b82f6';
        $this->formCategory = 'general';
        $this->formIsActive = true;
        $this->formSupportsReporting = true;
        $this->formSupportsCustomFields = true;
        $this->formSupportsItems = false;
    }

    public function render()
    {
        return view('livewire.admin.modules.module-manager')->layout('layouts.app');
    }
}
