<?php

declare(strict_types=1);

namespace App\Livewire\Projects;

use App\Models\Project;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use App\Livewire\BaseComponent as Component;
class Show extends Component
{
    use AuthorizesRequests;

    public Project $project;

    public function mount(Project $project): void
    {
        $this->authorize('projects.view');
        $this->project = $project->load(['customer', 'tasks', 'timeLogs', 'expenses']);
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.projects.show');
    }
}
