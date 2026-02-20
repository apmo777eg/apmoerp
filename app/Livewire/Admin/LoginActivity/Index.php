<?php

declare(strict_types=1);

namespace App\Livewire\Admin\LoginActivity;

use App\Models\LoginActivity;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use App\Livewire\BaseComponent as Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $event = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->can('logs.login.view')) {
            abort(403);
        }

        $this->dateFrom = now()->subDays(7)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $query = LoginActivity::with('user')
            ->when($this->search, function ($q) {
                $q->where(function ($inner) {
                    $inner->where('email', 'like', "%{$this->search}%")
                        ->orWhere('ip_address', 'like', "%{$this->search}%")
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->when($this->event, fn ($q) => $q->where('event', $this->event))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->orderByDesc('created_at');

        $stats = [
            'total_logins' => LoginActivity::where('event', 'login')->recent(30)->count(),
            'failed_attempts' => LoginActivity::where('event', 'failed')->recent(30)->count(),
            'unique_users' => LoginActivity::where('event', 'login')->recent(30)->distinct('user_id')->count('user_id'),
            'unique_ips' => LoginActivity::recent(30)->distinct('ip_address')->count('ip_address'),
        ];

        return view('livewire.admin.login-activity.index', [
            'activities' => $query->paginate(20),
            'stats' => $stats,
        ]);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingEvent(): void
    {
        $this->resetPage();
    }
}
