<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Reports;

use App\Models\Branch;
use App\Services\BranchAccessService;
use App\Services\ExportService;
use App\Services\ReportService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Index extends Component
{
    use AuthorizesRequests;

    public ?int $selectedBranchId = null;

    public ?int $selectedModuleId = null;

    public string $selectedReport = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public array $reportData = [];

    public array $summary = [];

    public bool $showExportModal = false;

    public array $selectedColumns = [];

    public string $exportFormat = 'xlsx';

    protected ReportService $reportService;

    protected BranchAccessService $branchAccessService;

    protected ExportService $exportService;

    public function boot(ReportService $reportService, BranchAccessService $branchAccessService, ExportService $exportService): void
    {
        $this->reportService = $reportService;
        $this->branchAccessService = $branchAccessService;
        $this->exportService = $exportService;
    }

    public function mount(): void
    {
        $this->authorize('reports.view');

        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');

        $user = auth()->user();
        if (! $user->hasAnyRole(['Super Admin', 'super-admin'])) {
            $branches = $this->branchAccessService->getUserBranches($user);
            $this->selectedBranchId = $branches->first()?->id;
        }
    }

    public function updatedSelectedModuleId(): void
    {
        $this->selectedReport = '';
        $this->reportData = [];
        $this->summary = [];
    }

    public function generateReport(): void
    {
        $this->authorize('reports.view');

        if (empty($this->selectedReport)) {
            session()->flash('error', __('Please select a report'));

            return;
        }

        $filters = [
            'branch_id' => $this->selectedBranchId,
            'module_id' => $this->selectedModuleId,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
        ];

        $result = $this->reportService->generateReport($this->selectedReport, $filters, auth()->user());

        $this->reportData = $result['data']->toArray();
        $this->summary = $result['summary'] ?? [];
    }

    public function openExportModal(): void
    {
        $this->authorize('reports.export');

        if (empty($this->reportData)) {
            session()->flash('error', __('Please generate a report first'));

            return;
        }

        $entityType = $this->getEntityTypeFromReport();
        $this->selectedColumns = array_keys($this->exportService->getAvailableColumns($entityType));
        $this->showExportModal = true;
    }

    public function closeExportModal(): void
    {
        $this->showExportModal = false;
    }

    public function export()
    {
        $this->authorize('reports.export');

        if (empty($this->selectedColumns)) {
            session()->flash('error', __('Please select at least one column'));

            return;
        }

        $entityType = $this->getEntityTypeFromReport();
        $availableColumns = $this->exportService->getAvailableColumns($entityType);

        $filename = 'report_'.date('Y-m-d_His');

        $filepath = $this->exportService->export(
            collect($this->reportData),
            $this->selectedColumns,
            $this->exportFormat,
            [
                'available_columns' => $availableColumns,
                'title' => __('Report Export'),
                'filename' => $filename,
            ]
        );

        $this->closeExportModal();

        $downloadName = $filename.'.'.$this->exportFormat;

        // Store export info in session for download
        // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
        session()->put('export_file', [
            'path' => $filepath,
            'name' => $downloadName,
            'time' => now()->timestamp,
            'user_id' => actual_user_id(),
        ]);

        // Make sure the next request can immediately read the export session data
        session()->save();

        // Use JavaScript to trigger download via a dedicated route
        $this->dispatch('trigger-download', url: route('download.export'));

        session()->flash('success', __('Export prepared. Download starting...'));
    }

    protected function getEntityTypeFromReport(): string
    {
        return match (true) {
            str_contains($this->selectedReport, 'inventory'), str_contains($this->selectedReport, 'products') => 'products',
            str_contains($this->selectedReport, 'sales') => 'sales',
            str_contains($this->selectedReport, 'purchases') => 'purchases',
            str_contains($this->selectedReport, 'expenses') => 'expenses',
            str_contains($this->selectedReport, 'income') => 'incomes',
            str_contains($this->selectedReport, 'customers') => 'customers',
            str_contains($this->selectedReport, 'suppliers') => 'suppliers',
            default => 'products',
        };
    }

    public function render()
    {
        $user = auth()->user();

        $isSuperAdmin = $user->hasAnyRole(['Super Admin', 'super-admin']);
        $branches = $isSuperAdmin
            ? Branch::active()->get()
            : $this->branchAccessService->getUserBranches($user);

        $modules = $this->branchAccessService->getAccessibleModulesForUser($user);

        $reports = $this->reportService->getAvailableReports($user, $this->selectedModuleId);

        $entityType = $this->getEntityTypeFromReport();
        $availableColumns = $this->exportService->getAvailableColumns($entityType);

        return view('livewire.admin.reports.index', [
            'branches' => $branches,
            'modules' => $modules,
            'reports' => $reports,
            'availableColumns' => $availableColumns,
            'isSuperAdmin' => $isSuperAdmin,
        ])->layout('layouts.app');
    }
}
