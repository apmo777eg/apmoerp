<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * SaleCompleted
 *
 * This event is often dispatched inside DB::transaction() blocks.
 * If it is dispatched before the transaction commits, queued listeners
 * may not see the SaleItems yet (or may see partial state), causing
 * stock updates and other side effects to be missed.
 */
class SaleCompleted implements ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public \App\Models\Sale $sale
    ) {}
}
