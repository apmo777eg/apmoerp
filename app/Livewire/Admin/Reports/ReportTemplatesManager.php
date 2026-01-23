<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Reports;

use App\Models\ReportTemplate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class ReportTemplatesManager extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public ?int $editingId = null;

    public string $search = '';

    public string $key = '';

    public string $name = '';

    public ?string $description = null;

    public string $routeName = '';

    public string $defaultFiltersJson = '{}';

    public string $outputType = 'html';

    public string $exportColumnsText = '';

    public bool $isActive = true;

    public bool $showAdvanced = false;

    public bool $overrideKey = false;

    #[Layout('layouts.app')]
    public function render()
    {
        $user = Auth::user();

        if (! $user || ! $user->can('reports.templates.manage')) {
            abort(403);
        }

        $query = ReportTemplate::query();

        if (trim($this->search) !== '') {
            $term = '%'.trim($this->search).'%';
            $query->where(function ($q) use ($term): void {
                $q->where('template_key', 'like', $term)
                    ->orWhere('name', 'like', $term)
                    ->orWhere('description', 'like', $term)
                    ->orWhere('route_name', 'like', $term);
            });
        }

        $templates = $query->orderBy('name')->paginate(20);

        return view('livewire.admin.reports.templates-manager', [
            'templates' => $templates,
            'availableRoutes' => $this->availableRoutes,
            'outputTypes' => ['html', 'xlsx', 'pdf'],
        ]);
    }

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user || ! $user->can('reports.templates.manage')) {
            abort(403);
        }

        $this->resetForm();
    }

    protected function rules(): array
    {
        return [
            'key' => [
                'required',
                'string',
                'max:100',
                Rule::unique('report_templates', 'key')->ignore($this->editingId),
            ],
            'name' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:255'],
            'routeName' => ['required', 'string', 'max:191'],
            'defaultFiltersJson' => ['nullable', 'string'],
            'outputType' => ['required', 'string', Rule::in(['html', 'xlsx', 'pdf'])],
            'exportColumnsText' => ['nullable', 'string', 'max:1000'],
            'isActive' => ['boolean'],
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedName(): void
    {
        // Auto-generate key from name if not overriding
        if (! $this->overrideKey && ! $this->editingId) {
            $this->key = $this->generateKeyFromName($this->name);
        }
    }

    protected function generateKeyFromName(string $name): string
    {
        $base = Str::slug($name, '_');
        $key = $base;
        $counter = 1;

        while (ReportTemplate::where('template_key', $key)->where('id', '!=', $this->editingId)->exists()) {
            $key = $base.'_'.$counter;
            $counter++;
        }

        return $key;
    }

    public function createNew(): void
    {
        $this->editingId = null;
        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $template = ReportTemplate::query()->findOrFail($id);

        $this->editingId = $template->id;
        $this->key = $template->template_key;
        $this->name = $template->name;
        $this->description = $template->description;
        $this->routeName = $template->route_name;
        $this->defaultFiltersJson = json_encode($template->default_filters ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $this->outputType = $template->output_type ?? 'html';
        $this->exportColumnsText = is_array($template->export_columns) ? implode(',', $template->export_columns) : '';
        $this->isActive = (bool) $template->is_active;
        $this->overrideKey = true; // When editing, key is already set
        $this->showAdvanced = ! empty($template->default_filters) || ! empty($template->export_columns);
    }

    public function save(): void
    {
        $this->validate();

        $defaultFilters = [];
        if (trim($this->defaultFiltersJson) !== '') {
            try {
                $decoded = json_decode($this->defaultFiltersJson, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($decoded)) {
                    $defaultFilters = $decoded;
                }
            } catch (\Throwable $e) {
                $this->addError('defaultFiltersJson', __('Filters must be valid JSON.'));

                return;
            }
        }

        $exportColumns = [];
        if (trim($this->exportColumnsText) !== '') {
            $parts = preg_split('/[,\s]+/', $this->exportColumnsText);
            foreach ($parts as $col) {
                $col = trim((string) $col);
                if ($col !== '') {
                    $exportColumns[] = $col;
                }
            }
            $exportColumns = array_values(array_unique($exportColumns));
        }

        ReportTemplate::query()->updateOrCreate(
            ['id' => $this->editingId],
            [
                'template_key' => $this->key,
                'name' => $this->name,
                'description' => $this->description,
                'route_name' => $this->routeName,
                'default_filters' => $defaultFilters,
                'output_type' => $this->outputType,
                'export_columns' => $exportColumns ?: null,
                'is_active' => $this->isActive,
            ]
        );

        $this->dispatch('template-saved');

        $this->createNew();
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        ReportTemplate::query()->whereKey($id)->delete();
        $this->resetPage();
    }

    protected function resetForm(): void
    {
        $this->key = '';
        $this->name = '';
        $this->description = null;
        $this->routeName = '';
        $this->defaultFiltersJson = '{}';
        $this->outputType = 'html';
        $this->exportColumnsText = '';
        $this->isActive = true;
        $this->showAdvanced = false;
        $this->overrideKey = false;
    }

    public function getAvailableRoutesProperty(): array
    {
        $routes = collect(Route::getRoutes())
            ->filter(static function ($route): bool {
                return in_array('GET', $route->methods(), true) && $route->getName();
            })
            ->map(static function ($route): array {
                $name = $route->getName();
                // Generate human-readable label
                $label = self::getRouteLabel($name);

                return [
                    'name' => $name,
                    'uri' => $route->uri(),
                    'label' => $label,
                ];
            })
            ->filter(static function (array $route): bool {
                $name = $route['name'];

                return str_contains($name, 'report')
                    || str_contains($name, 'store')
                    || str_contains($name, 'pos')
                    || str_contains($name, 'inventory');
            })
            ->sortBy('label')
            ->values()
            ->all();

        return $routes;
    }

    protected static function getRouteLabel(string $routeName): string
    {
        // Map common route names to human-readable labels
        $labels = [
            'admin.reports.index' => __('General Reports'),
            'admin.reports.module' => __('Module Reports'),
            'admin.reports.scheduled' => __('Scheduled Reports'),
            'admin.reports.templates' => __('Report Templates'),
            'admin.reports.inventory' => __('Inventory Reports'),
            'admin.reports.pos' => __('POS Reports'),
            'pos.daily.report' => __('Daily POS Report'),
            'pos.terminal' => __('POS Terminal'),
            'inventory.products.index' => __('Products List'),
            'app.inventory.products.index' => __('Products List'),
            'store.reports.dashboard' => __('Store Dashboard'),
        ];

        if (isset($labels[$routeName])) {
            return $labels[$routeName];
        }

        // Generate label from route name
        $parts = explode('.', $routeName);
        $label = collect($parts)
            ->map(fn ($part) => ucfirst(str_replace(['-', '_'], ' ', $part)))
            ->join(' › ');

        return $label;
    }
}
