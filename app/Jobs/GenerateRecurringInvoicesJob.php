<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Branch;
use App\Services\BranchContextManager;
use App\Services\RentalService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateRecurringInvoicesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public $timeout = 300;

    public function __construct(
        public ?string $forDate = null,
        public ?int $branchId = null
    ) {}

    public function handle(): void
    {
        $target = $this->forDate ? \Carbon\Carbon::parse($this->forDate) : now();
        
        // MED-05 FIX: Use RentalService instead of duplicating logic
        // This ensures consistent code, due_date, and status handling
        $rentalService = app(RentalService::class);

        // If branchId specified, process only that branch
        if ($this->branchId) {
            // HIGH-08 FIX: Set branch context for BranchScope to work properly
            BranchContextManager::setBranchContext($this->branchId);
            try {
                $result = $rentalService->generateRecurringInvoicesForMonth($this->branchId, $target);
                Log::info('Generated recurring invoices', [
                    'branch_id' => $this->branchId,
                    'period' => $target->format('Y-m'),
                    'generated' => $result['success_count'] ?? 0,
                ]);
            } finally {
                BranchContextManager::clearBranchContext();
            }
        } else {
            // Process all active branches
            $branches = Branch::where('is_active', true)->get();
            foreach ($branches as $branch) {
                BranchContextManager::setBranchContext($branch->id);
                try {
                    $result = $rentalService->generateRecurringInvoicesForMonth($branch->id, $target);
                    Log::info('Generated recurring invoices', [
                        'branch_id' => $branch->id,
                        'period' => $target->format('Y-m'),
                        'generated' => $result['success_count'] ?? 0,
                    ]);
                } finally {
                    BranchContextManager::clearBranchContext();
                }
            }
        }
    }

    public function tags(): array
    {
        return ['rental', 'recurring', 'period:'.($this->forDate ?? now()->format('Y-m'))];
    }
}
