<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use App\Livewire\BaseComponent as Component;
class NotificationsCenter extends Component
{
    public int $unreadCount = 0;

    public array $notifications = [];

    public bool $showDropdown = false;

    public function mount(): void
    {
        $this->loadNotifications();
    }

    public function loadNotifications(): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        // Get recent notifications (last 20)
        $notificationQuery = Notification::where('notifiable_id', $user->id)
            ->where('notifiable_type', get_class($user))
            ->latest()
            ->limit(20);

        $this->notifications = $notificationQuery->get()->map(fn ($n) => [
            'id' => $n->id,
            'type' => $this->getNotificationType($n->type),
            'title' => $n->data['title'] ?? __('Notification'),
            'message' => $n->data['message'] ?? '',
            'icon' => $this->getNotificationIcon($n->type),
            'color' => $this->getNotificationColor($n->type),
            'read' => $n->read_at !== null,
            'time' => $n->created_at->diffForHumans(),
            'action_url' => $n->data['action_url'] ?? null,
        ])->toArray();

        $this->unreadCount = Notification::where('notifiable_id', $user->id)
            ->where('notifiable_type', get_class($user))
            ->whereNull('read_at')
            ->count();
    }

    public function toggleDropdown(): void
    {
        $this->showDropdown = ! $this->showDropdown;
    }

    public function markAsRead(string $notificationId): void
    {
        $notification = Notification::find($notificationId);

        if ($notification && $notification->notifiable_id === Auth::id()) {
            $notification->markAsRead();
            $this->loadNotifications();
        }
    }

    public function markAllAsRead(): void
    {
        Notification::where('notifiable_id', Auth::id())
            ->where('notifiable_type', get_class(Auth::user()))
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $this->loadNotifications();
    }

    protected function getNotificationType(string $type): string
    {
        $parts = explode('\\', $type);
        $className = end($parts);

        return match (true) {
            str_contains($className, 'Sale') => 'sales',
            str_contains($className, 'Purchase') => 'purchases',
            str_contains($className, 'Stock') || str_contains($className, 'Inventory') => 'inventory',
            str_contains($className, 'Employee') || str_contains($className, 'HR') => 'hr',
            str_contains($className, 'Payment') => 'payment',
            default => 'system'
        };
    }

    protected function getNotificationIcon(string $type): string
    {
        $category = $this->getNotificationType($type);

        return match ($category) {
            'sales' => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z',
            'purchases' => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z',
            'inventory' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
            'hr' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
            'payment' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
            default => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9'
        };
    }

    protected function getNotificationColor(string $type): string
    {
        $category = $this->getNotificationType($type);

        return match ($category) {
            'sales' => 'green',
            'purchases' => 'blue',
            'inventory' => 'purple',
            'hr' => 'rose',
            'payment' => 'emerald',
            default => 'gray'
        };
    }

    public function render()
    {
        return view('livewire.components.notifications-center');
    }
}
