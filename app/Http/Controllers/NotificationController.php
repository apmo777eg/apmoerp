<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Contracts\NotificationServiceInterface as Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function __construct(protected Notifier $notify) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);

        $rows = DB::table('notifications')
            ->where('notifiable_id', $user->getKey())
            ->where('notifiable_type', get_class($user))
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return $this->ok($rows);
    }

    public function unreadCount(Request $request)
    {
        $user = $request->user();
        $count = DB::table('notifications')
            ->where('notifiable_id', $user->getKey())
            ->where('notifiable_type', get_class($user))
            ->whereNull('deleted_at')
            ->whereNull('read_at')
            ->count();

        return $this->ok(['count' => (int) $count]);
    }

    public function markRead(Request $request, string $id)
    {
        $this->notify->markRead($request->user()->getKey(), $id);

        return $this->ok(['id' => $id], __('Notification marked as read'));
    }

    public function markMany(Request $request)
    {
        $ids = (array) $request->input('ids', []);
        $count = $this->notify->markManyRead($request->user()->getKey(), $ids);

        return $this->ok(['updated' => $count], __('Notifications updated'));
    }

    public function markAll(Request $request)
    {
        $user = $request->user();
        $userId = $user->getKey();
        $ids = DB::table('notifications')
            ->where('notifiable_id', $userId)
            ->where('notifiable_type', get_class($user))
            ->whereNull('deleted_at')
            ->pluck('id')
            ->all();
        $count = $this->notify->markManyRead($userId, $ids);

        return $this->ok(['updated' => $count], __('All notifications marked as read'));
    }
}
