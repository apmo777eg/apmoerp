<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class InAppMessage extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected string $title,
        protected string $message,
        protected array $data = [],
        protected bool $shouldBroadcast = true
    ) {}

    /**
     * تحديد القنوات التي سيتم إرسال الإشعار عبرها.
     */
    public function via($notifiable): array
    {
        $channels = ['database'];

        if ($this->shouldBroadcast && $notifiable->shouldReceiveBroadcastNotifications()) {
            $channels[] = 'broadcast';
        }

        return $channels;
    }

    /**
     * تحويل الإشعار إلى مصفوفة للتخزين في قاعدة البيانات.
     */
    public function toArray($notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'data' => $this->data,
            'read' => false,
            'created_at' => now()->toISOString(),
        ];
    }

    /**
     * تحويل الإشعار إلى رسالة بث فوري (Realtime).
     */
    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'id' => $this->id,
            'title' => $this->title,
            'message' => $this->message,
            'data' => $this->data,
            'read' => false,
            'created_at' => now()->toISOString(),
            'type' => 'in_app',
        ]);
    }

    /**
     * تحديد متى يجب إرسال البث (يمكن التحكم به من خارج الكلاس).
     */
    public function shouldSend($notifiable, $channel): bool
    {
        if ($channel === 'broadcast') {
            return $this->shouldBroadcast && $notifiable->shouldReceiveBroadcastNotifications();
        }

        return true;
    }

    /**
     * اسم الحدث المستخدم في Echo (اختياري - يمكن تخصيصه).
     */
    public function broadcastAs(): string
    {
        return 'notification.received';
    }

    /**
     * القناة الخاصة (Private Channel) لكل مستخدم.
     */
    public function broadcastOn($notifiable): array
    {
        return [
            new \Illuminate\Broadcasting\PrivateChannel('App.Models.User.'.$notifiable->id),
        ];
    }
}
