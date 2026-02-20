<?php

declare(strict_types=1);

namespace App\Livewire\Notifications;

use Livewire\Attributes\On;
use App\Livewire\BaseComponent as Component;
class Dropdown extends Component
{
    public int $unreadCount = 0;

    /**
     * @var array<int, array{type:string,message:string}>
     */
    public array $items = [];

    #[On('notification-received')]
    public function addNotification(string $type, string $message): void
    {
        $this->unreadCount++;

        array_unshift($this->items, [
            'type' => $type,
            'message' => $message,
        ]);

        $this->items = array_slice($this->items, 0, 10);
    }

    public function render()
    {
        return view('livewire.notifications.dropdown');
    }
}
