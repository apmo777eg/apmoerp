<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Reports;

use App\Services\BranchAccessService;
use App\Services\ReportService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Livewire\BaseComponent as Component;
class Aggregate extends Component
{
    use AuthorizesRequests;

    public string $dateFrom = '';

    public string $dateTo = '';

    public array $aggregateData = [];

    public array $totals = [];

    protected ReportService $reportService;

    protected BranchAccessService $branchAccessService;

    public function boot(ReportService $reportService, BranchAccessService $branchAccessService): void
    {
        $this->reportService = $reportService;
        $this->branchAccessService = $branchAccessService;
    }

    public function mount(): void
    {
        $this->authorize('reports.aggregate');

        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');

        $this->generateReport();
    }

    public function generateReport(): void
    {
        $filters = [
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
        ];

        $result = $this->reportService->getAggregateReport($filters, auth()->user());

        $this->aggregateData = $result['branches']->toArray();
        $this->totals = $result['totals'];
    }

    public function render()
    {
        return view('livewire.admin.reports.aggregate')
            ->layout('layouts.app');
    }
}
