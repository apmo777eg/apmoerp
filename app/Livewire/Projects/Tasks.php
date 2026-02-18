<?php

declare(strict_types=1);

namespace App\Livewire\Projects;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Tasks extends Component
{
    use AuthorizesRequests;

    public Project $project;

    public ?ProjectTask $editingTask = null;

    public bool $showModal = false;

    public ?int $editingTaskId = null;

    // Form fields
    public string $title = '';

    public ?string $description = null;

    public ?int $assigned_to = null;

    public ?int $parent_task_id = null;

    public string $priority = 'medium';

    public string $status = 'pending';

    public ?string $start_date = null;

    public ?string $due_date = null;

    public float $estimated_hours = 0;

    public int $progress = 0;

    public array $selectedDependencies = [];

    /**
     * NOTE: Route parameter name is {project} (see routes/web.php).
     */
    public function mount(int $project): void
    {
        $this->authorize('projects.tasks.view');
        $this->project = Project::query()
            ->forUserBranches(auth()->user())
            ->findOrFail($project);
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            // Scope assigned_to validation to user's branches
            'assigned_to' => [
                'nullable',
                Rule::exists('users', 'id')->whereIn('branch_id', $userBranchIds),
            ],
            'parent_task_id' => [
                'nullable',
                Rule::exists('project_tasks', 'id')->where('project_id', $this->project->id),
            ],
            'priority' => ['required', 'in:low,medium,high,critical'],
            'status' => ['required', 'in:pending,in_progress,review,completed,cancelled'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'estimated_hours' => ['required', 'numeric', 'min:0'],
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
            'selectedDependencies' => ['array'],
            'selectedDependencies.*' => [
                Rule::exists('project_tasks', 'id')->where('project_id', $this->project->id),
            ],
        ];
    }

    public function createTask(): void
    {
        $this->authorize('projects.tasks.manage');
        $this->resetForm();
        $this->editingTask = null;
        $this->editingTaskId = null;
        $this->showModal = true;
    }

    public function editTask(int $id): void
    {
        $this->authorize('projects.tasks.manage');
        $this->editingTask = $this->project->tasks()->findOrFail($id);
        $this->editingTaskId = $id;
        $this->showModal = true;
        $this->fill($this->editingTask->only([
            'title', 'description', 'assigned_to', 'parent_task_id',
            'priority', 'status', 'start_date', 'due_date',
            'estimated_hours', 'progress',
        ]));
        $this->selectedDependencies = $this->editingTask
            ->dependencies()
            ->pluck('project_tasks.id')
            ->toArray();
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->editingTask = null;
        $this->editingTaskId = null;
        $this->resetForm();
    }

    public function toggleStatus(int $id): void
    {
        $this->authorize('projects.tasks.manage');

        $task = $this->project->tasks()->findOrFail($id);

        if ($task->status === 'completed') {
            $task->update([
                'status' => 'pending',
                'progress' => 0,
            ]);
        } else {
            $task->update([
                'status' => 'completed',
                'progress' => 100,
            ]);
        }
    }

    public function save(): void
    {
        $this->authorize('projects.tasks.manage');
        $this->validate();

        $data = $this->only([
            'title', 'description', 'assigned_to', 'parent_task_id',
            'priority', 'status', 'start_date', 'due_date',
            'estimated_hours', 'progress',
        ]);

        if ($this->editingTask) {
            $this->editingTask->update($data);
            $task = $this->editingTask;
        } else {
            $task = $this->project->tasks()->create(array_merge(
                $data,
                // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
                ['created_by' => actual_user_id()]
            ));
        }

        // Sync dependencies
        $task->dependencies()->sync($this->selectedDependencies);

        session()->flash('success', __('Task saved successfully'));
        $this->closeModal();
    }

    public function deleteTask(int $id): void
    {
        $this->authorize('projects.tasks.manage');
        $task = $this->project->tasks()->findOrFail($id);
        $task->delete();
        session()->flash('success', __('Task deleted successfully'));
    }

    public function resetForm(): void
    {
        $this->reset([
            'title', 'description', 'assigned_to', 'parent_task_id',
            'priority', 'status', 'start_date', 'due_date',
            'estimated_hours', 'progress', 'selectedDependencies',
        ]);
    }

    public function render()
    {
        $tasks = $this->project->tasks()
            ->with(['assignedTo', 'parentTask', 'dependencies'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Scope users to user's branches
        $userBranchIds = $this->getUserBranchIds();
        $users = User::whereIn('branch_id', $userBranchIds)
            ->orderBy('name')
            ->get();

        $availableTasks = $this->project->tasks()
            ->when($this->editingTask, fn ($q) => $q->where('id', '!=', $this->editingTask->id))
            ->orderBy('title')
            ->get();

        return view('livewire.projects.tasks', [
            'tasks' => $tasks,
            'users' => $users,
            'availableTasks' => $availableTasks,
        ]);
    }
}
