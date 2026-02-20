<?php

declare(strict_types=1);

namespace App\Livewire\Projects;

use App\Models\Project;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use App\Livewire\BaseComponent as Component;
/**
 * Projects Gantt Chart Component
 *
 * Visual Gantt chart view of projects and their tasks showing:
 * - Project timelines
 * - Task progress
 * - Milestones
 * - Dependencies visualization
 */
#[Layout('layouts.app')]
class GanttChart extends Component
{
    use AuthorizesRequests;

    #[Url]
    public string $viewMode = 'month'; // week, month, quarter

    #[Url]
    public string $status = '';

    #[Url]
    public ?int $projectId = null;

    public string $startDate = '';

    public string $endDate = '';

    public function mount(): void
    {
        $this->authorize('projects.view');
        $this->setDateRange();
    }

    /**
     * Set date range based on view mode
     */
    protected function setDateRange(): void
    {
        $now = now();

        if ($this->viewMode === 'week') {
            $this->startDate = $now->copy()->startOfWeek()->toDateString();
            $this->endDate = $now->copy()->endOfWeek()->toDateString();
        } elseif ($this->viewMode === 'month') {
            $this->startDate = $now->copy()->startOfMonth()->toDateString();
            $this->endDate = $now->copy()->endOfMonth()->toDateString();
        } else { // quarter
            $this->startDate = $now->copy()->startOfQuarter()->toDateString();
            $this->endDate = $now->copy()->endOfQuarter()->toDateString();
        }
    }

    /**
     * Navigate to previous period
     */
    public function previousPeriod(): void
    {
        $start = \Carbon\Carbon::parse($this->startDate);

        if ($this->viewMode === 'week') {
            $this->startDate = $start->subWeek()->startOfWeek()->toDateString();
            $this->endDate = \Carbon\Carbon::parse($this->startDate)->endOfWeek()->toDateString();
        } elseif ($this->viewMode === 'month') {
            $this->startDate = $start->subMonth()->startOfMonth()->toDateString();
            $this->endDate = \Carbon\Carbon::parse($this->startDate)->endOfMonth()->toDateString();
        } else {
            $this->startDate = $start->subQuarter()->startOfQuarter()->toDateString();
            $this->endDate = \Carbon\Carbon::parse($this->startDate)->endOfQuarter()->toDateString();
        }
    }

    /**
     * Navigate to next period
     */
    public function nextPeriod(): void
    {
        $start = \Carbon\Carbon::parse($this->startDate);

        if ($this->viewMode === 'week') {
            $this->startDate = $start->addWeek()->startOfWeek()->toDateString();
            $this->endDate = \Carbon\Carbon::parse($this->startDate)->endOfWeek()->toDateString();
        } elseif ($this->viewMode === 'month') {
            $this->startDate = $start->addMonth()->startOfMonth()->toDateString();
            $this->endDate = \Carbon\Carbon::parse($this->startDate)->endOfMonth()->toDateString();
        } else {
            $this->startDate = $start->addQuarter()->startOfQuarter()->toDateString();
            $this->endDate = \Carbon\Carbon::parse($this->startDate)->endOfQuarter()->toDateString();
        }
    }

    /**
     * Go to today
     */
    public function goToToday(): void
    {
        $this->setDateRange();
    }

    /**
     * Update view mode
     */
    public function updatedViewMode(): void
    {
        $this->setDateRange();
    }

    /**
     * Get projects for Gantt chart
     */
    public function getProjectsProperty()
    {
        $user = auth()->user();

        return Project::query()
            ->when($user && $user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->projectId, fn ($q) => $q->where('id', $this->projectId))
            ->where(function ($query) {
                $query->whereBetween('start_date', [$this->startDate, $this->endDate])
                    ->orWhereBetween('end_date', [$this->startDate, $this->endDate])
                    ->orWhere(function ($q) {
                        $q->where('start_date', '<=', $this->startDate)
                            ->where('end_date', '>=', $this->endDate);
                    });
            })
            ->with(['client', 'manager', 'tasks'])
            ->orderBy('start_date')
            ->get()
            ->map(function ($project) {
                return [
                    'id' => $project->id,
                    'code' => $project->code,
                    'name' => $project->name,
                    'client_name' => $project->client?->name ?? __('N/A'),
                    'manager_name' => $project->manager?->name ?? __('N/A'),
                    'status' => $project->status,
                    'priority' => $project->priority,
                    'progress' => $project->progress ?? 0,
                    'start_date' => $project->start_date?->format('Y-m-d'),
                    'end_date' => $project->end_date?->format('Y-m-d'),
                    'budget' => $project->budget_amount,
                    'currency' => $project->currency ?? 'EGP',
                    'days_remaining' => $project->end_date ? now()->diffInDays($project->end_date, false) : null,
                    'is_overdue' => $project->end_date && $project->end_date < now() && $project->status !== 'completed',
                    'tasks' => $project->tasks->map(fn ($task) => [
                        'id' => $task->id,
                        'name' => $task->name ?? $task->title ?? '',
                        'status' => $task->status,
                        'progress' => $task->progress ?? 0,
                        'start_date' => $task->start_date?->format('Y-m-d') ?? $project->start_date?->format('Y-m-d'),
                        'end_date' => $task->due_date?->format('Y-m-d') ?? $task->end_date?->format('Y-m-d'),
                    ])->toArray(),
                ];
            });
    }

