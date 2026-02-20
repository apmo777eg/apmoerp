<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Analytics;

use App\Services\Analytics\AdvancedAnalyticsService;
use App\Livewire\BaseComponent as Component;
class AdvancedDashboard extends Component
{
    public ?int $branchId = null;

    public string $period = 'month';

    public array $metrics = [];

    public bool $loading = true;

    protected AdvancedAnalyticsService $analyticsService;

    public function boot(AdvancedAnalyticsService $service): void
    {
        $this->analyticsService = $service;
    }

    public function mount(): void
    {
        $user = auth()->user();
        if (! $user || ! $user->can('reports.view')) {
            abort(403);
        }

        $this->branchId = $user->branch_id;
        $this->loadMetrics();
    }

    public function loadMetrics(): void
    {
        $this->loading = true;

        try {
            $this->metrics = $this->analyticsService->getDashboardMetrics($this->branchId, $this->period);
        } catch (\Exception $e) {
            session()->flash('error', __('Failed to load analytics: ').$e->getMessage());
            $this->metrics = [];
        }

        $this->loading = false;
    }

    public function updatedBranchId(): void
    {
        $this->loadMetrics();
    }

    public function updatedPeriod(): void
    {
        $this->loadMetrics();
    }

    public function render()
    {
        return view('livewire.admin.analytics.advanced-dashboard')->layout('layouts.app', [
            'title' => __('Advanced Analytics Dashboard'),
        ]);
    }
}
