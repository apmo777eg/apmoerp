<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\StockBelowThreshold;
use App\Notifications\GeneralNotification;
use App\Services\BranchContextManager;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProposePurchaseOrder implements ShouldQueue
{
    public function handle(StockBelowThreshold $event): void
    {
        $product = $event->product;
        $warehouse = $event->warehouse;
        $current = $event->currentQty ?? 0.0;
        $threshold = $event->threshold ?? 0.0;

        // V23-CRIT-04 FIX: Set explicit branch context for queue workers
        // BranchScope fails in queue because there's no authenticated user
        $branchId = $warehouse?->branch_id ?? $product->branch_id ?? null;
        if ($branchId) {
            BranchContextManager::setBranchContext($branchId);
        }

        try {
            $title = __('Stock low: :name', ['name' => $product->name]);
            $body = __('Current :current below threshold :threshold in :wh', [
                'current' => number_format($current, 2),
                'threshold' => number_format($threshold, 2),
                'wh' => $warehouse?->name ?? 'N/A',
            ]);

            // V23-CRIT-04 FIX: Use withoutGlobalScopes() to bypass BranchScope
            // since queue workers have no authenticated user context
            $notifiables = \App\Models\User::query()
                ->withoutGlobalScopes()
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->whereHas('roles', fn ($q) => $q->where('name', 'Branch Manager'))
                ->get();

            foreach ($notifiables as $user) {
                $user->notify(new GeneralNotification($title, $body, [
                    'product_id' => $product->getKey(),
                    'warehouse_id' => $warehouse?->getKey(),
                ]));
            }
        } finally {
            // V23-CRIT-04 FIX: Always clear branch context to prevent leakage
            if ($branchId) {
                BranchContextManager::clearBranchContext();
            }
        }
    }
}