    /**
     * Get all projects for filter dropdown
     */
    public function getAllProjectsProperty()
    {
        $user = auth()->user();

        return Project::query()
            ->when($user && $user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
    }

    /**
     * Get days/dates for header based on view mode
     */
    public function getTimelineHeaderProperty(): array
    {
        $headers = [];
        $current = \Carbon\Carbon::parse($this->startDate);
        $end = \Carbon\Carbon::parse($this->endDate);

        if ($this->viewMode === 'week') {
            // Daily headers for week view
            while ($current <= $end) {
                $headers[] = [
                    'date' => $current->format('Y-m-d'),
                    'label' => $current->format('D'),
                    'sub_label' => $current->format('d'),
                    'is_today' => $current->isToday(),
                    'is_weekend' => $current->isWeekend(),
                ];
                $current->addDay();
            }
        } elseif ($this->viewMode === 'month') {
            // Daily headers for month view
            while ($current <= $end) {
                $headers[] = [
                    'date' => $current->format('Y-m-d'),
                    'label' => $current->format('d'),
                    'sub_label' => '',
                    'is_today' => $current->isToday(),
                    'is_weekend' => $current->isWeekend(),
                ];
                $current->addDay();
            }
        } else {
            // Weekly headers for quarter view
            while ($current <= $end) {
                $weekEnd = $current->copy()->endOfWeek();
                if ($weekEnd > $end) {
                    $weekEnd = $end;
                }

                $headers[] = [
                    'date' => $current->format('Y-m-d'),
                    'label' => 'W'.$current->weekOfYear,
                    'sub_label' => $current->format('M d'),
                    'is_today' => now()->between($current, $weekEnd),
                    'is_weekend' => false,
                ];
                $current->addWeek()->startOfWeek();
            }
        }

        return $headers;
    }

    /**
     * Get status color
     */
    public function getStatusColor(string $status): string
    {
        return match ($status) {
            'draft', 'pending' => 'bg-gray-400',
            'planning' => 'bg-blue-400',
            'active', 'in_progress' => 'bg-amber-400',
            'on_hold' => 'bg-orange-400',
            'completed', 'done' => 'bg-green-400',
            'cancelled' => 'bg-red-400',
            default => 'bg-gray-400',
        };
    }

    /**
     * Get priority color
     */
    public function getPriorityColor(string $priority): string
    {
        return match ($priority) {
            'critical', 'urgent' => 'bg-red-500 text-white',
            'high' => 'bg-orange-500 text-white',
            'medium', 'normal' => 'bg-blue-500 text-white',
            'low' => 'bg-gray-500 text-white',
            default => 'bg-gray-500 text-white',
        };
    }

    /**
     * Calculate position and width for timeline bar
     */
    public function getTimelinePosition(?string $itemStart, ?string $itemEnd): array
    {
        if (! $itemStart) {
            return ['left' => 0, 'width' => 0];
        }

        $startDate = \Carbon\Carbon::parse($this->startDate);
        $endDate = \Carbon\Carbon::parse($this->endDate);
        $totalDays = max(1, $startDate->diffInDays($endDate) + 1);

        $itemStartDate = \Carbon\Carbon::parse($itemStart);
        $itemEndDate = $itemEnd ? \Carbon\Carbon::parse($itemEnd) : $itemStartDate;

        // Clamp to visible range
        if ($itemStartDate < $startDate) {
            $itemStartDate = $startDate;
        }
        if ($itemEndDate > $endDate) {
            $itemEndDate = $endDate;
        }

        $leftDays = $startDate->diffInDays($itemStartDate);
        $widthDays = max(1, $itemStartDate->diffInDays($itemEndDate) + 1);

        return [
            'left' => ($leftDays / $totalDays) * 100,
            'width' => ($widthDays / $totalDays) * 100,
        ];
    }

    public function render()
    {
        return view('livewire.projects.gantt-chart')
            ->title(__('Project Gantt Chart'));
    }
}
