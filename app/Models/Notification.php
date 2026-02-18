<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\DatabaseNotification as BaseDatabaseNotification;

/**
 * Database notifications.
 *
 * IMPORTANT:
 * This project uses the canonical Laravel notifications table schema:
 *  - type, notifiable_type, notifiable_id, data, read_at, timestamps (+ soft deletes)
 *
 * Some legacy code previously treated this as a custom table with user_id/title/body,
 * which caused 500s because those columns do not exist. This model intentionally
 * extends Laravel's DatabaseNotification to stay aligned with migrations.
 */
class Notification extends BaseDatabaseNotification
{
    use SoftDeletes;
}
