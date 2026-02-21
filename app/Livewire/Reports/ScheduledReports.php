<?php

declare(strict_types=1);

namespace App\Livewire\Reports;

use App\Models\ReportTemplate;
use App\Services\ScheduledReportService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use App\Livewire\BaseComponent as Component;
use Livewire\WithPagination;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ScheduledReports extends Component
{
    use AuthorizesRequests, WithPagination;
    protected ScheduledReportService $reportService;

    public function boot(ScheduledReportService $reportService): void
    {
        $this->reportService = $reportService;
    }

    #[On('refreshComponent')]
    public function refreshComponent(): void
    {
        // Livewire 4 compatible refresh handler
    }

    public function mount(): void
    {
        // Authorization check - must have reports.manage permission
        $user = auth()->user();
        if (! $user || ! $user->can('reports.manage')) {
            abort(403, __('Unauthorized access to scheduled reports'));
        }
    }

    public function render()
    {
        $templates = ReportTemplate::active()->orderBy('name')->get();

        $schedules = DB::table('report_schedules')
            ->leftJoin('report_templates', 'report_schedules.report_template_id', '=', 'report_templates.id')
            ->leftJoin('users', 'report_schedules.created_by', '=', 'users.id')
            ->select([
                'report_schedules.*',
                'report_templates.name as template_name',
                'users.name as created_by_name',
            ])
            ->whereNull('report_schedules.deleted_at')
            ->orderByDesc('report_schedules.created_at')
            ->paginate(15);

        return view('livewire.reports.scheduled-reports', [
            'templates' => $templates,
            'schedules' => $schedules,
        ]);
    }

    public function delete(int $id): void
    {
        $this->authorize('reports.manage');
        DB::table('report_schedules')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->update([
                'is_active' => false,
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);
        $this->dispatch('notify', type: 'success', message: __('Schedule deleted successfully'));
    }

    public function toggleActive(int $id): void
    {
        $this->authorize('reports.manage');

        $schedule = DB::table('report_schedules')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();
        if ($schedule) {
            DB::table('report_schedules')
                ->where('id', $id)
                ->whereNull('deleted_at')
                ->update(['is_active' => ! $schedule->is_active, 'updated_at' => now()]);
        }
    }

    public function runNow(int $id): void
    {
        $this->authorize('reports.manage');

        $result = $this->reportService->runNow($id);

        if ($result['success']) {
            $sentCount = count($result['sent_to'] ?? []);
            $this->dispatch('notify', type: 'success', message: __('Report generated and sent to :count recipient(s)', ['count' => $sentCount]));
        } else {
            $this->dispatch('notify', type: 'error', message: $result['error'] ?? __('Failed to generate report'));
        }
    }
}
