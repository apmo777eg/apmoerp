<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Branch;
use App\Services\BranchContextManager;
use App\Services\RentalService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GenerateRecurringInvoices extends Command
{
    /**
     * Signature details:
     *  --branch=*   Repeatable; limit to specific branch IDs or codes. If omitted, runs for all active branches with Rentals enabled.
     *  --date=      Anchor date (Y-m-d). Defaults to today.
     */
    protected $signature = 'rental:generate-recurring {--branch=*} {--date=}';

    protected $description = 'Generate recurring rental invoices (due today/overdue as per contracts), per branch.';

    public function __construct(private readonly RentalService $rentalService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $dateStr = $this->option('date') ?: Carbon::today()->toDateString();
        $date = Carbon::parse($dateStr)->startOfDay();
        $targets = (array) $this->option('branch');

        $this->info("Recurring Invoices generation for {$dateStr}".(count($targets) ? ' | targeted branches supplied' : ' | all eligible branches'));

        // Resolve branches
        $branches = Branch::query()->where('is_active', true);
        if (! empty($targets)) {
            $branches->where(function ($q) use ($targets) {
                $q->whereIn('id', $targets)->orWhereIn('code', $targets);
            });
        }
        $branches = $branches->get();

        $totalInvoices = 0;

        foreach ($branches as $branch) {
            $lockKey = "cmd:rental:recurring:{$branch->id}:{$date->toDateString()}";
            $lock = Cache::lock($lockKey, 600);

            if (! $lock->get()) {
                $this->warn("Skipped (locked) branch={$branch->id} {$branch->name}");

                continue;
            }

            try {
                Log::info('Recurring invoices generation started', [
                    'branch_id' => $branch->id,
                    'date' => $date->toDateString(),
                ]);

                // HIGH-01 FIX: Call correct method name (generateRecurringInvoicesForMonth)
                // Also set branch context for BranchScope to work properly
                BranchContextManager::setBranchContext($branch->id);
                try {
                    $result = $this->rentalService->generateRecurringInvoicesForMonth($branch->id, $date);
                    $count = (int) ($result['success_count'] ?? 0);
                    $totalInvoices += $count;

                    $this->line("✔ Branch={$branch->code} ({$branch->name}) | generated={$count}");
                    Log::info('Recurring invoices generation finished', [
                        'branch_id' => $branch->id,
                        'date' => $date->toDateString(),
                        'generated' => $count,
                    ]);
                } finally {
                    BranchContextManager::clearBranchContext();
                }
            } catch (\Throwable $e) {
                Log::error('Recurring invoices error', [
                    'branch_id' => $branch->id,
                    'date' => $date->toDateString(),
                    'error' => $e->getMessage(),
                ]);
                $this->error("✖ Error on branch={$branch->code}: ".$e->getMessage());
            } finally {
                optional($lock)->release();
            }
        }

        $this->info("Done. Total generated invoices: {$totalInvoices}");

        return self::SUCCESS;
    }
}
