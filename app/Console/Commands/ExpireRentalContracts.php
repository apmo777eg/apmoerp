<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\RentalContract;
use App\Models\RentalUnit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpireRentalContracts extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'rental:expire-contracts
                            {--date= : The date to check for expired contracts (default: today)}
                            {--dry-run : Preview changes without applying them}';

    /**
     * The console command description.
     */
    protected $description = 'Expire rental contracts that have passed their end date and release associated units';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $date = $this->option('date') ?: now()->toDateString();
        $dryRun = $this->option('dry-run');

        $this->info("Checking for rental contracts that expired before or on: {$date}");

        // MED-06 FIX: Use <= to match description "before or on"
        // Find active contracts that have passed their end date (including today)
        $expiredContracts = RentalContract::where('status', 'active')
            ->whereNotNull('end_date')
            ->where('end_date', '<=', $date)
            ->with('unit')
            ->get();

        if ($expiredContracts->isEmpty()) {
            $this->info('No expired contracts found.');

            return self::SUCCESS;
        }

        $this->info("Found {$expiredContracts->count()} expired contract(s).");

        $expiredCount = 0;
        $releasedUnits = 0;
        $errors = [];

        foreach ($expiredContracts as $contract) {
            try {
                if ($dryRun) {
                    $this->line("Would expire contract #{$contract->id} (end date: {$contract->end_date})");
                    if ($contract->unit) {
                        $this->line("  - Would release unit #{$contract->unit->id} ({$contract->unit->code})");
                    }
                    $expiredCount++;
                } else {
                    DB::transaction(function () use ($contract, &$expiredCount, &$releasedUnits) {
                        // Update contract status
                        $contract->status = 'expired';
                        $contract->save();

                        $this->line("Expired contract #{$contract->id} (end date: {$contract->end_date})");

                        // Release the associated unit
                        if ($contract->unit) {
                            $unit = $contract->unit;
                            if ($unit->status === 'occupied' || $unit->status === 'rented') {
                                $unit->status = 'available';
                                $unit->save();
                                $this->line("  - Released unit #{$unit->id} ({$unit->code})");
                                $releasedUnits++;
                            }
                        }

                        $expiredCount++;
                    });
                }
            } catch (\Exception $e) {
                $errorMsg = "Failed to expire contract #{$contract->id}: {$e->getMessage()}";
                $this->error($errorMsg);
                $errors[] = $errorMsg;
                Log::error('Rental contract expiration failed', [
                    'contract_id' => $contract->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($dryRun) {
            $this->info("\nDry run completed. No changes were made.");
        } else {
            $this->info("\nSuccessfully expired {$expiredCount} contract(s) and released {$releasedUnits} unit(s).");
        }

        if (! empty($errors)) {
            $this->error("\nErrors encountered: ".count($errors));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
