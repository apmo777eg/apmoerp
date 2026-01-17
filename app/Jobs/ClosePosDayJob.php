<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Branch;
use App\Services\BranchContextManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ClosePosDayJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public $timeout = 300;

    public function __construct(public ?string $date = null, public ?int $branchId = null) {}

    public function handle(): void
    {
        $date = $this->date ?: now()->toDateString();

        // V22-MED-05 FIX: If no branchId is provided, process all active branches
        if ($this->branchId === null) {
            $activeBranches = Branch::withoutGlobalScopes()
                ->where('is_active', true)
                ->pluck('id');

            foreach ($activeBranches as $branchId) {
                $this->processClosingForBranch($date, $branchId);
            }
        } else {
            $this->processClosingForBranch($date, $this->branchId);
        }
    }

    /**
     * V22-MED-05 FIX: Process POS closing for a specific branch
     * V31-HIGH-04 FIX: Use sale_date instead of created_at for consistency with POSService
     */
    protected function processClosingForBranch(string $date, int $branchId): void
    {
        // HIGH-08 FIX: Set branch context for BranchScope to work properly in queue
        BranchContextManager::setBranchContext($branchId);

        try {
            // V31-HIGH-04 FIX: Use sale_date instead of created_at to be consistent with POSService
            // V6-MEDIUM-02 FIX: Filter only revenue statuses, exclude cancelled/void/returned sales
            $sales = \App\Models\Sale::query()
                ->whereDate('sale_date', $date)
                ->where('branch_id', $branchId)
                ->whereNotIn('status', ['cancelled', 'void', 'returned', 'refunded'])
                ->get(['total_amount', 'paid_amount']);

            // Use bcmath for precise financial totals
            $grossString = '0.00';
            $paidString = '0.00';

            foreach ($sales as $sale) {
                $grossString = bcadd($grossString, (string) $sale->total_amount, 2);
                $paidString = bcadd($paidString, (string) $sale->paid_amount, 2);
            }

            $gross = (float) $grossString;
            $paid = (float) $paidString;

            // Save a closing record if you have a model/table for that
            // V31-HIGH-04 FIX: closed_by is nullable for scheduled/CLI closings
            if (class_exists(\App\Models\PosClosing::class)) {
                \App\Models\PosClosing::query()->updateOrCreate(
                    [
                        'branch_id' => $branchId,
                        'date' => $date,
                    ],
                    [
                        'gross' => $gross,
                        'paid' => $paid,
                        'closed_by' => null, // V31-HIGH-04 FIX: Explicitly null for scheduled jobs
                    ]
                );
            } else {
                Log::info('POS closing summary', compact('date', 'gross', 'paid') + ['branch_id' => $branchId]);
            }
        } finally {
            // HIGH-08 FIX: Clear branch context to prevent leakage
            BranchContextManager::clearBranchContext();
        }
    }

    public function tags(): array
    {
        return ['pos', 'closing', 'date:'.($this->date ?? now()->toDateString())];
    }
}
