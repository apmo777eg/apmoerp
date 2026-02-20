<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Reports;

use App\Models\ReportTemplate;
use App\Models\ScheduledReport;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Layout;
use App\Livewire\BaseComponent as Component;
use Livewire\WithPagination;

class ScheduledReportsManager extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public ?int $editingId = null;

    public ?int $userId = null;

    public ?int $templateId = null;

    public string $routeName = '';

    public string $cronExpression = '0 8 * * *';

    public ?string $recipientEmail = null;

    public string $filtersJson = '{}';

    /**
     * UI helper for super admins: pick a branch from dropdown instead of typing branch_id in JSON.
     * null = all branches (no branch filter).
     */
    public ?int $filterBranchId = null;

    // Simplified schedule options
    public string $frequency = 'daily';

    public string $timeOfDay = '08:00';

    public string $dayOfWeek = '1';

    public string $dayOfMonth = '1';

    public bool $showAdvanced = false;

    /**
     * Determine if current user can schedule reports for multiple branches.
     */
    protected function canSelectAnyBranch(): bool
    {
        $user = Auth::user();

        return (bool) ($user && ($user->hasRole('Super Admin') || $user->can('branches.view-all')));
    }

    /**
     * Branch context (if selected) is the safest default for scheduled reports.
     */
    protected function defaultBranchId(): ?int
    {
        $branchId = current_branch_id();

        if ($branchId) {
            return (int) $branchId;
        }

        $user = Auth::user();

        return $user?->branch_id ? (int) $user->branch_id : null;
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $user = Auth::user();

        if (! $user || ! $user->can('reports.scheduled.manage')) {
            abort(403);
        }

        $reports = ScheduledReport::query()
            ->with('user', 'template')
            ->orderByDesc('id')
            ->paginate(25);

        return view('livewire.admin.reports.scheduled-manager', [
            'reports' => $reports,
            'availableRoutes' => $this->availableRoutes,
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'templates' => $this->templates,
            'canSelectBranch' => $this->canSelectAnyBranch(),
            'branches' => $this->canSelectAnyBranch()
                ? Branch::query()->active()->orderBy('name')->get(['id', 'name'])
                : collect(),
            // Used for displaying branch names in the listing table
            'branchNames' => Branch::query()->pluck('name', 'id')->toArray(),
        ]);
    }

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user || ! $user->can('reports.scheduled.manage')) {
            abort(403);
        }

        $this->userId = $user->id;
        $this->recipientEmail = $user->email;
        $this->cronExpression = '0 8 * * *';
        $this->filtersJson = '{}';
        $this->frequency = 'daily';
        $this->timeOfDay = '08:00';
        $this->filterBranchId = $this->defaultBranchId();
    }

    protected function rules(): array
    {
        return [
            'userId' => ['nullable', 'integer'],
            'templateId' => ['required', 'integer', 'exists:report_templates,id'],
            'routeName' => ['nullable', 'string', 'max:191'],
            'cronExpression' => ['required', 'string', 'max:191'],
            'recipientEmail' => ['nullable', 'email', 'max:191'],
            'filtersJson' => ['nullable', 'string'],
            'filterBranchId' => ['nullable', 'integer', 'exists:branches,id'],
        ];
    }

    /**
     * Always enforce a safe branch scope for scheduled reports.
     * - Non-super-admins: forced to their current branch.
     * - Super-admins: can select a branch (or leave empty for all branches).
     */
    protected function applyBranchFilter(array $filters): array
    {
        if ($this->canSelectAnyBranch()) {
            if ($this->filterBranchId) {
                $filters['branch_id'] = (int) $this->filterBranchId;
            } else {
                unset($filters['branch_id']);
            }

            return $filters;
        }

        $branchId = $this->defaultBranchId();

        if (! $branchId) {
            abort(403, __('User must be assigned to a branch to perform this action'));
        }

        $filters['branch_id'] = (int) $branchId;

        return $filters;
    }

    public function createNew(): void
    {
        $this->reset(['editingId', 'routeName', 'cronExpression', 'recipientEmail', 'filtersJson', 'templateId', 'filterBranchId']);
        $this->cronExpression = '0 8 * * *';
        $this->filtersJson = '{}';
        $this->frequency = 'daily';
        $this->timeOfDay = '08:00';
        $this->dayOfWeek = '1';
        $this->dayOfMonth = '1';
        $this->showAdvanced = false;
        $this->recipientEmail = Auth::user()?->email;
        $this->filterBranchId = $this->defaultBranchId();
    }

    public function updatedFrequency(): void
    {
        $this->cronExpression = $this->buildCronExpression();
    }

    public function updatedTimeOfDay(): void
    {
        $this->cronExpression = $this->buildCronExpression();
    }

    public function updatedDayOfWeek(): void
    {
        $this->cronExpression = $this->buildCronExpression();
    }

    public function updatedDayOfMonth(): void
    {
        $this->cronExpression = $this->buildCronExpression();
    }

    protected function buildCronExpression(): string
    {
        $parts = explode(':', $this->timeOfDay);
        $hour = (int) ($parts[0] ?? 8);
        $minute = (int) ($parts[1] ?? 0);

        return match ($this->frequency) {
            'daily' => "{$minute} {$hour} * * *",
            'weekly' => "{$minute} {$hour} * * {$this->dayOfWeek}",
            'monthly' => "{$minute} {$hour} {$this->dayOfMonth} * *",
            'quarterly' => "{$minute} {$hour} {$this->dayOfMonth} */3 *",
            default => "{$minute} {$hour} * * *",
        };
    }

    protected function parseCronExpression(): void
    {
        // Parse cron expression back to frequency options
        $parts = explode(' ', $this->cronExpression);
        if (count($parts) < 5) {
            return;
        }

        $minute = $parts[0];
        $hour = $parts[1];
        $dayOfMonth = $parts[2];
        $month = $parts[3];
        $dayOfWeek = $parts[4];

        $this->timeOfDay = sprintf('%02d:%02d', (int) $hour, (int) $minute);

        if ($dayOfWeek !== '*' && $dayOfMonth === '*') {
            $this->frequency = 'weekly';
            $this->dayOfWeek = $dayOfWeek;
        } elseif ($dayOfMonth !== '*' && $month === '*/3') {
            $this->frequency = 'quarterly';
            $this->dayOfMonth = $dayOfMonth;
        } elseif ($dayOfMonth !== '*') {
            $this->frequency = 'monthly';
            $this->dayOfMonth = $dayOfMonth;
        } else {
            $this->frequency = 'daily';
        }
    }

    public function edit(int $id): void
    {
        $report = ScheduledReport::query()->findOrFail($id);

        $this->editingId = $report->id;
        $this->userId = $report->user_id;
        $this->routeName = $report->route_name;
        $this->cronExpression = $report->cron_expression;
        $this->recipientEmail = $report->recipient_email;
        $filtersArray = $report->filters ?? [];
        $this->filtersJson = json_encode($filtersArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $this->templateId = $report->report_template_id;
        $this->showAdvanced = ! empty($report->filters);

        // UX: show branch dropdown instead of asking admin to type branch_id manually
        $this->filterBranchId = isset($filtersArray['branch_id']) ? (int) $filtersArray['branch_id'] : $this->defaultBranchId();

        // Parse the cron expression to set frequency fields
        $this->parseCronExpression();

        if (! $this->templateId && $report->route_name) {
            foreach ($this->templates as $template) {
                if (($template['route_name'] ?? null) === $report->route_name) {
                    $this->templateId = $template['id'];
                    break;
                }
            }
        }
    }

    public function save(): void
    {
        $this->validate();

        $filters = [];

        if (trim($this->filtersJson) !== '') {
            try {
                $decoded = json_decode($this->filtersJson, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($decoded)) {
                    $filters = $decoded;
                }
            } catch (\Throwable $e) {
                $this->addError('filtersJson', __('Filters must be valid JSON.'));

                return;
            }
        }

        $template = null;
        if ($this->templateId) {
            $template = ReportTemplate::query()->whereKey($this->templateId)->where('is_active', true)->first();
        }

        if ($template) {
            $this->routeName = $template->route_name;

            $defaultFilters = $template->default_filters ?? [];
            if (is_array($defaultFilters) && ! empty($defaultFilters)) {
                $filters = array_merge($defaultFilters, $filters);
            }
        }

        // UX + Security: branch dropdown -> filters['branch_id']
        $filters = $this->applyBranchFilter($filters);

        $userId = $this->userId ?: Auth::id();

        ScheduledReport::query()->updateOrCreate(
            ['id' => $this->editingId],
            [
                'user_id' => $userId,
                'report_template_id' => $template?->id,
                'route_name' => $this->routeName,
                'cron_expression' => $this->cronExpression,
                'recipient_email' => $this->recipientEmail,
                'filters' => $filters,
            ]
        );

        $this->dispatch('saved');

        $this->createNew();
    }

    public function delete(int $id): void
    {
        $user = Auth::user();

        if (! $user || ! $user->can('reports.scheduled.manage')) {
            abort(403);
        }

        ScheduledReport::query()->whereKey($id)->delete();
    }

    public function applyTemplate(): void
    {
        if (! $this->templateId) {
            return;
        }

        $template = ReportTemplate::query()->whereKey($this->templateId)->where('is_active', true)->first();

        if (! $template) {
            return;
        }

        $this->routeName = $template->route_name;

        $defaults = $template->default_filters ?? [];
        $this->filtersJson = json_encode($defaults, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        // If template already includes a branch filter, reflect it in the dropdown.
        if (is_array($defaults) && isset($defaults['branch_id'])) {
            $this->filterBranchId = (int) $defaults['branch_id'];
        } else {
            $this->filterBranchId = $this->defaultBranchId();
        }
    }

    public function getAvailableRoutesProperty(): array
    {
        $routes = collect(Route::getRoutes())
            ->filter(static function ($route): bool {
                return in_array('GET', $route->methods(), true) && $route->getName();
            })
            ->map(static function ($route): array {
                return [
                    'name' => $route->getName(),
                    'uri' => $route->uri(),
                ];
            })
            ->filter(static function (array $route): bool {
                return str_contains($route['name'], 'report');
            })
            ->sortBy('name')
            ->values()
            ->all();

        return $routes;
    }

    public function getTemplatesProperty(): array
    {
        return ReportTemplate::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'description', 'route_name', 'output_type'])
            ->toArray();
    }

    /**
     * Format a cron expression to human-readable text.
     */
    public static function formatCronExpression(string $cronExpression): string
    {
        $parts = explode(' ', $cronExpression);
        if (count($parts) < 5) {
            return $cronExpression;
        }

        $minute = $parts[0];
        $hour = $parts[1];
        $dayOfMonth = $parts[2];
        $dayOfWeek = $parts[4];

        $time = sprintf('%02d:%02d', (int) $hour, (int) $minute);
        $days = [__('Sunday'), __('Monday'), __('Tuesday'), __('Wednesday'), __('Thursday'), __('Friday'), __('Saturday')];

        if ($dayOfWeek !== '*' && $dayOfMonth === '*') {
            return __('Weekly').' - '.($days[(int) $dayOfWeek] ?? '').' '.$time;
        } elseif ($dayOfMonth !== '*') {
            return __('Monthly').' - '.__('Day').' '.$dayOfMonth.' '.$time;
        }

        return __('Daily').' - '.$time;
    }
}
