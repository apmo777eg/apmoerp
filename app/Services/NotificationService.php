<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\NotificationRead;
use App\Events\NotificationsMarkedAsRead;
use App\Events\RealTimeNotification;
use App\Events\UpdateNotificationCounters;
use App\Models\User;
use App\Notifications\InAppMessage;
use App\Services\Contracts\NotificationServiceInterface;
use App\Traits\HandlesServiceErrors;
use Illuminate\Support\Facades\DB;

class NotificationService implements NotificationServiceInterface
{
    use HandlesServiceErrors;

    /**
     * Predefined notification templates
     */
    protected array $templates = [
        'sale_created' => [
            'title' => 'فاتورة جديدة',
            'message' => 'تم إنشاء فاتورة بيع رقم :invoice_number بقيمة :total',
            'type' => 'success',
        ],
        'low_stock' => [
            'title' => 'تنبيه: نقص المخزون',
            'message' => 'المنتج :product_name وصل للحد الأدنى (:quantity متبقي)',
            'type' => 'warning',
        ],
        'payment_received' => [
            'title' => 'تم استلام دفعة',
            'message' => 'تم استلام دفعة بقيمة :amount من العميل :customer_name',
            'type' => 'success',
        ],
        'task_assigned' => [
            'title' => 'مهمة جديدة',
            'message' => 'تم تعيينك لمهمة: :task_title',
            'type' => 'info',
        ],
        'report_ready' => [
            'title' => 'التقرير جاهز',
            'message' => 'التقرير :report_name جاهز للتحميل',
            'type' => 'info',
        ],
        'approval_needed' => [
            'title' => 'مطلوب موافقة',
            'message' => ':item_type يحتاج موافقتك: :item_name',
            'type' => 'warning',
        ],
        'system_alert' => [
            'title' => 'تنبيه النظام',
            'message' => ':alert_message',
            'type' => 'error',
        ],
    ];

    /**
     * Send notification using a template
     */
    public function sendFromTemplate(int $userId, string $templateKey, array $replacements = [], array $extraData = []): void
    {
        $this->handleServiceOperation(
            callback: function () use ($userId, $templateKey, $replacements, $extraData) {
                $template = $this->templates[$templateKey] ?? null;

                if (! $template) {
                    throw new \InvalidArgumentException("Notification template '{$templateKey}' not found");
                }

                $title = $this->replacePlaceholders($template['title'], $replacements);
                $message = $this->replacePlaceholders($template['message'], $replacements);

                $data = array_merge($extraData, [
                    'type' => $template['type'],
                    'template' => $templateKey,
                ]);

                $this->inApp($userId, $title, $message, $data);
            },
            operation: 'sendFromTemplate',
            context: ['user_id' => $userId, 'template' => $templateKey]
        );
    }

    /**
     * Send template notification to many users
     */
    public function sendTemplateToMany(array $userIds, string $templateKey, array $replacements = [], array $extraData = []): void
    {
        $this->handleServiceOperation(
            callback: function () use ($userIds, $templateKey, $replacements, $extraData) {
                $template = $this->templates[$templateKey] ?? null;

                if (! $template) {
                    throw new \InvalidArgumentException("Notification template '{$templateKey}' not found");
                }

                $title = $this->replacePlaceholders($template['title'], $replacements);
                $message = $this->replacePlaceholders($template['message'], $replacements);

                $data = array_merge($extraData, [
                    'type' => $template['type'],
                    'template' => $templateKey,
                ]);

                $this->inAppToMany($userIds, $title, $message, $data);
            },
            operation: 'sendTemplateToMany',
            context: ['user_ids' => $userIds, 'template' => $templateKey]
        );
    }

    /**
     * Replace placeholders in template strings
     */
    protected function replacePlaceholders(string $text, array $replacements): string
    {
        foreach ($replacements as $key => $value) {
            $text = str_replace(":{$key}", (string) $value, $text);
        }

        return $text;
    }

    /**
     * Get available notification templates
     */
    public function getAvailableTemplates(): array
    {
        return array_keys($this->templates);
    }

    /**
     * Send an in-app notification to a user
     * Also broadcasts a real-time notification if broadcasting is enabled
     */
    public function inApp(int $userId, string $title, string $message, array $data = []): void
    {
        $this->handleServiceOperation(
            callback: function () use ($userId, $title, $message, $data) {
                $user = User::find($userId);
                if ($user) {
                    $user->notify(new InAppMessage($title, $message, $data));

                    // Broadcast real-time notification
                    event(new RealTimeNotification(
                        userId: $userId,
                        title: $title,
                        message: $message,
                        type: $data['type'] ?? 'info',
                        link: $data['link'] ?? null,
                        data: $data
                    ));
                }
                event(new UpdateNotificationCounters($userId));
            },
            operation: 'inApp',
            context: ['user_id' => $userId, 'title' => $title]
        );
    }

