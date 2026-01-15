<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Branch;
use App\Services\BranchContextManager;
use App\Services\POSService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ClosePosDay extends Command
{
    protected $signature = 'pos:close-day {--branch=} {--date=} {--force}';

    protected $description = 'Close POS business day per branch, post X/Z reports, and finalize receipts.';

    public function __construct(private readonly POSService $posService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $dateStr = $this->option('date') ?: Carbon::today()->toDateString();
        $force = (bool) $this->option('force');
        $branchOpt = $this->option('branch');

        $this->info("POS Close Day started for date: {$dateStr}".($branchOpt ? " | branch: {$branchOpt}" : ' | all eligible branches'));

        // Resolve branches
        $branches = collect();

        if ($branchOpt) {
            $branches = Branch::query()
                ->where('id', $branchOpt)
                ->orWhere('code', $branchOpt)
                ->get();

            if ($branches->isEmpty()) {
                $this->error("Branch not found by id/code: {$branchOpt}");

                return self::FAILURE;
            }
        } else {
            $branches = Branch::query()
                ->where('is_active', true) // افترضت أن الحقل اسمه is_active بدلاً من active
                ->get();
        }

        $date = Carbon::parse($dateStr)->startOfDay();
        $totalClosed = 0;

        foreach ($branches as $branch) {
            $lockKey = "cmd:pos:close-day:{$branch->id}:{$date->toDateString()}";
            $lock = Cache::lock($lockKey, 600); // 10 minutes

            if (! $lock->get()) {
                $this->warn("Skipped (locked) branch={$branch->id} {$branch->name}");

                continue;
            }

            try {
                Log::info('POS close-day started', [
                    'branch_id' => $branch->id,
                    'date' => $date->toDateString(),
                    'force' => $force,
                    'request_id' => app()->bound('request_id') ? app('request_id') : null,
                ]);

                // HIGH-08 FIX: Set branch context for BranchScope to work properly
                BranchContextManager::setBranchContext($branch->id);
                try {
                    $result = $this->posService->closeDay(
                        branch: $branch,
                        date: $date,
                        force: $force
                    );

                    // Safe extraction of values before printing
                    $sales = $result['sales'] ?? 0;
                    $receipts = $result['receipts'] ?? 0;

                    $this->line("Closed branch={$branch->code} ({$branch->name}) | sales={$sales} | receipts={$receipts}");
                    $totalClosed++;

                    Log::info('POS close-day finished', [
                        'branch_id' => $branch->id,
                        'date' => $date->toDateString(),
                        'result' => $result,
                    ]);
                } finally {
                    BranchContextManager::clearBranchContext();
                }
            } catch (\Throwable $e) {
                Log::error('POS close-day error', [
                    'branch_id' => $branch->id,
                    'date' => $date->toDateString(),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                $this->error("Error on branch={$branch->code}: ".$e->getMessage());
            } finally {
                optional($lock)->release();
            }
        }

        $this->info("POS Close Day completed. Total branches closed: {$totalClosed}");

        return self::SUCCESS;
    }
}
