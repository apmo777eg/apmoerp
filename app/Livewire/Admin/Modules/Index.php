<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Modules;

use App\Models\Module;
use App\Services\ModuleRegistrationService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use App\Livewire\BaseComponent as Component;
use Livewire\WithPagination;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Index extends Component
{
    use AuthorizesRequests, WithPagination;
    #[Url]
    public string $search = '';

    public string $sortField = 'sort_order';

    public string $sortDirection = 'asc';

    public string $filterCategory = '';

    public string $filterStatus = '';

    public array $selectedModules = [];

    public bool $showSuggestions = false;

    public array $suggestions = [];

    public function mount(): void
    {
        // Authorization check - must have modules.manage permission
        $user = auth()->user();
        if (! $user || ! $user->can('modules.manage')) {
            abort(403, __('Unauthorized access to module management'));
        }

        $this->loadSuggestions();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function toggleActive(int $id): void
    {
        $this->authorize('modules.manage');

        $module = Module::findOrFail($id);

        if ($module->is_core) {
            session()->flash('error', __('Cannot deactivate core module'));

            return;
        }

        $module->update(['is_active' => ! $module->is_active]);
        session()->flash('success', __('Module status updated'));
    }

    public function bulkActivate(): void
    {
        $this->authorize('modules.manage');

        Module::whereIn('id', $this->selectedModules)
            ->where('is_core', false)
            ->update(['is_active' => true]);

        $this->selectedModules = [];
        session()->flash('success', __('Selected modules activated'));
    }

    public function bulkDeactivate(): void
    {
        $this->authorize('modules.manage');

        Module::whereIn('id', $this->selectedModules)
            ->where('is_core', false)
            ->update(['is_active' => false]);

        $this->selectedModules = [];
        session()->flash('success', __('Selected modules deactivated'));
    }

    public function getModuleHealth(int $moduleId): array
    {
        $module = Module::with('navigation')->findOrFail($moduleId);

        $score = 0;
        $issues = [];

        // Check navigation (30 points)
        if ($module->navigation()->count() > 0) {
            $score += 30;
        } else {
            $issues[] = __('No navigation items');
        }

        // Check description (20 points)
        if ($module->description) {
            $score += 20;
        } else {
            $issues[] = __('Missing description');
        }

        // Check icon (10 points)
        if ($module->icon) {
            $score += 10;
        }

        // Check if used by branches (40 points)
        $branchCount = $module->branches()->count();
        if ($branchCount > 0) {
            $score += min(40, $branchCount * 10);
        } else {
            $issues[] = __('Not used by any branch');
        }

        return [
            'score' => $score,
            'issues' => $issues,
            'status' => $score >= 80 ? 'excellent' : ($score >= 60 ? 'good' : 'needs_attention'),
        ];
    }

    protected function loadSuggestions(): void
    {
        $existingModules = Module::pluck('module_key')->toArray();

        $allSuggestions = [
            [
                'key' => 'ecommerce',
                'name' => 'E-Commerce',
                'name_ar' => 'التجارة الإلكترونية',
                'description' => 'Online store management with products, cart, and checkout',
                'icon' => '🛒',
                'reason' => 'Expand sales channels online',
                'priority' => 'high',
            ],
            [
                'key' => 'crm',
                'name' => 'CRM',
                'name_ar' => 'إدارة علاقات العملاء',
                'description' => 'Advanced customer relationship management',
                'icon' => '🤝',
                'reason' => 'Better customer engagement',
                'priority' => 'high',
            ],
            [
                'key' => 'marketing',
                'name' => 'Marketing',
                'name_ar' => 'التسويق',
                'description' => 'Campaigns, email marketing, and promotions',
                'icon' => '📢',
                'reason' => 'Drive more sales',
                'priority' => 'medium',
            ],
            [
                'key' => 'shipping',
                'name' => 'Shipping',
                'name_ar' => 'الشحن',
                'description' => 'Shipping management and tracking',
                'icon' => '📦',
                'reason' => 'Streamline deliveries',
                'priority' => 'medium',
            ],
            [
                'key' => 'loyalty',
                'name' => 'Loyalty Program',
                'name_ar' => 'برنامج الولاء',
                'description' => 'Customer loyalty and rewards',
                'icon' => '🎁',
                'reason' => 'Retain customers',
                'priority' => 'low',
            ],
        ];

        $this->suggestions = array_filter($allSuggestions, fn ($s) => ! in_array($s['key'], $existingModules));
    }

    public function acceptSuggestion(string $key): void
    {
        $this->authorize('modules.manage');

        $suggestion = collect($this->suggestions)->firstWhere('key', $key);

        if ($suggestion) {
            $service = app(ModuleRegistrationService::class);

            try {
                $service->registerModule([
                    'module_key' => $suggestion['key'],
                    'slug' => str($suggestion['key'])->slug(),
                    'name' => $suggestion['name'],
                    'name_ar' => $suggestion['name_ar'],
                    'description' => $suggestion['description'],
                    'icon' => $suggestion['icon'],
                    'is_active' => true,
                    'category' => 'general',
                    'sort_order' => 999,
                ]);

                $this->loadSuggestions();
                session()->flash('success', __('Module created successfully'));
            } catch (\Exception $e) {
                session()->flash('error', __('Failed to create module: ').$e->getMessage());
            }
        }
    }

    public function render()
    {
        $modules = Module::query()
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('module_key', 'like', "%{$this->search}%")
                ->orWhere('name_ar', 'like', "%{$this->search}%"))
            ->when($this->filterCategory, fn ($q) => $q->where('category', $this->filterCategory))
            ->when($this->filterStatus !== '', fn ($q) => $q->where('is_active', $this->filterStatus === 'active'))
            ->withCount('branches', 'navigation')
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);

        // Calculate health scores
        $modulesWithHealth = $modules->through(function ($module) {
            $health = $this->getModuleHealth($module->id);
            $module->health_score = $health['score'];
            $module->health_status = $health['status'];
            $module->health_issues = $health['issues'];

            return $module;
        });

        return view('livewire.admin.modules.index', [
            'modules' => $modulesWithHealth,
            'suggestions' => $this->suggestions,
        ])->layout('layouts.app', ['title' => __('Module Management')]);
    }
}
