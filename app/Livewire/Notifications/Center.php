<?php

declare(strict_types=1);

namespace App\Livewire\Notifications;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use App\Livewire\BaseComponent as Component;
use Livewire\WithPagination;

class Center extends Component
{
    use WithPagination;

    #[Url(except: 'all')]
    public string $type = 'all';

    public function updatingType(): void
    {
        $this->resetPage();
    }

    public function markAsRead(string $id): void
    {
        if (! auth()->check()) {
            return;
        }

        $notification = auth()->user()->notifications()->whereKey($id)->first();

        if ($notification && $notification->read_at === null) {
            $notification->markAsRead();
        }
    }

    public function markAllAsRead(): void
    {
        if (! auth()->check()) {
            return;
        }

        auth()->user()->unreadNotifications->markAsRead();
    }

    protected function getNotificationsProperty()
    {
        if (! auth()->check()) {
            return collect();
        }

        $query = auth()->user()->notifications()->orderByDesc('created_at');

        if ($this->type !== 'all') {
            $query->where('data->type', 'like', $this->type.'%');
        }

        return $query->paginate(15);
    }

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->can('system.view-notifications')) {
            abort(403);
        }
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.notifications.center', [
            'items' => $this->getNotificationsProperty(),
        ]);
    }
}
