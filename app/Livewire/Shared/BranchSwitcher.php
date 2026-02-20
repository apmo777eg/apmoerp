<?php

declare(strict_types=1);

namespace App\Livewire\Shared;

use App\Models\Branch;
use App\Models\BranchModule;
use App\Services\BranchContextManager;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use App\Livewire\BaseComponent as Component;
/**
 * Branch Switcher Component
 *
 * Allows Super Admin and users with 'branches.view-all' permission to switch
 * between branches and view the system from that branch's perspective.
 *
 * The selected branch is stored in session and affects:
 * - Sidebar menu items (only shows modules enabled for the selected branch)
 * - Reports and data filtering (via BranchScope + SetUserBranchContext)
 *
 * IMPORTANT:
 * - We use integer 0 in session as a sentinel for "All Branches".
 * - When session('admin_branch_context') is 0, we intentionally do NOT scope queries by branch.
 */
class BranchSwitcher extends Component
{
    public ?int $selectedBranchId = null;

    public function mount(): void
    {
        $user = Auth::user();

        // For users who can switch branches, persist the context in session.
        if ($user && $this->canSwitchBranches()) {
            // If there's no saved context yet, default to the user's own branch (or 0 if none).
            if (! session()->exists('admin_branch_context')) {
                session(['admin_branch_context' => (int) ($user->branch_id ?? 0)]);
            }

            $this->selectedBranchId = (int) session('admin_branch_context', 0);

            return;
        }

        // For normal users, just reflect their assigned branch (no session context).
        $this->selectedBranchId = $user?->branch_id;
    }

    /**
     * Check if user can switch branches.
     */
    public function canSwitchBranches(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return BranchContextManager::canViewAllBranches($user);
    }

    /**
     * Switch to a different branch context.
     *
     * @param  ?int  $branchId  Branch ID, or 0/null for "All Branches"
     */
    public function switchBranch(?int $branchId): void
    {
        if (! $this->canSwitchBranches()) {
            return;
        }

        // 0 (or null from older calls) means: All Branches
        if ($branchId === null || (int) $branchId === 0) {
            session(['admin_branch_context' => 0]);
            $this->selectedBranchId = 0;

            $this->dispatch('branch-switched');

            return;
        }

        // Verify branch exists and is active
        $branch = Branch::query()
            ->where('is_active', true)
            ->find((int) $branchId);

        if ($branch) {
            session(['admin_branch_context' => (int) $branchId]);
            $this->selectedBranchId = (int) $branchId;
        }

        // Refresh the page to apply changes
        $this->dispatch('branch-switched');
    }

    /**
     * Get list of available branches.
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
     * Get currently selected branch info.
     */
    public function getSelectedBranchProperty(): ?Branch
    {
        if (! $this->selectedBranchId) {
            return null;
        }

        return Branch::find($this->selectedBranchId);
    }

    /**
     * Get enabled modules for the selected branch.
     */
    public function getSelectedBranchModulesProperty(): array
    {
        // For "All Branches" context, show the union of enabled modules across branches.
        // This prevents modules from disappearing unexpectedly in the sidebar.
        if (! $this->selectedBranchId) {
            return BranchModule::query()
                ->where('enabled', true)
                ->whereNotNull('module_key')
                ->distinct()
                ->orderBy('module_key')
                ->pluck('module_key')
                ->toArray();
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
            'selectedBranch' => $this->getSelectedBranchProperty(),
        ]);
    }
}
