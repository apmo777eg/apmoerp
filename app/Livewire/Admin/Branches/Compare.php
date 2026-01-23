<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Branches;

use App\Models\Branch;
use App\Models\BranchModule;
use App\Models\Module;
use Livewire\Component;

class Compare extends Component
{
    public ?int $branch1Id = null;

    public ?int $branch2Id = null;

    public array $comparison = [];

    public function mount(): void
    {
        $user = auth()->user();
        if (! $user || ! $user->can('branches.manage')) {
            abort(403);
        }
    }

    public function compare(): void
    {
        if (! $this->branch1Id || ! $this->branch2Id) {
            session()->flash('error', __('Please select two branches to compare'));

            return;
        }

        $branch1 = Branch::with('branchModules.module')->findOrFail($this->branch1Id);
        $branch2 = Branch::with('branchModules.module')->findOrFail($this->branch2Id);

        $modules1 = $branch1->branchModules->where('enabled', true)->pluck('module_key')->toArray();
        $modules2 = $branch2->branchModules->where('enabled', true)->pluck('module_key')->toArray();

        $allModules = Module::all()->keyBy('module_key');

        $this->comparison = [
            'branch1' => [
                'name' => $branch1->name,
                'modules' => $modules1,
                'count' => count($modules1),
            ],
            'branch2' => [
                'name' => $branch2->name,
                'modules' => $modules2,
                'count' => count($modules2),
            ],
            'common' => array_intersect($modules1, $modules2),
            'only_in_branch1' => array_diff($modules1, $modules2),
            'only_in_branch2' => array_diff($modules2, $modules1),
            'all_modules' => $allModules,
        ];
    }

    public function syncModules(string $direction): void
    {
        $this->authorize('branches.manage');

        if (! $this->branch1Id || ! $this->branch2Id) {
            session()->flash('error', __('Please compare branches first'));

            return;
        }

        // direction: 'to_branch1' or 'to_branch2'
        $sourceBranchId = $direction === 'to_branch1' ? $this->branch2Id : $this->branch1Id;
        $targetBranchId = $direction === 'to_branch1' ? $this->branch1Id : $this->branch2Id;

        $sourceBranch = Branch::with('branchModules')->findOrFail($sourceBranchId);
        $targetBranch = Branch::findOrFail($targetBranchId);

        foreach ($sourceBranch->branchModules as $branchModule) {
            BranchModule::updateOrCreate(
                [
                    'branch_id' => $targetBranch->id,
                    'module_id' => $branchModule->module_id,
                ],
                [
                    'module_key' => $branchModule->module_key,
                    'enabled' => $branchModule->enabled,
                    'settings' => $branchModule->settings,
                ]
            );
        }

        session()->flash('success', __('Modules synced successfully'));
        $this->compare(); // Refresh comparison
    }

    public function render()
    {
        $branches = Branch::orderBy('name')->get();

        return view('livewire.admin.branches.compare', [
            'branches' => $branches,
        ])->layout('layouts.app', ['title' => __('Compare Branches')]);
    }
}
