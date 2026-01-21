<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

#[Layout('layouts.app')]
class ActivityLog extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $logType = '';

    #[Url(except: '')]
    public string $eventType = '';

    #[Url(except: '')]
    public string $causerType = '';

    #[Url(except: '')]
    public string $dateFrom = '';

    #[Url(except: '')]
    public string $dateTo = '';

    public int $perPage = 25;
    
    public function mount(): void
    {
        // V57-HIGH-01 FIX: Add authorization for activity log viewing
        $user = Auth::user();
        if (! $user || ! $user->can('logs.activity.view')) {
            abort(403);
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingLogType(): void
    {
        $this->resetPage();
    }

    public function updatingEventType(): void
    {
        $this->resetPage();
    }

    public function updatingCauserType(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'logType', 'eventType', 'causerType', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function getLogTypes(): array
    {
        // Use shorter cache duration to catch new log types more quickly
        return Cache::remember('activity_log_types', 60, function () {
            return Activity::distinct()->pluck('log_name')->filter()->toArray();
        });
    }

    public function getCauserTypes(): array
    {
        return Cache::remember('activity_causer_types', 60, function () {
            return Activity::distinct()
                ->whereNotNull('causer_type')
                ->pluck('causer_type')
                ->filter()
                ->unique()
                ->mapWithKeys(fn ($type) => [$type => class_basename($type)])
                ->toArray();
        });
    }

    public function getEventTypes(): array
    {
        return ['created', 'updated', 'deleted', 'restored', 'login', 'logout', 'exported', 'imported'];
    }

    public function render(): View
    {
        $activities = Activity::query()
            ->with(['causer', 'subject'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('description', 'like', '%'.$this->search.'%')
                        ->orWhere('properties', 'like', '%'.$this->search.'%')
                        ->orWhere('subject_type', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->logType, fn ($q) => $q->where('log_name', $this->logType))
            ->when($this->eventType, fn ($q) => $q->where('event', $this->eventType))
            ->when($this->causerType, fn ($q) => $q->where('causer_type', $this->causerType))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.admin.activity-log', [
            'activities' => $activities,
            'logTypes' => $this->getLogTypes(),
            'eventTypes' => $this->getEventTypes(),
            'causerTypes' => $this->getCauserTypes(),
        ]);
    }
}
