<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreSyncLog extends BaseModel
{
    protected $fillable = [
        'store_id',
        'branch_id',
        'type',
        'direction',
        'status',
        'records_processed',
        'records_success',
        'records_failed',
        'details',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'records_processed' => 'integer',
        'records_success' => 'integer',
        'records_failed' => 'integer',
        'details' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public const TYPE_PRODUCTS = 'products';

    public const TYPE_ORDERS = 'orders';

    public const TYPE_CUSTOMERS = 'customers';

    public const TYPE_INVENTORY = 'inventory';

    public const DIRECTION_PULL = 'pull';

    public const DIRECTION_PUSH = 'push';

    public const STATUS_PENDING = 'pending';

    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function markCompleted(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    public function markFailed(string $errorMessage): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
            'completed_at' => now(),
        ]);
    }

    public function incrementSuccess(int $count = 1): void
    {
        $this->increment('records_success', $count);
        $this->increment('records_processed', $count);
    }

    public function incrementFailed(int $count = 1): void
    {
        $this->increment('records_failed', $count);
        $this->increment('records_processed', $count);
    }
}
