<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * PurchaseReceived
 *
 * This event may be dispatched inside DB::transaction() blocks.
 * Implementing ShouldDispatchAfterCommit ensures queued listeners
 * (stock updates, accounting hooks, etc.) run only after commit.
 */
class PurchaseReceived implements ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public \App\Models\Purchase $purchase
    ) {}
}
