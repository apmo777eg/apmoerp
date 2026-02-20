<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use App\Livewire\BaseComponent as Component;
class ActivityTimeline extends Component
{
    public array $activities = [];

    public int $limit = 15;

    public function mount(): void
    {
        $this->loadActivities();
    }

    public function loadActivities(): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        // Get recent activities for the user or branch
        $query = AuditLog::with('user')
            ->latest()
            ->limit($this->limit);

        // IMPORTANT: Branch filtering is already handled by BranchScope on AuditLog (HasBranch).
        // Adding a manual filter here causes double-scoping and breaks "All Branches".

        $this->activities = $query->get()->map(fn ($activity) => [
            'id' => $activity->id,
            'action' => $activity->event,
            'model' => $this->getModelName($activity->auditable_type),
            'description' => $this->getActivityDescription($activity),
            'user' => $activity->user?->name ?? __('System'),
            'icon' => $this->getActivityIcon($activity->event),
            'color' => $this->getActivityColor($activity->event),
            'time' => $activity->created_at->diffForHumans(),
            'url' => $this->getActivityUrl($activity),
        ])->toArray();
    }

    public function refresh(): void
    {
        $this->loadActivities();
    }

    protected function getModelName(string $type): string
    {
        $parts = explode('\\', $type);
        $className = end($parts);

        // Convert PascalCase to readable format
        return __(preg_replace('/(?<!^)[A-Z]/', ' $0', $className));
    }

    protected function getActivityDescription(AuditLog $activity): string
    {
        $model = $this->getModelName($activity->auditable_type);
        $action = match ($activity->event) {
            'created' => __('created'),
            'updated' => __('updated'),
            'deleted' => __('deleted'),
            'restored' => __('restored'),
            default => $activity->event
        };

        // Get the identifier from old/new values
        $identifier = $activity->new_values['name']
            ?? $activity->new_values['reference_number']
            ?? $activity->new_values['code']
            ?? $activity->old_values['name']
            ?? $activity->old_values['reference_number']
            ?? $activity->old_values['code']
            ?? '#'.$activity->auditable_id;

        return "{$action} {$model}: {$identifier}";
    }

    protected function getActivityIcon(?string $event): string
    {
        return match ($event) {
            'created' => 'M12 6v6m0 0v6m0-6h6m-6 0H6',
            'updated' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
            'deleted' => 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16',
            'restored' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
            default => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
        };
    }

    protected function getActivityColor(?string $event): string
    {
        return match ($event) {
            'created' => 'green',
            'updated' => 'blue',
            'deleted' => 'red',
            'restored' => 'purple',
            default => 'gray'
        };
    }

    protected function getActivityUrl(AuditLog $activity): ?string
    {
        // Return null for now - can be enhanced with route generation based on model type
        return null;
    }

    public function render()
    {
        return view('livewire.components.activity-timeline');
    }
}
