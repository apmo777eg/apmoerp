<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ContractOverdue;
use App\Models\RentalInvoice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ApplyLateFee implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        protected float $penaltyPercent = 2.0, // default 2%
        protected float $minPenalty = 10.0
    ) {}

    public function handle(ContractOverdue $event): void
    {
        /** @var \App\Models\RentalContract $contract */
        $contract = $event->contract;
        $invoice = RentalInvoice::query()
            ->where('contract_id', $contract->getKey())
            ->where('status', 'unpaid')
            ->orderBy('due_date')
            ->first();

        if (! $invoice) {
            return;
        }

        $base = (string) $invoice->amount;
        // Use bcmath for precise late fee calculation
        $penaltyRate = bcdiv((string) $this->penaltyPercent, '100', 6);
        $penaltyFromRate = bcmul($base, $penaltyRate, 2);
        // Use bcmath comparison for precision
        $minPenaltyString = (string) $this->minPenalty;
        $penalty = bccomp($penaltyFromRate, $minPenaltyString, 2) >= 0 ? $penaltyFromRate : $minPenaltyString;
        $newAmount = bcadd($base, $penalty, 2);
        $invoice->amount = decimal_float($newAmount);
        $invoice->save();
    }
}
