<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Reports;

use App\Models\Module;
use App\Services\BranchAccessService;
use App\Services\ReportService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Livewire\BaseComponent as Component;
class ModuleReport extends Component
{
    use AuthorizesRequests;

    public Module $module;

    public string $dateFrom = '';

    public string $dateTo = '';

    public ?int $selectedBranchId = null;

    /**
     * If a Super Admin (or branches.view-all) selected a "branch perspective"
     * from the global branch switcher, we lock this report to that branch.
     * This avoids asking the user to pick the branch again.
     */
    public ?int $branchContextId = null;
    public ?string $branchContextName = null;

    public array $reportData = [];

    public array $summary = [];

    protected ReportService $reportService;

    protected BranchAccessService $branchAccessService;

    public function boot(ReportService $reportService, BranchAccessService $branchAccessService): void
    {
        $this->reportService = $reportService;
        $this->branchAccessService = $branchAccessService;
    }

    public function mount(Module $module): void
    {
        $this->authorize('reports.view');
        $this->module = $module;
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');

        // Global (request-level) branch context selected from the sidebar switcher.
        $this->branchContextId = current_branch_id();
        if ($this->branchContextId) {
            $this->selectedBranchId = $this->branchContextId;
            $this->branchContextName = \App\Models\Branch::find($this->branchContextId)?->name;
        }

        $user = auth()->user();
        if (! $this->selectedBranchId && ! $user->hasAnyRole(['Super Admin', 'super-admin'])) {
            $branches = $this->branchAccessService->getUserBranches($user);
            $this->selectedBranchId = $branches->first()?->id;
        }

        $this->generateReport();
    }

    public function generateReport(): void
    {
        $this->authorize('reports.view');

        $filters = [
            'branch_id' => $this->selectedBranchId,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
        ];

        $result = $this->reportService->getModuleReport($this->module->id, $filters, auth()->user());

        $this->reportData = $result['inventory']['items']->toArray();
        $this->summary = $result['inventory']['summary'] ?? [];
    }

    public function render()
    {
        $user = auth()->user();

        $isSuperAdmin = $user->hasAnyRole(['Super Admin', 'super-admin']);
        $branches = $isSuperAdmin
            ? \App\Models\Branch::active()->get()
            : $this->branchAccessService->getUserBranches($user);

        return view('livewire.admin.reports.module-report', [
            'branches' => $branches,
            'isSuperAdmin' => $isSuperAdmin,
        ])->layout('layouts.app');
    }
}
