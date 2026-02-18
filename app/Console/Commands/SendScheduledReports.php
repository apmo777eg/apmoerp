<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\ScheduledReportMail;
use App\Models\ScheduledReport;
use App\Notifications\InAppMessage;
use Cron\CronExpression;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendScheduledReports extends Command
{
    protected $signature = 'reports:send-scheduled';

    protected $description = 'Send scheduled reports via email based on scheduled_reports table.';

    public function handle(): int
    {
        $this->info('Processing scheduled reports...');

        $now = now();
        $reports = ScheduledReport::query()->with('user', 'template')->get();

        foreach ($reports as $report) {
            try {
                if (! $report->route_name) {
                    continue;
                }

                if ($report->cron_expression && class_exists(CronExpression::class)) {
                    $cron = CronExpression::factory($report->cron_expression);

                    if (! $cron->isDue($now)) {
                        continue;
                    }
                }

                $filters = $report->filters ?? [];
                $template = $report->template;
                $url = null;

                // Template-aware export routing
                // APMOERP68-FIX: Support both 'xlsx' and 'excel' output types for export
                if ($template && in_array($template->output_type, ['excel', 'xlsx', 'pdf'], true)) {
                    if ($template->route_name === 'admin.store.dashboard') {
                        $exportFilters = $filters;

                        if (is_array($template->export_columns) && ! empty($template->export_columns)) {
                            $exportFilters['columns'] = $template->export_columns;
                        }

                        // Normalize xlsx to excel for consistent format handling
                        $exportFilters['format'] = $template->output_type === 'xlsx' ? 'excel' : $template->output_type;

                        try {
                            // APMOERP68-FIX: Correct route name is 'admin.stores.orders.export' not 'admin.store.orders.export'
                            $url = url()->route('admin.stores.orders.export', $exportFilters);
                        } catch (\Throwable $e) {
                            Log::warning('ScheduledReports: export route failed, falling back to base route', [
                                'report_id' => $report->id,
                                'message' => $e->getMessage(),
                            ]);
                        }
                    }
                }

                if (! $url) {
                    try {
                        $url = url()->route($report->route_name, $filters);
                    } catch (\Throwable $e) {
                        Log::warning('ScheduledReports: route not found', [
                            'route_name' => $report->route_name,
                            'id' => $report->id,
                        ]);

                        continue;
                    }
                }

                $recipient = $report->recipient_email;

                if (! $recipient && $report->user) {
                    $recipient = $report->user->email;
                }

                if (! $recipient) {
                    Log::warning('ScheduledReports: no recipient for report', [
                        'id' => $report->id,
                    ]);

                    continue;
                }

                $outputType = $template->output_type ?? 'web';

                Mail::to($recipient)->send(new ScheduledReportMail(
                    $report->route_name,
                    $filters,
                    $url,
                    $outputType
                ));

                $this->line(sprintf(
                    'Sent scheduled report [%s] to [%s]',
                    $report->route_name,
                    $recipient
                ));
                $this->markScheduledReportSuccess($report);

            } catch (\Throwable $e) {
                Log::error('Failed to send scheduled report', [
                    'id' => $report->id,
                    'message' => $e->getMessage(),
                ]);
                $this->markScheduledReportFailure($report, $e->getMessage());

            }
        }

        $this->info('Done.');

        return self::SUCCESS;
    }

    protected function markScheduledReportSuccess(ScheduledReport $report): void
    {
        $report->forceFill([
            'last_status' => 'success',
            'last_run_at' => now(),
            'last_error' => null,
            'runs_count' => ($report->runs_count ?? 0) + 1,
        ])->save();
    }

    protected function markScheduledReportFailure(ScheduledReport $report, string $message): void
    {
        $report->forceFill([
            'last_status' => 'failed',
            'last_run_at' => now(),
            'last_error' => $message,
            'runs_count' => ($report->runs_count ?? 0) + 1,
            'failures_count' => ($report->failures_count ?? 0) + 1,
        ])->save();

        if ($report->user) {
            $title = __('Scheduled report failed');
            $body = __('Report ":name" failed: :message', [
                'name' => $report->template?->name ?? $report->route_name,
                'message' => $message,
            ]);

            $report->user->notify(new InAppMessage(
                $title,
                $body,
                [
                    'type' => 'reports',
                    'scheduled_report_id' => $report->id,
                    'template_id' => $report->report_template_id,
                    'route_name' => $report->route_name,
                ]
            ));
        }
    }
}
