<?php

declare(strict_types=1);

namespace App\Livewire\Banking;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Bank Reconciliation Wizard
 *
 * A step-by-step wizard for reconciling bank statements with system transactions
 * Features:
 * - Step 1: Select account and date range
 * - Step 2: Enter statement balance
 * - Step 3: Match/unmatch transactions
 * - Step 4: Review and complete
 */
#[Layout('layouts.app')]
class Reconciliation extends Component
{
    // V23-CRIT-03 FIX: Add AuthorizesRequests trait for $this->authorize() support
    use AuthorizesRequests;

    // Wizard state
    public int $currentStep = 1;

    public int $totalSteps = 4;

    // Step 1: Account selection
    public ?int $accountId = null;

    public string $startDate = '';

    public string $endDate = '';

    // Step 2: Statement balance
    public float $statementBalance = 0;

    public string $statementDate = '';

    // Step 3: Transaction matching
    public array $matchedTransactions = [];

    public array $unmatchedTransactions = [];

    // Step 4: Summary
    public float $systemBalance = 0;

    public float $difference = 0;

    public string $notes = '';

    public function mount(): void
    {
        $this->authorize('banking.reconcile');
        $this->startDate = now()->startOfMonth()->toDateString();
        $this->endDate = now()->endOfMonth()->toDateString();
        $this->statementDate = now()->toDateString();
    }

    /**
     * Move to next step with validation
     */
    public function nextStep(): void
    {
        if ($this->validateCurrentStep()) {
            if ($this->currentStep < $this->totalSteps) {
                $this->currentStep++;

                // Load data for step 3
                if ($this->currentStep === 3) {
                    $this->loadTransactions();
                }

                // Calculate summary for step 4
                if ($this->currentStep === 4) {
                    $this->calculateSummary();
                }
            }
        }
    }

    /**
     * Move to previous step
     */
    public function previousStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    /**
     * Go to specific step
     */
    public function goToStep(int $step): void
    {
        if ($step >= 1 && $step <= $this->currentStep) {
            $this->currentStep = $step;
        }
    }

    /**
     * Validate current step before proceeding
     */
    protected function validateCurrentStep(): bool
    {
        $rules = match ($this->currentStep) {
            1 => [
                'accountId' => 'required|exists:bank_accounts,id',
                'startDate' => 'required|date',
                'endDate' => 'required|date|after_or_equal:startDate',
            ],
            2 => [
                'statementBalance' => 'required|numeric',
                'statementDate' => 'required|date',
            ],
            3 => [],
            4 => [],
            default => [],
        };

        if (empty($rules)) {
            return true;
        }

        $this->validate($rules);

        return true;
    }

    /**
     * Load transactions for matching
     */
    protected function loadTransactions(): void
    {
        $transactions = BankTransaction::where('bank_account_id', $this->accountId)
            ->whereBetween('transaction_date', [$this->startDate, $this->endDate])
            ->orderBy('transaction_date', 'desc')
            ->get();

        // V23-CRIT-03 FIX: Use 'status' field and 'reconciled' value instead of 'is_reconciled' boolean
        // Also use 'reference_number' instead of 'reference'
        $this->unmatchedTransactions = $transactions
            ->where('status', '!=', 'reconciled')
            ->map(fn ($t) => [
                'id' => $t->id,
                'date' => $t->transaction_date->format('Y-m-d'),
                'description' => $t->description,
                'reference' => $t->reference_number,
                'amount' => $t->amount,
                'type' => $t->type,
                'matched' => false,
            ])
            ->values()
            ->toArray();

        $this->matchedTransactions = $transactions
            ->where('status', 'reconciled')
            ->map(fn ($t) => [
                'id' => $t->id,
                'date' => $t->transaction_date->format('Y-m-d'),
                'description' => $t->description,
                'reference' => $t->reference_number,
                'amount' => $t->amount,
                'type' => $t->type,
                'matched' => true,
            ])
            ->values()
            ->toArray();
    }

