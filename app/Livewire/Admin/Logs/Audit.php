<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Logs;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Audit extends Component
{
    use WithPagination;

    public ?int $actorId = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->can('logs.audit.view')) {
            abort(403);
        }
    }

    public function updating($name, $value): void
    {
        if (in_array($name, ['actorId', 'dateFrom', 'dateTo'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $query = AuditLog::query()
            ->with(['user', 'targetUser'])
            ->latest();

        if ($this->actorId) {
            $query->where('user_id', $this->actorId);
        }

        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        $logs = $query->paginate(20);

        $actors = User::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('livewire.admin.logs.audit', [
            'logs' => $logs,
            'actors' => $actors,
        ]);
    }
}
