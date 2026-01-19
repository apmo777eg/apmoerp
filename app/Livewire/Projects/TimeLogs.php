<?php

declare(strict_types=1);

namespace App\Livewire\Projects;

use App\Models\Project;
use App\Models\ProjectTimeLog;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class TimeLogs extends Component
{
    use AuthorizesRequests, WithPagination;

    public Project $project;

    public ?ProjectTimeLog $editingLog = null;

    public bool $showModal = false;

    // Form fields
    public ?int $task_id = null;

    public ?int $employee_id = null;

    public ?string $date = null;

    public float $hours = 0;

    public bool $is_billable = true;

    public float $hourly_rate = 0;

    public ?string $description = null;

    public function mount(int $projectId): void
    {
        $this->authorize('projects.timelogs.view');
        // BUG-003 FIX: Scope project access to user's branches
        $this->project = Project::query()
            ->forUserBranches(auth()->user())
            ->findOrFail($projectId);
        $this->date = now()->format('Y-m-d');
        $this->employee_id = auth()->id();
    }

    /**
     * Get array of branch IDs accessible by the current user.
     */
    protected function getUserBranchIds(): array
    {
        $user = auth()->user();
        if (! $user) {
            return [];
        }

        $branchIds = [];

        // Check if branches relation exists
        if (method_exists($user, 'branches')) {
            // Force load the relation if not already loaded
            if (! $user->relationLoaded('branches')) {
                $user->load('branches');
            }
            $branchIds = $user->branches->pluck('id')->toArray();
        }

        if ($user->branch_id && ! in_array($user->branch_id, $branchIds)) {
            $branchIds[] = $user->branch_id;
        }

        return $branchIds;
    }

    public function rules(): array
    {
        $userBranchIds = $this->getUserBranchIds();

        return [
            // BUG-004 FIX: Ensure task belongs to this project
            'task_id' => [
                'nullable',
                Rule::exists('project_tasks', 'id')->where('project_id', $this->project->id),
            ],
            // BUG-004/008 FIX: Ensure employee belongs to user's branches
            'employee_id' => [
                'required',
                Rule::exists('users', 'id')->whereIn('branch_id', $userBranchIds),
            ],
            'date' => ['required', 'date'],
            'hours' => ['required', 'numeric', 'min:0.1', 'max:24'],
            'is_billable' => ['boolean'],
            'hourly_rate' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function createLog(): void
    {
        $this->authorize('projects.timelogs.manage');
        $this->resetForm();
        $this->editingLog = null;
        $this->showModal = true;
    }

    public function editLog(int $id): void
    {
        $this->authorize('projects.timelogs.manage');
        $this->editingLog = $this->project->timeLogs()->findOrFail($id);
        $this->fill($this->editingLog->only([
            'task_id', 'employee_id', 'hours',
            'is_billable', 'hourly_rate', 'description',
        ]));
        $this->date = $this->editingLog->log_date?->format('Y-m-d')
            ?? $this->editingLog->date?->format('Y-m-d');
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->authorize('projects.timelogs.manage');
        $this->validate();

        $data = $this->only([
            'task_id', 'employee_id', 'date', 'hours',
            'is_billable', 'hourly_rate', 'description',
        ]);

        $logDate = $data['date'] ?? now()->format('Y-m-d');
        $data['user_id'] = $data['employee_id'] ?? auth()->id();
        $data['log_date'] = $logDate;
        $data['date'] = $logDate;
        $data['billable'] = $data['is_billable'];

        if ($this->editingLog) {
            $this->editingLog->update($data);
        } else {
            $this->project->timeLogs()->create($data);
        }

        session()->flash('success', __('Time log saved successfully'));
        $this->resetForm();
        $this->editingLog = null;
        $this->showModal = false;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->editingLog = null;
        $this->resetForm();
    }

    public function deleteLog(int $id): void
    {
        $this->authorize('projects.timelogs.manage');
        $log = $this->project->timeLogs()->findOrFail($id);
        $log->delete();
        session()->flash('success', __('Time log deleted successfully'));
    }

    public function resetForm(): void
    {
        $this->reset([
            'task_id', 'hours', 'hourly_rate', 'description',
        ]);
        $this->date = now()->format('Y-m-d');
        $this->employee_id = auth()->id();
        $this->is_billable = true;
    }

    public function render()
    {
        $timeLogs = $this->project->timeLogs()
            ->with(['task', 'employee', 'user'])
            ->orderByRaw('COALESCE(log_date, date) desc')
            ->paginate(15);

        $tasks = $this->project->tasks()->orderBy('name')->get();

        // BUG-008 FIX: Scope employees to user's branches
        $userBranchIds = $this->getUserBranchIds();
        $employees = User::whereIn('branch_id', $userBranchIds)
            ->orderBy('name')
            ->get();

        // BUG-005 FIX: Consolidate aggregate queries into a single query
        // Note: Both 'billable' and 'is_billable' columns are checked for backwards compatibility
        // with legacy data that may have values in either column
        $stats = $this->project->timeLogs()
            ->selectRaw('
                COALESCE(SUM(hours), 0) as total_hours,
                COALESCE(SUM(CASE WHEN billable = 1 OR is_billable = 1 THEN hours ELSE 0 END), 0) as billable_hours,
                COALESCE(SUM(CASE WHEN billable = 0 AND (is_billable = 0 OR is_billable IS NULL) THEN hours ELSE 0 END), 0) as non_billable_hours,
                COALESCE(SUM(hours * hourly_rate), 0) as total_cost
            ')
            ->first();

        $statsArray = [
            'total_hours' => decimal_float($stats->total_hours ?? 0),
            'billable_hours' => decimal_float($stats->billable_hours ?? 0),
            'non_billable_hours' => decimal_float($stats->non_billable_hours ?? 0),
            'total_cost' => decimal_float($stats->total_cost ?? 0),
        ];

        return view('livewire.projects.time-logs', [
            'timeLogs' => $timeLogs,
            'tasks' => $tasks,
            'employees' => $employees,
            'totalHours' => $statsArray['total_hours'],
            'stats' => $statsArray,
        ]);
    }
}
