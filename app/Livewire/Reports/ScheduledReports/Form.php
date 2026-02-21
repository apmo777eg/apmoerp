<?php

declare(strict_types=1);

namespace App\Livewire\Reports\ScheduledReports;

use App\Models\ReportTemplate;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use App\Livewire\BaseComponent as Component;
class Form extends Component
{
    public ?int $scheduleId = null;

    public ?int $templateId = null;

    public string $scheduleName = '';

    public string $frequency = 'daily';

    public string $dayOfWeek = '1';

    public string $dayOfMonth = '1';

    public string $timeOfDay = '08:00';

    public string $recipientEmails = '';

    public string $format = 'pdf';

    public bool $isActive = true;

    public array $filters = [];

    public function mount(?int $schedule = null): void
    {
        $user = auth()->user();
        if (! $user || ! $user->can('reports.manage')) {
            abort(403, __('Unauthorized access to scheduled reports'));
        }

        if ($schedule) {
            $this->scheduleId = $schedule;
            $this->loadSchedule();
        }
    }

    protected function loadSchedule(): void
    {
        $schedule = DB::table('report_schedules')
            ->where('id', $this->scheduleId)
            ->whereNull('deleted_at')
            ->first();

        if ($schedule) {
            $this->templateId = $schedule->report_template_id;
            $this->scheduleName = $schedule->name;
            $this->frequency = $schedule->frequency;
            $this->dayOfWeek = (string) ($schedule->day_of_week ?? '1');
            $this->dayOfMonth = (string) ($schedule->day_of_month ?? '1');
            $this->timeOfDay = $schedule->time_of_day ?? '08:00';
            $this->recipientEmails = $schedule->recipient_emails ?? '';
            $this->format = $schedule->format ?? 'pdf';
            $this->isActive = (bool) $schedule->is_active;
            $this->filters = json_decode($schedule->filters ?? '[]', true);
        }
    }

    protected function rules(): array
    {
        return [
            'templateId' => 'required|exists:report_templates,id',
            'scheduleName' => 'required|string|max:255',
            'frequency' => 'required|in:daily,weekly,monthly,quarterly',
            'timeOfDay' => 'required|date_format:H:i',
            'recipientEmails' => 'required|string',
            'format' => 'required|in:pdf,excel,csv',
        ];
    }

    public function save(): mixed
    {
        $user = auth()->user();
        if (! $user || ! $user->can('reports.manage')) {
            abort(403);
        }

        $this->validate();

        $data = [
            'report_template_id' => $this->templateId,
            'name' => $this->scheduleName,
            'frequency' => $this->frequency,
            'day_of_week' => $this->frequency === 'weekly' ? (int) $this->dayOfWeek : null,
            'day_of_month' => in_array($this->frequency, ['monthly', 'quarterly']) ? (int) $this->dayOfMonth : null,
            'time_of_day' => $this->timeOfDay,
            'recipient_emails' => $this->recipientEmails,
            'format' => $this->format,
            'is_active' => $this->isActive,
            'filters' => json_encode($this->filters),
            'updated_at' => now(),
        ];

        if ($this->scheduleId) {
            DB::table('report_schedules')
                ->where('id', $this->scheduleId)
                ->whereNull('deleted_at')
                ->update($data);
            session()->flash('success', __('Schedule updated successfully'));
        } else {
            $data['created_at'] = now();
            // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
            $data['created_by'] = actual_user_id();
            $data['next_run_at'] = $this->calculateNextRun();
            DB::table('report_schedules')->insert($data);
            session()->flash('success', __('Schedule created successfully'));
        }

        $this->redirectRoute('admin.reports.scheduled', navigate: true);
    }

    protected function calculateNextRun(): string
    {
        $now = now();
        $time = explode(':', $this->timeOfDay);
        $hour = (int) $time[0];
        $minute = (int) ($time[1] ?? 0);

        $days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

        switch ($this->frequency) {
            case 'daily':
                $next = $now->copy()->setTime($hour, $minute);
                if ($next->lte($now)) {
                    $next->addDay();
                }
                break;
            case 'weekly':
                $dayName = $days[(int) $this->dayOfWeek] ?? 'monday';
                $next = $now->copy()->next($dayName)->setTime($hour, $minute);
                break;
            case 'monthly':
                $next = $now->copy()->day((int) $this->dayOfMonth)->setTime($hour, $minute);
                if ($next->lte($now)) {
                    $next->addMonth();
                }
                break;
            case 'quarterly':
                $next = $now->copy()->day((int) $this->dayOfMonth)->setTime($hour, $minute);
                $quarter = ceil($now->month / 3);
                $next->month(($quarter * 3) + 1);
                if ($next->lte($now)) {
                    $next->addMonths(3);
                }
                break;
            default:
                $next = $now->addDay();
        }

        return $next->toDateTimeString();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $templates = ReportTemplate::active()->orderBy('name')->get();

        return view('livewire.reports.scheduled-reports.form', [
            'templates' => $templates,
        ]);
    }
}