    /**
     * Send an in-app notification to multiple users
     * Optimized to batch operations where possible
     */
    public function inAppToMany(array $userIds, string $title, string $message, array $data = []): void
    {
        $this->handleServiceOperation(
            callback: function () use ($userIds, $title, $message, $data) {
                // Load all users at once to avoid N+1
                $users = User::whereIn('id', $userIds)->get();

                foreach ($users as $user) {
                    $user->notify(new InAppMessage($title, $message, $data));
                }

                // Broadcast real-time notifications in batch
                foreach ($userIds as $userId) {
                    event(new RealTimeNotification(
                        userId: $userId,
                        title: $title,
                        message: $message,
                        type: $data['type'] ?? 'info',
                        link: $data['link'] ?? null,
                        data: $data
                    ));
                    event(new UpdateNotificationCounters($userId));
                }
            },
            operation: 'inAppToMany',
            context: ['user_ids' => $userIds, 'title' => $title]
        );
    }

    /**
     * Broadcast a real-time notification without storing it
     * Useful for transient notifications like typing indicators
     */
    public function broadcast(int $userId, string $title, string $message, string $type = 'info', array $data = []): void
    {
        $this->handleServiceOperation(
            callback: function () use ($userId, $title, $message, $type, $data) {
                event(new RealTimeNotification(
                    userId: $userId,
                    title: $title,
                    message: $message,
                    type: $type,
                    link: $data['link'] ?? null,
                    data: $data
                ));
            },
            operation: 'broadcast',
            context: ['user_id' => $userId, 'title' => $title]
        );
    }

    public function email(string $to, string $subject, string $view, array $data = []): void
    {
        $this->handleServiceOperation(
            callback: fn () => dispatch(new \App\Jobs\SendEmailNotificationJob($to, $subject, $view, $data)),
            operation: 'email',
            context: ['to' => $to, 'subject' => $subject, 'view' => $view]
        );
    }

    public function sms(string $toPhone, string $message): void
    {
        $this->handleServiceOperation(
            callback: fn () => dispatch(new \App\Jobs\SendSmsNotificationJob($toPhone, $message)),
            operation: 'sms',
            context: ['to_phone' => $toPhone]
        );
    }

    public function markRead(int $userId, string $notificationId): void
    {
        $this->handleServiceOperation(
            callback: function () use ($userId, $notificationId) {
                DB::table('notifications')
                    ->where('id', $notificationId)
                    ->where('notifiable_id', $userId)
                    ->whereNull('deleted_at')
                    ->update(['read_at' => now()]);
                event(new NotificationRead($userId, $notificationId));
                event(new UpdateNotificationCounters($userId));
            },
            operation: 'markRead',
            context: ['user_id' => $userId, 'notification_id' => $notificationId]
        );
    }

    public function markManyRead(int $userId, array $ids): int
    {
        return $this->handleServiceOperation(
            callback: function () use ($userId, $ids) {
                $count = DB::table('notifications')
                    ->whereIn('id', $ids)
                    ->where('notifiable_id', $userId)
                    ->whereNull('deleted_at')
                    ->update(['read_at' => now()]);
                event(new NotificationsMarkedAsRead($userId, $ids));
                event(new UpdateNotificationCounters($userId));

                return (int) $count;
            },
            operation: 'markManyRead',
            context: ['user_id' => $userId, 'ids_count' => count($ids)],
            defaultValue: 0
        );
    }

    /**
     * Get unread notification count for a user
     * Uses Eloquent relationships for consistency and caching
     */
    public function getUnreadCount(int $userId): int
    {
        return $this->handleServiceOperation(
            callback: function () use ($userId) {
                $user = User::find($userId);

                return $user ? $user->unreadNotifications()->count() : 0;
            },
            operation: 'getUnreadCount',
            context: ['user_id' => $userId],
            defaultValue: 0
        );
    }

    /**
     * Get recent notifications for a user
     * Uses Eloquent relationships for consistency and caching
     */
    public function getRecent(int $userId, int $limit = 10): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($userId, $limit) {
                $user = User::find($userId);
                if (! $user) {
                    return [];
                }

                return $user->notifications()
                    ->orderByDesc('created_at')
                    ->limit($limit)
                    ->get()
                    ->map(function ($notification) {
                        $data = $notification->data ?? [];

                        return [
                            'id' => $notification->id,
                            'type' => $data['type'] ?? 'info',
                            'title' => $data['title'] ?? '',
                            'message' => $data['message'] ?? '',
                            'link' => $data['link'] ?? null,
                            'read_at' => $notification->read_at,
                            'created_at' => $notification->created_at,
                        ];
                    })
                    ->toArray();
            },
            operation: 'getRecent',
            context: ['user_id' => $userId, 'limit' => $limit],
            defaultValue: []
        );
    }
}
