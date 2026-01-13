<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BankAccount;
use App\Models\BankReconciliation;
use App\Models\BankTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Service for banking and cashflow operations
 */
class BankingService
{
    /**
     * Record a bank transaction
     */
    public function recordTransaction(array $data): BankTransaction
    {
        return DB::transaction(function () use ($data) {
            $bankAccount = BankAccount::lockForUpdate()->findOrFail($data['bank_account_id']);

            $transaction = BankTransaction::create($data);

            // V7-MEDIUM-N08 FIX: Use bcmath for precise decimal arithmetic
            // Float arithmetic on decimal-casted fields causes precision loss
            // Keep as string for Laravel's decimal cast to handle properly
            $signedAmount = (string) $transaction->getSignedAmount();
            $currentBalance = (string) $bankAccount->current_balance;
            $newBalance = bcadd($currentBalance, $signedAmount, 4);
            
            // Assign string value - Laravel's decimal:4 cast handles conversion properly
            $bankAccount->current_balance = $newBalance;
            $transaction->balance_after = $newBalance;
            $transaction->save();

            $bankAccount->save();

            return $transaction;
        });
    }

    /**
     * Start a bank reconciliation
     */
    public function startReconciliation(
        int $bankAccountId,
        int $branchId,
        Carbon $statementDate,
        float $statementBalance
    ): BankReconciliation {
        $bankAccount = BankAccount::findOrFail($bankAccountId);

        // Calculate book balance at statement date
        $bookBalance = $this->calculateBookBalanceAt($bankAccountId, $statementDate);

        $difference = bcsub((string) $statementBalance, (string) $bookBalance, 2);

        return BankReconciliation::create([
            'bank_account_id' => $bankAccountId,
            'branch_id' => $branchId,
            'statement_date' => $statementDate,
            'reconciliation_date' => now(),
            'statement_balance' => $statementBalance,
            'book_balance' => $bookBalance,
            'difference' => (float) $difference,
            'status' => 'draft',
            'reconciled_by' => auth()->id(),
        ]);
    }

    /**
     * Mark transactions as reconciled
     */
    public function reconcileTransactions(BankReconciliation $reconciliation, array $transactionIds): void
    {
        DB::transaction(function () use ($reconciliation, $transactionIds) {
            BankTransaction::whereIn('id', $transactionIds)
                ->update([
                    'status' => 'reconciled',
                    'reconciliation_id' => $reconciliation->id,
                ]);

            // Recalculate difference using bcmath
            $depositsCollection = BankTransaction::where('reconciliation_id', $reconciliation->id)
                ->whereIn('type', ['deposit', 'interest'])
                ->get();

            $withdrawalsCollection = BankTransaction::where('reconciliation_id', $reconciliation->id)
                ->whereNotIn('type', ['deposit', 'interest'])
                ->get();

            $deposits = '0';
            foreach ($depositsCollection as $txn) {
                $deposits = bcadd($deposits, (string) $txn->amount, 2);
            }

            $withdrawals = '0';
            foreach ($withdrawalsCollection as $txn) {
                $withdrawals = bcadd($withdrawals, (string) $txn->amount, 2);
            }

            $reconciledTotal = bcsub($deposits, $withdrawals, 2);
            $statementMinusBook = bcsub((string) $reconciliation->statement_balance, (string) $reconciliation->book_balance, 2);
            $newDifference = bcsub($statementMinusBook, $reconciledTotal, 2);

            $reconciliation->update(['difference' => (float) $newDifference]);
        });
    }

    /**
     * Complete reconciliation
     */
    public function completeReconciliation(BankReconciliation $reconciliation): void
    {
        if (! $reconciliation->isBalanced()) {
            throw new \Exception('Reconciliation is not balanced. Cannot complete.');
        }

        $reconciliation->update([
            'status' => 'completed',
            'reconciliation_date' => now(),
        ]);
    }

    /**
     * Calculate book balance at a specific date
     */
    protected function calculateBookBalanceAt(int $bankAccountId, Carbon $date): float
    {
        $bankAccount = BankAccount::findOrFail($bankAccountId);

        $transactions = BankTransaction::where('bank_account_id', $bankAccountId)
            ->where('transaction_date', '<=', $date)
            ->where('status', '!=', 'cancelled')
            ->get();

        $balance = (string) $bankAccount->opening_balance;

        foreach ($transactions as $transaction) {
            if ($transaction->isDeposit() || $transaction->type === 'interest') {
                $balance = bcadd($balance, (string) $transaction->amount, 2);
            } else {
                $balance = bcsub($balance, (string) $transaction->amount, 2);
            }
        }

        return (float) $balance;
    }

