<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GeneralNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected string $title,
        protected string $body,
        protected array $data = [],
        protected bool $sendMail = false,
        protected bool $shouldBroadcast = true
    ) {}

    /**
     * تحديد القنوات (database + mail + broadcast).
     */
    public function via($notifiable): array
    {
        $channels = ['database'];

        if ($this->sendMail && method_exists($notifiable, 'routeNotificationForMail')) {
            $channels[] = 'mail';
        }

        if ($this->shouldBroadcast && $notifiable->shouldReceiveBroadcastNotifications()) {
            $channels[] = 'broadcast';
        }

        return $channels;
    }

    /**
     * رسالة البريد الإلكتروني (إذا طُلب).
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title)
            ->greeting(__('notifications.greeting'))
            ->line($this->body)
            ->when(! empty($this->data['action_url'] ?? ''), function (MailMessage $mail) {
                $mail->action(__('notifications.view_details'), $this->data['action_url']);
            })
            ->salutation(config('app.name'));
    }

    /**
     * تخزين في قاعدة البيانات.
     */
    public function toArray($notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'data' => $this->data,
            'read' => false,
            'created_at' => now()->toISOString(),
            'type' => 'general',
        ];
    }

    /**
     * بث فوري (Realtime).
     */
    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'data' => $this->data,
            'read' => false,
            'created_at' => now()->toISOString(),
            'type' => 'general',
        ]);
    }

    /**
     * تحكم دقيق في إرسال البث.
     */
    public function shouldSend($notifiable, $channel): bool
    {
        if ($channel === 'broadcast') {
            return $this->shouldBroadcast && $notifiable->shouldReceiveBroadcastNotifications();
        }

        return true;
    }

    /**
     * اسم الحدث في الـ Frontend (Vue 3).
     */
    public function broadcastAs(): string
    {
        return 'notification.received';
    }

    /**
     * القناة الخاصة لكل مستخدم.
     */
    public function broadcastOn($notifiable): array
    {
        return [
            // Laravel will prefix the channel with "private-" when using PrivateChannel.
            new PrivateChannel('App.Models.User.'.$notifiable->id),
        ];
    }
}
