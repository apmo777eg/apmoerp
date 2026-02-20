<?php

declare(strict_types=1);

namespace App\Livewire\Projects;

use App\Models\Project;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use App\Livewire\BaseComponent as Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use AuthorizesRequests, WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    public function mount(): void
    {
        $this->authorize('projects.view');
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

    public function delete(int $id): void
    {
        $this->authorize('projects.delete');

        $project = Project::findOrFail($id);
        $project->delete();

        session()->flash('success', __('Project deleted successfully'));
    }

    public function render()
    {
        $query = Project::with(['client', 'manager'])
            ->when($this->search, fn ($q) => $q->where(function ($query) {
                $query->where('code', 'like', "%{$this->search}%")
                    ->orWhere('name', 'like', "%{$this->search}%")
                    ->orWhere('description', 'like', "%{$this->search}%");
            }))
            ->when($this->status, fn ($q) => $q->where('status', $this->status));

        $projects = $query->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);

        // Statistics
        $stats = [
            'total' => Project::count(),
            'active' => Project::active()->count(),
            'completed' => Project::completed()->count(),
            'overdue' => Project::overdue()->count(),
            'over_budget' => Project::overBudget()->count(),
        ];

        return view('livewire.projects.index', [
            'projects' => $projects,
            'stats' => $stats,
        ]);
    }
}
