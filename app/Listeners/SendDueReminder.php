<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ContractDueSoon;
use App\Notifications\GeneralNotification;
use App\Services\BranchContextManager;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendDueReminder implements ShouldQueue
{
    public function handle(ContractDueSoon $event): void
    {
        $contract = $event->contract;

        // V23-CRIT-04 FIX: Set explicit branch context for queue workers
        // BranchScope fails in queue because there's no authenticated user
        $branchId = $contract->branch_id ?? null;
        if ($branchId) {
            BranchContextManager::setBranchContext($branchId);
        }

        try {
            // V23-CRIT-04 FIX: Use withoutGlobalScopes() to bypass BranchScope when loading tenant
            // since queue workers have no authenticated user context
            $tenant = $contract->tenant()
                ->withoutGlobalScopes()
                ->first();

            $title = __('Rent due soon');
            $body = __('Your rent is due on :date for unit :unit', [
                'date' => optional($contract->end_date)->toDateString(),
                'unit' => $contract->unit?->code,
            ]);

            if ($tenant && method_exists($tenant, 'notify')) {
                $tenant->notify(new GeneralNotification($title, $body, [
                    'contract_id' => $contract->getKey(),
                ], sendMail: true));
            }
        } finally {
            // V23-CRIT-04 FIX: Always clear branch context to prevent leakage
            if ($branchId) {
                BranchContextManager::clearBranchContext();
            }
        }
    }
}
