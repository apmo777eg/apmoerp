<?php

declare(strict_types=1);

namespace App\Livewire\Shared;

use App\Models\Branch;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Branch Switcher Component
 *
 * Allows Super Admin and users with 'branches.view-all' permission to switch
 * between branches and view the system from that branch's perspective.
 *
 * The selected branch is stored in session and affects:
 * - Sidebar menu items (only shows modules enabled for the selected branch)
 * - Reports and data filtering
 */
class BranchSwitcher extends Component
{
    public ?int $selectedBranchId = null;

    public function mount(): void
    {
        $user = Auth::user();

        // Get the stored branch ID from session, or use user's own branch
        $this->selectedBranchId = session('admin_branch_context', $user?->branch_id);
    }

    /**
     * Check if user can switch branches
     */
    public function canSwitchBranches(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        // Super Admin or users with branches.view-all can switch
        return $user->hasRole('Super Admin') || $user->can('branches.view-all');
    }

    /**
     * Switch to a different branch context
     */
    public function switchBranch(?int $branchId): void
    {
        if (! $this->canSwitchBranches()) {
            return;
        }

        if ($branchId === null) {
            // Clear branch context (view all)
            session()->forget('admin_branch_context');
            $this->selectedBranchId = null;
        } else {
            // Verify branch exists and is active
            $branch = Branch::where('is_active', true)->find($branchId);

            if ($branch) {
                session(['admin_branch_context' => $branchId]);
                $this->selectedBranchId = $branchId;
            }
        }

        // Refresh the page to apply changes
        $this->dispatch('branch-switched');
    }

    /**
     * Get list of available branches
     */
    public function getBranches(): array
    {
        if (! $this->canSwitchBranches()) {
            return [];
        }

        return Branch::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn ($branch) => [
                'id' => $branch->id,
                'name' => $branch->name ?? '',
                'code' => $branch->code ?? '',
            ])
            ->filter(fn ($branch) => isset($branch['id']) && ! empty($branch['name']))
            ->values()
            ->toArray();
    }

    /**
     * Get currently selected branch info
     */
    public function getSelectedBranchProperty(): ?Branch
    {
        if (! $this->selectedBranchId) {
            return null;
        }

        return Branch::find($this->selectedBranchId);
    }

    /**
     * Get enabled modules for the selected branch
     */
    public function getSelectedBranchModulesProperty(): array
    {
        if (! $this->selectedBranchId) {
            return [];
        }

        return Branch::find($this->selectedBranchId)
            ?->branchModules()
            ->where('branch_modules.enabled', true)
            ->pluck('branch_modules.module_key')
            ->toArray() ?? [];
    }

    public function render(): View
    {
        return view('livewire.shared.branch-switcher', [
            'branches' => $this->getBranches(),
            'canSwitch' => $this->canSwitchBranches(),
            'selectedBranch' => $this->selectedBranch,
        ]);
    }
}