    /**
     * Toggle transaction match status
     */
    public function toggleMatch(int $transactionId): void
    {
        // Find in unmatched
        foreach ($this->unmatchedTransactions as $index => $transaction) {
            if ($transaction['id'] === $transactionId) {
                $transaction['matched'] = true;
                $this->matchedTransactions[] = $transaction;
                unset($this->unmatchedTransactions[$index]);
                $this->unmatchedTransactions = array_values($this->unmatchedTransactions);
                $this->calculateSummary();

                return;
            }
        }

        // Find in matched
        foreach ($this->matchedTransactions as $index => $transaction) {
            if ($transaction['id'] === $transactionId) {
                $transaction['matched'] = false;
                $this->unmatchedTransactions[] = $transaction;
                unset($this->matchedTransactions[$index]);
                $this->matchedTransactions = array_values($this->matchedTransactions);
                $this->calculateSummary();

                return;
            }
        }
    }

    /**
     * Match all transactions
     */
    public function matchAll(): void
    {
        foreach ($this->unmatchedTransactions as $transaction) {
            $transaction['matched'] = true;
            $this->matchedTransactions[] = $transaction;
        }
        $this->unmatchedTransactions = [];
        $this->calculateSummary();
    }

    /**
     * Unmatch all transactions
     */
    public function unmatchAll(): void
    {
        foreach ($this->matchedTransactions as $transaction) {
            $transaction['matched'] = false;
            $this->unmatchedTransactions[] = $transaction;
        }
        $this->matchedTransactions = [];
        $this->calculateSummary();
    }

    /**
     * Calculate reconciliation summary
     */
    protected function calculateSummary(): void
    {
        // V23-CRIT-03 FIX: Use signed amounts (deposits = +, withdrawals = -)
        // instead of raw sum which ignores transaction type
        $matchedTotal = collect($this->matchedTransactions)->sum(function ($t) {
            $amount = (float) $t['amount'];
            // Deposits and interest are positive, all others (withdrawals) are negative
            if (in_array($t['type'], ['deposit', 'interest'])) {
                return $amount;
            }

            return -$amount;
        });

        $account = BankAccount::find($this->accountId);
        $this->systemBalance = $account ? ($account->current_balance ?? 0) : 0;

        $this->difference = $this->statementBalance - $matchedTotal;
    }

    /**
     * Complete the reconciliation
     */
    public function complete(): void
    {
        if (abs($this->difference) > 0.01) {
            session()->flash('warning', __('There is still a difference of :amount. Are you sure you want to complete?', [
                'amount' => number_format($this->difference, 2),
            ]));
        }

        // V23-CRIT-03 FIX: Use correct field names (status, reconciliation_id)
        // and ensure transactions belong to the selected bank account
        // Note: reconciliation_id is left as null since this simple wizard doesn't create
        // a BankReconciliation record. For full reconciliation with tracking, use BankingService.
        $matchedIds = collect($this->matchedTransactions)->pluck('id');
        BankTransaction::whereIn('id', $matchedIds)
            ->where('bank_account_id', $this->accountId) // Ensure transactions belong to this account
            ->update([
                'status' => 'reconciled',
            ]);

        // Update account last reconciled date
        BankAccount::where('id', $this->accountId)->update([
            'last_reconciled_at' => now(),
            'last_reconciled_balance' => $this->statementBalance,
        ]);

        session()->flash('success', __('Reconciliation completed successfully. :count transactions reconciled.', [
            'count' => count($this->matchedTransactions),
        ]));

        $this->redirect(route('banking.index'));
    }

    public function render()
    {
        $accounts = BankAccount::where('branch_id', auth()->user()->branch_id)
            ->orderBy('account_name')
            ->get();

        $selectedAccount = $this->accountId ? BankAccount::find($this->accountId) : null;

        return view('livewire.banking.reconciliation', [
            'accounts' => $accounts,
            'selectedAccount' => $selectedAccount,
        ]);
    }
}
