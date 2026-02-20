<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Reports;

use App\Models\ReportTemplate;
use App\Models\ScheduledReport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use App\Livewire\BaseComponent as Component;
class ReportsHub extends Component
{
    #[Url]
    public string $module = 'all';

    #[Url]
    public string $search = '';

    protected array $cachedPermissions = [];

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->can('reports.view')) {
            abort(403);
        }
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $user = Auth::user();

        $query = ReportTemplate::query()
            ->where('is_active', true);

        if ($this->module !== 'all') {
            $query->where('module', $this->module);
        }

        if ($this->search !== '') {
            $term = '%'.$this->search.'%';

            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('description', 'like', $term)
                    // DB-first canonical column is `template_key` (not `key`)
                    ->orWhere('template_key', 'like', $term);
            });
        }

        // Cache user permissions for 5 minutes to avoid repeated DB queries
        $userPermissions = [];
        if ($user) {
            $cacheKey = 'user_permissions_'.$user->id;
            $userPermissions = Cache::remember($cacheKey, 300, function () use ($user) {
                return $user->getAllPermissions()->pluck('name')->toArray();
            });
        }

        $templates = $query
            ->orderBy('module')
            ->orderBy('name')
            ->get()
            ->filter(function (ReportTemplate $template) use ($userPermissions) {
                if (! $template->required_permission) {
                    return true;
                }

                return in_array($template->required_permission, $userPermissions);
            })
            ->groupBy(fn (ReportTemplate $t) => $t->module ?? 'general');

        $scheduledCount = ScheduledReport::query()->count();

        $failedCount = ScheduledReport::query()
            ->where('last_status', 'failed')
            ->count();

        return view('livewire.admin.reports.reports-hub', [
            'templatesByModule' => $templates,
            'scheduledCount' => $scheduledCount,
            'failedCount' => $failedCount,
        ]);
    }
}
