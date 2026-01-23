<?php

declare(strict_types=1);

namespace App\Livewire\Banking;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Rules\BranchScopedExists;
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

    // V28-MEDIUM-04 FIX: Tolerance threshold for reconciliation balance difference
    private const TOLERANCE_THRESHOLD = '0.01';

    // Wizard state
    public int $currentStep = 1;

    public int $totalSteps = 4;

    // Step 1: Account selection
    public ?int $accountId = null;

    public string $startDate = '';

    public string $endDate = '';

    // Step 2: Statement balance
    // V28-MEDIUM-04/05 FIX: Use string for statement balance to avoid float precision loss
    public string $statementBalance = '0';

    public string $statementDate = '';

    // Step 3: Transaction matching
    public array $matchedTransactions = [];

    public array $unmatchedTransactions = [];

    // Step 4: Summary
    // V28-MEDIUM-04 FIX: Use string for financial values to maintain precision
    public string $systemBalance = '0';

    public string $difference = '0';

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
                // V57-CRITICAL-03 FIX: Use BranchScopedExists to prevent cross-branch bank account access
                'accountId' => ['required', new BranchScopedExists('bank_accounts')],
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
     * V26-CRIT-01 FIX: Expose matched total for UI display
     * This is calculated using signed amounts (deposits = +, withdrawals = -)
     * V28-MEDIUM-04 FIX: Use string to maintain precision
     */
    public string $matchedTotal = '0';

    /**
     * Calculate reconciliation summary
     * V27-CRIT-03 FIX: Calculate difference using matched transactions total instead of system balance
     * V28-MEDIUM-04 FIX: Use bcmath with scale=4 for consistent precision
     */
    protected function calculateSummary(): void
    {
        // V23-CRIT-03 FIX: Use signed amounts (deposits = +, withdrawals = -)
        // instead of raw sum which ignores transaction type
        // V26-CRIT-01 FIX: Store matchedTotal as a public property for use in both
        // the difference calculation and the UI display
        // V28-MEDIUM-04 FIX: Use bcmath for precise calculations
        $matchedTotal = '0';
        foreach ($this->matchedTransactions as $t) {
            $amount = (string) $t['amount'];
            // Deposits and interest are positive, all others (withdrawals) are negative
            if (in_array($t['type'], ['deposit', 'interest'])) {
                $matchedTotal = bcadd($matchedTotal, $amount, 4);
            } else {
                $matchedTotal = bcsub($matchedTotal, $amount, 4);
            }
        }
        $this->matchedTotal = $matchedTotal;

        $account = BankAccount::find($this->accountId);
        $this->systemBalance = $account ? (string) ($account->current_balance ?? '0') : '0';

        // V27-CRIT-03 FIX: Calculate difference using matchedTotal, not systemBalance
        //
        // This implements a simple "matched transactions" reconciliation approach:
        // 1. User enters statement ending balance from bank statement
        // 2. User matches transactions from the system that appear on the statement
        // 3. The difference = statementBalance - matchedTotal
        //
        // Interpretation of difference:
        // - If 0: All statement transactions are matched (reconciled)
        // - If positive: Statement shows more money than matched (missing deposits or extra withdrawals)
        // - If negative: Statement shows less money than matched (extra deposits or missing withdrawals)
        //
        // Previous implementation used systemBalance which ignores what the user actually selected/matched.
        // That approach was incorrect because the user's selection of matched transactions is the core
        // of the reconciliation process.
        // V28-MEDIUM-04 FIX: Use bcmath for precise difference calculation
        $this->difference = bcsub($this->statementBalance, $this->matchedTotal, 4);
    }

    /**
     * Complete the reconciliation
     * V28-MEDIUM-04 FIX: Use bccomp for string-based comparison
     */
    public function complete(): void
    {
        // V58-HIGH-01 FIX: Re-authorize on mutation to prevent direct method calls
        $this->authorize('banking.reconcile');

        // V28-MEDIUM-04 FIX: Use bccomp to compare string difference with tolerance threshold
        $negativeThreshold = '-'.self::TOLERANCE_THRESHOLD;
        if (bccomp($this->difference, self::TOLERANCE_THRESHOLD, 4) > 0 || bccomp($this->difference, $negativeThreshold, 4) < 0) {
            session()->flash('warning', __('There is still a difference of :amount. Are you sure you want to complete?', [
                // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
                'amount' => number_format(decimal_float($this->difference), 2),
            ]));
        }

        // V23-CRIT-03 FIX: Use correct field names (status, reconciliation_id)
        // and ensure transactions belong to the selected bank account
        // Note: reconciliation_id is left as null since this simple wizard doesn't create
        // a BankReconciliation record. For full reconciliation with tracking, use BankingService.
        $matchedIds = collect($this->matchedTransactions)->pluck('id');

        // V25-HIGH-02 FIX: Only update transactions that fall within the selected date range
        // This prevents reconciling transactions from outside the statement period
        BankTransaction::whereIn('id', $matchedIds)
            ->where('bank_account_id', $this->accountId) // Ensure transactions belong to this account
            ->whereBetween('transaction_date', [$this->startDate, $this->endDate]) // V25-HIGH-02 FIX
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

        $this->redirect(route('app.banking.index'));
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
