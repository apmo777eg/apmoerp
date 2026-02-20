<?php

declare(strict_types=1);

namespace App\Livewire\Notifications;

use Livewire\Attributes\On;
use App\Livewire\BaseComponent as Component;
class Items extends Component
{
    public array $notifications = [];

    public function mount(): void
    {
        $this->loadNotifications();
    }

    #[On('notificationsUpdated')]
    public function loadNotifications(): void
    {
        $user = auth()->user();
        if ($user) {
            $this->notifications = $user->unreadNotifications()
                ->latest()
                ->limit(10)
                ->get()
                ->map(function ($notification) {
                    return [
                        'id' => $notification->id,
                        'type' => $notification->type,
                        'message' => $notification->data['message'] ?? __('New notification'),
                        'link' => $notification->data['link'] ?? null,
                        'created_at' => $notification->created_at->diffForHumans(),
                    ];
                })
                ->toArray();
        }
    }

    public function markAsRead(string $id): void
    {
        $user = auth()->user();
        if ($user) {
            $notification = $user->notifications()->where('id', $id)->first();
            if ($notification) {
                $notification->markAsRead();
                $this->loadNotifications();
            }
        }
    }

    public function markAllAsRead(): void
    {
        $user = auth()->user();
        if ($user) {
            $user->unreadNotifications->markAsRead();
            $this->notifications = [];
        }
    }

    public function render()
    {
        return view('livewire.notifications.items');
    }
}