    /**
     * Get cashflow summary for a period
     */
    public function getCashflowSummary(int $branchId, Carbon $startDate, Carbon $endDate): array
    {
        $transactions = BankTransaction::where('branch_id', $branchId)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled')
            ->get();

        $inflows = '0';
        $outflows = '0';

        foreach ($transactions as $transaction) {
            if ($transaction->isDeposit() || $transaction->type === 'interest') {
                $inflows = bcadd($inflows, (string) $transaction->amount, 2);
            } else {
                $outflows = bcadd($outflows, (string) $transaction->amount, 2);
            }
        }

        $netCashflow = bcsub($inflows, $outflows, 2);

        return [
            'total_inflows' => (float) $inflows,
            'total_outflows' => (float) $outflows,
            'net_cashflow' => (float) $netCashflow,
            'transaction_count' => $transactions->count(),
        ];
    }

    /**
     * Import transactions from CSV/Excel
     */
    public function importTransactions(int $bankAccountId, array $transactions): array
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($transactions as $txn) {
            try {
                // Check if transaction already exists
                $exists = BankTransaction::where('bank_account_id', $bankAccountId)
                    ->where('reference_number', $txn['reference_number'] ?? '')
                    ->exists();

                if ($exists) {
                    $skipped++;

                    continue;
                }

                $this->recordTransaction([
                    'bank_account_id' => $bankAccountId,
                    'branch_id' => $txn['branch_id'],
                    'reference_number' => $txn['reference_number'] ?? null,
                    'transaction_date' => $txn['transaction_date'],
                    'type' => $txn['type'],
                    'amount' => $txn['amount'],
                    'description' => $txn['description'] ?? null,
                    'payee_payer' => $txn['payee_payer'] ?? null,
                    'status' => 'cleared',
                    'created_by' => auth()->id(),
                ]);

                $imported++;
            } catch (\Exception $e) {
                $errors[] = [
                    'reference' => $txn['reference_number'] ?? 'Unknown',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * Get current balance for an account
     */
    public function getAccountBalance(int $accountId): float
    {
        $account = BankAccount::findOrFail($accountId);

        return (float) $account->current_balance;
    }

    /**
     * Check if account has sufficient balance for a withdrawal
     */
    public function hasSufficientBalance(int $accountId, float $amount): bool
    {
        $balance = $this->getAccountBalance($accountId);

        return bccomp((string) $balance, (string) $amount, 2) >= 0;
    }

    /**
     * Record a deposit transaction
     */
    public function recordDeposit(array $data): BankTransaction
    {
        return $this->recordTransaction([
            'bank_account_id' => $data['account_id'],
            'branch_id' => $data['branch_id'],
            'transaction_date' => $data['transaction_date'] ?? now(),
            'type' => 'deposit',
            'amount' => $data['amount'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? 'cleared',
            'created_by' => $data['created_by'] ?? auth()->id(),
            'reference_number' => $data['reference_number'] ?? null,
            'payee_payer' => $data['payee_payer'] ?? null,
        ]);
    }

    /**
     * Record a withdrawal transaction
     *
     * @throws \InvalidArgumentException if insufficient balance
     */
    public function recordWithdrawal(array $data): BankTransaction
    {
        // Check for sufficient balance before withdrawal using bcmath
        $availableBalance = $this->getAccountBalance($data['account_id']);
        if (bccomp((string) $availableBalance, (string) $data['amount'], 2) < 0) {
            throw new \InvalidArgumentException(sprintf(
                'Insufficient balance for withdrawal. Available: %.2f, Requested: %.2f',
                $availableBalance,
                $data['amount']
            ));
        }

        return $this->recordTransaction([
            'bank_account_id' => $data['account_id'],
            'branch_id' => $data['branch_id'],
            'transaction_date' => $data['transaction_date'] ?? now(),
            'type' => 'withdrawal',
            'amount' => $data['amount'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? 'cleared',
            'created_by' => $data['created_by'] ?? auth()->id(),
            'reference_number' => $data['reference_number'] ?? null,
            'payee_payer' => $data['payee_payer'] ?? null,
        ]);
    }
}
