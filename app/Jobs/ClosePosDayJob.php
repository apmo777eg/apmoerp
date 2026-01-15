<?php

declare(strict_types=1);

namespace App\Jobs;

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

        // HIGH-08 FIX: Set branch context for BranchScope to work properly in queue
        if ($this->branchId) {
            BranchContextManager::setBranchContext($this->branchId);
        }

        try {
            // V6-MEDIUM-02 FIX: Filter only revenue statuses, exclude cancelled/void/returned sales
            $sales = \App\Models\Sale::query()
                ->whereDate('created_at', $date)
                ->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))
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
            if (class_exists(\App\Models\PosClosing::class)) {
                \App\Models\PosClosing::query()->create([
                    'branch_id' => $this->branchId,
                    'date' => $date,
                    'gross' => $gross,
                    'paid' => $paid,
                ]);
            } else {
                Log::info('POS closing summary', compact('date', 'gross', 'paid') + ['branch_id' => $this->branchId]);
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
