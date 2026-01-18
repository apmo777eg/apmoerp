<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Account;
use App\Models\AccountMapping;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Purchase;
use App\Models\Sale;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * AccountingService - Journal entry generation and accounting operations
 *
 * SECURITY NOTE: All raw SQL expressions in this service use only hardcoded column names.
 * Parameters like $branchId and model IDs are passed through where() with proper binding.
 * No user input is interpolated into the SQL expressions.
 */
class AccountingService
{
    /**
     * Generate journal entry from a sale
     */
    public function generateSaleJournalEntry(Sale $sale): JournalEntry
    {
        if ($sale->journal_entry_id) {
            throw new Exception('Journal entry already generated for this sale');
        }

        return DB::transaction(function () use ($sale) {
            $fiscalPeriod = FiscalPeriod::getCurrentPeriod($sale->branch_id);

            // V7-CRITICAL-N01 FIX: Create entry as 'draft' first, then post properly
            $entry = JournalEntry::create([
                'branch_id' => $sale->branch_id,
                'reference_number' => $this->generateReferenceNumber('SALE', $sale->id),
                'entry_date' => $sale->posted_at ?? $sale->created_at,
                'description' => "Sale #{$sale->code}",
                'status' => 'draft', // Start as draft
                'source_module' => 'sales',
                'source_type' => 'Sale',
                'source_id' => $sale->id,
                'fiscal_year' => $fiscalPeriod?->year,
                'fiscal_period' => $fiscalPeriod?->period,
                'is_auto_generated' => true,
                // V33-CRIT-02 FIX: Use actual_user_id() for correct audit attribution during impersonation
                'created_by' => actual_user_id(),
            ]);

            $lines = [];

            // BUG FIX #3: Handle split payments - create separate debit entries for each payment method
            $sale->load('payments');
            $totalPaymentReceived = '0';

            if ($sale->payments->isNotEmpty()) {
                foreach ($sale->payments as $payment) {
                    $accountKey = match ($payment->payment_method) {
                        'card', 'credit_card', 'debit_card' => 'bank_account',
                        'transfer', 'bank_transfer' => 'bank_account',
                        'cheque', 'check' => 'cheque_account',
                        default => 'cash_account',
                    };

                    $paymentAccount = AccountMapping::getAccount('sales', $accountKey, $sale->branch_id);
                    if ($paymentAccount && $payment->amount > 0) {
                        $lines[] = [
                            'journal_entry_id' => $entry->id,
                            'account_id' => $paymentAccount->id,
                            'debit' => $payment->amount,
                            'credit' => 0,
                            'description' => "Payment received via {$payment->payment_method}",
                        ];
                        $totalPaymentReceived = bcadd($totalPaymentReceived, (string) $payment->amount, 2);
                    }
                }

                // FIX U-05: Add Accounts Receivable line for unpaid remainder (partial payments)
                // Use bccomp for proper decimal comparison instead of float cast
                $unpaidAmount = bcsub((string) $sale->total_amount, $totalPaymentReceived, 2);
                if (bccomp($unpaidAmount, '0', 2) > 0) {
                    $receivableAccount = AccountMapping::getAccount('sales', 'accounts_receivable', $sale->branch_id);
                    if ($receivableAccount) {
                        $lines[] = [
                            'journal_entry_id' => $entry->id,
                            'account_id' => $receivableAccount->id,
                            'debit' => (float) $unpaidAmount,
                            'credit' => 0,
                            'description' => "Account receivable (partial payment) - Customer #{$sale->customer_id}",
                        ];
                    }
                }
            } else {
                // Fallback: Debit Cash/Bank or Customer Account (Asset) if no payment records
                if ($sale->isPaid()) {
                    $cashAccount = AccountMapping::getAccount('sales', 'cash_account', $sale->branch_id);
                    if ($cashAccount) {
                        $lines[] = [
                            'journal_entry_id' => $entry->id,
                            'account_id' => $cashAccount->id,
                            'debit' => $sale->total_amount,
                            'credit' => 0,
                            'description' => 'Cash received from sale',
                        ];
                    }
                } else {
                    $receivableAccount = AccountMapping::getAccount('sales', 'accounts_receivable', $sale->branch_id);
                    if ($receivableAccount) {
                        $lines[] = [
                            'journal_entry_id' => $entry->id,
                            'account_id' => $receivableAccount->id,
                            'debit' => $sale->total_amount,
                            'credit' => 0,
                            'description' => "Account receivable - Customer #{$sale->customer_id}",
                        ];
                    }
                }
            }

            // Credit: Sales Revenue
            $revenueAccount = AccountMapping::getAccount('sales', 'sales_revenue', $sale->branch_id);
            if ($revenueAccount) {
                $lines[] = [
                    'journal_entry_id' => $entry->id,
                    'account_id' => $revenueAccount->id,
                    'debit' => 0,
                    'credit' => $sale->subtotal,
                    'description' => 'Sales revenue',
                ];
            }

            // Credit: Tax Payable (if applicable)
            if ($sale->tax_amount > 0) {
                $taxAccount = AccountMapping::getAccount('sales', 'tax_payable', $sale->branch_id);
                if ($taxAccount) {
                    $lines[] = [
                        'journal_entry_id' => $entry->id,
                        'account_id' => $taxAccount->id,
                        'debit' => 0,
                        'credit' => $sale->tax_amount,
                        'description' => 'Tax payable on sales',
                    ];
                }
            }

            // Credit: Discount (if applicable)
            if ($sale->discount_amount > 0) {
                $discountAccount = AccountMapping::getAccount('sales', 'sales_discount', $sale->branch_id);
                if ($discountAccount) {
                    $lines[] = [
                        'journal_entry_id' => $entry->id,
                        'account_id' => $discountAccount->id,
                        'debit' => $sale->discount_amount,
                        'credit' => 0,
                        'description' => 'Discount given',
                    ];
                }
            }

            // STILL-V7-CRITICAL-U03 FIX: Credit: Shipping Income (if applicable)
            // This ensures journal entries remain balanced when shipping_amount > 0
            if ($sale->shipping_amount > 0) {
                $shippingAccount = AccountMapping::getAccount('sales', 'shipping_income', $sale->branch_id);
                if ($shippingAccount) {
                    $lines[] = [
                        'journal_entry_id' => $entry->id,
                        'account_id' => $shippingAccount->id,
                        'debit' => 0,
                        'credit' => $sale->shipping_amount,
                        'description' => 'Shipping income',
                    ];
                }
            }

            foreach ($lines as $lineData) {
                JournalEntryLine::create($lineData);
            }

            // V7-CRITICAL-N01 FIX: Properly post the entry with validation and balance updates
            $this->postAutoGeneratedEntry($entry);

            // Update sale with journal entry
            $sale->update(['journal_entry_id' => $entry->id]);

            // BUG FIX #1: Generate COGS entry immediately after revenue entry
            $this->recordCogsEntry($sale);

            return $entry->fresh('lines');
        });
    }

    /**
     * Generate journal entry from a purchase
     */
    public function generatePurchaseJournalEntry(Purchase $purchase): JournalEntry
    {
        if (! empty($purchase->journal_entry_id)) {
            throw new Exception('Journal entry already generated for this purchase');
        }

        return DB::transaction(function () use ($purchase) {
            $fiscalPeriod = FiscalPeriod::getCurrentPeriod($purchase->branch_id);

            // V7-CRITICAL-N01 FIX: Create entry as 'draft' first, then post properly
            $entry = JournalEntry::create([
                'branch_id' => $purchase->branch_id,
                'reference_number' => $this->generateReferenceNumber('PURCH', $purchase->id),
                'entry_date' => $purchase->posted_at ?? $purchase->created_at,
                'description' => "Purchase Order #{$purchase->code}",
                'status' => 'draft', // Start as draft
                'source_module' => 'purchases',
                'source_type' => 'Purchase',
                'source_id' => $purchase->id,
                'fiscal_year' => $fiscalPeriod?->year,
                'fiscal_period' => $fiscalPeriod?->period,
                'is_auto_generated' => true,
                // V33-CRIT-02 FIX: Use actual_user_id() for correct audit attribution during impersonation
                'created_by' => actual_user_id(),
            ]);

            $lines = [];

            // Debit: Inventory/Expense
            $inventoryAccount = AccountMapping::getAccount('purchases', 'inventory_account', $purchase->branch_id);
            if ($inventoryAccount) {
                $lines[] = [
                    'journal_entry_id' => $entry->id,
                    'account_id' => $inventoryAccount->id,
                    'debit' => $purchase->subtotal,
                    'credit' => 0,
                    'description' => 'Inventory purchased',
                ];
            }

            // Debit: Tax Recoverable
            if ($purchase->tax_amount > 0) {
                $taxAccount = AccountMapping::getAccount('purchases', 'tax_recoverable', $purchase->branch_id);
                if ($taxAccount) {
                    $lines[] = [
                        'journal_entry_id' => $entry->id,
                        'account_id' => $taxAccount->id,
                        'debit' => $purchase->tax_amount,
                        'credit' => 0,
                        'description' => 'Tax recoverable on purchases',
                    ];
                }
            }

            // STILL-V7-CRITICAL-U03 FIX: Debit: Shipping Expense (if applicable)
            // This ensures journal entries remain balanced when shipping_amount > 0
            if ($purchase->shipping_amount > 0) {
                $shippingAccount = AccountMapping::getAccount('purchases', 'shipping_expense', $purchase->branch_id);
                if ($shippingAccount) {
                    $lines[] = [
                        'journal_entry_id' => $entry->id,
                        'account_id' => $shippingAccount->id,
                        'debit' => $purchase->shipping_amount,
                        'credit' => 0,
                        'description' => 'Shipping expense',
                    ];
                }
            }

            // Credit: Cash/Bank or Accounts Payable
            if ($purchase->isPaid()) {
                $cashAccount = AccountMapping::getAccount('purchases', 'cash_account', $purchase->branch_id);
                if ($cashAccount) {
                    $lines[] = [
                        'journal_entry_id' => $entry->id,
                        'account_id' => $cashAccount->id,
                        'debit' => 0,
                        'credit' => $purchase->total_amount,
                        'description' => 'Cash paid for purchase',
                    ];
                }
            } else {
                $payableAccount = AccountMapping::getAccount('purchases', 'accounts_payable', $purchase->branch_id);
                if ($payableAccount) {
                    $lines[] = [
                        'journal_entry_id' => $entry->id,
                        'account_id' => $payableAccount->id,
                        'debit' => 0,
                        'credit' => $purchase->total_amount,
                        'description' => "Account payable - Supplier #{$purchase->supplier_id}",
                    ];
                }
            }

            foreach ($lines as $lineData) {
                JournalEntryLine::create($lineData);
            }

            // V7-CRITICAL-N01 FIX: Properly post the entry with validation and balance updates
            $this->postAutoGeneratedEntry($entry);

            // Update purchase with journal entry
            $purchase->update(['journal_entry_id' => $entry->id]);

            return $entry->fresh('lines');
        });
    }

    /**
     * Generate reference number
     */
    protected function generateReferenceNumber(string $prefix, int $id): string
    {
        return sprintf('%s-%s-%06d', $prefix, date('Ymd'), $id);
    }

    /**
     * Validate journal entry balance
     */
    public function validateBalance(JournalEntry $entry): bool
    {
        // Critical ERP: Use bcmath for precise balance validation
        $totalDebit = '0';
        $totalCredit = '0';

        foreach ($entry->lines as $line) {
            $totalDebit = bcadd($totalDebit, (string) $line->debit, 2);
            $totalCredit = bcadd($totalCredit, (string) $line->credit, 2);
        }

        $difference = bcsub($totalDebit, $totalCredit, 2);

        // Log unbalanced entries for audit
        if (abs((float) $difference) >= 0.01) {
            Log::error('Unbalanced journal entry detected', [
                'entry_id' => $entry->id,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'difference' => $difference,
            ]);
        }

        return abs((float) $difference) < 0.01;
    }

    /**
     * V7-CRITICAL-N01 FIX: Internal method to properly post auto-generated journal entries.
     * This ensures balance validation and account balance updates are always performed.
     *
     * @param  JournalEntry  $entry  The journal entry to post (must be in draft status)
     * @return bool True if posting succeeded
     *
     * @throws Exception If entry is not balanced or posting fails
     */
    protected function postAutoGeneratedEntry(JournalEntry $entry): bool
    {
        // Validate the entry is balanced before posting
        if (! $this->validateBalance($entry)) {
            Log::error('Auto-generated journal entry is not balanced', [
                'entry_id' => $entry->id,
                'reference' => $entry->reference_number,
            ]);
            throw new Exception('Auto-generated journal entry is not balanced');
        }

        // Update entry to posted status
        // V33-CRIT-02 FIX: Use actual_user_id() for correct audit attribution during impersonation
        $entry->update([
            'status' => 'posted',
            'posted_at' => now(),
            'approved_by' => actual_user_id(),
            'approved_at' => now(),
        ]);

        // Load lines to get account IDs
        $entry->load('lines');
        $accountIds = $entry->lines->pluck('account_id')->unique()->toArray();

        if (empty($accountIds)) {
            return true; // No lines to process
        }

        // Lock all accounts involved to prevent race conditions
        $accounts = Account::whereIn('id', $accountIds)->lockForUpdate()->get()->keyBy('id');

        foreach ($entry->lines as $line) {
            $account = $accounts->get($line->account_id);
            if (! $account) {
                // V7-CRITICAL-N01 FIX: Throw exception for missing accounts - data integrity issue
                throw new Exception("Account ID {$line->account_id} not found during auto-post. Data integrity issue detected.");
            }

            $netChange = $line->debit - $line->credit;

            // For asset and expense accounts, debit increases balance
            // For liability, equity, and revenue accounts, credit increases balance
            if (in_array($account->type, ['asset', 'expense'], true)) {
                $account->increment('balance', $netChange);
            } else {
                $account->decrement('balance', $netChange);
            }
        }

        return true;
    }

    /**
     * Post journal entry
     */
    public function postJournalEntry(JournalEntry $entry, int $userId): bool
    {
        if ($entry->status === 'posted') {
            throw new Exception('Journal entry already posted');
        }

        if (! $this->validateBalance($entry)) {
            throw new Exception('Journal entry is not balanced');
        }

        return DB::transaction(function () use ($entry, $userId) {
            $entry->update([
                'status' => 'posted',
                'approved_by' => $userId,
                'approved_at' => now(),
            ]);

            // Load lines first to get account IDs
            $entry->load('lines');
            $accountIds = $entry->lines->pluck('account_id')->unique()->toArray();

            // Lock all accounts involved in this journal entry to prevent race conditions
            // This ensures consistent balance reporting even during concurrent transactions
            $accounts = Account::whereIn('id', $accountIds)->lockForUpdate()->get()->keyBy('id');

            foreach ($entry->lines as $line) {
                $account = $accounts->get($line->account_id);
                if (! $account) {
                    throw new Exception("Account ID {$line->account_id} not found for journal entry line. Data integrity issue detected.");
                }
                $netChange = $line->debit - $line->credit;

                // For asset and expense accounts, debit increases balance
                // For liability, equity, and revenue accounts, credit increases balance
                if (in_array($account->type, ['asset', 'expense'], true)) {
                    $account->increment('balance', $netChange);
                } else {
                    $account->decrement('balance', $netChange);
                }
            }

            return true;
        });
    }

    /**
     * Reverse journal entry
     */
    public function reverseJournalEntry(JournalEntry $entry, string $reason, int $userId): JournalEntry
    {
        if ($entry->status !== 'posted') {
            throw new Exception('Can only reverse posted journal entries');
        }

        if (! $entry->is_reversible) {
            throw new Exception('This journal entry cannot be reversed');
        }

        return DB::transaction(function () use ($entry, $reason, $userId) {
            $reversalEntry = JournalEntry::create([
                'branch_id' => $entry->branch_id,
                'reference_number' => $this->generateReferenceNumber('REV', $entry->id),
                'entry_date' => now()->toDateString(),
                'description' => "Reversal of {$entry->reference_number}: {$reason}",
                'status' => 'posted',
                'source_module' => $entry->source_module,
                'source_type' => $entry->source_type,
                'source_id' => $entry->source_id,
                'fiscal_year' => FiscalPeriod::getCurrentPeriod($entry->branch_id)?->year,
                'fiscal_period' => FiscalPeriod::getCurrentPeriod($entry->branch_id)?->period,
                'is_auto_generated' => true,
                'created_by' => $userId,
                'approved_by' => $userId,
                'approved_at' => now(),
            ]);

            // Load lines to get account IDs
            $entry->load('lines');
            $accountIds = $entry->lines->pluck('account_id')->unique()->toArray();

            // Lock all accounts involved to prevent race conditions
            $accounts = Account::whereIn('id', $accountIds)->lockForUpdate()->get()->keyBy('id');

            // Create reversed lines (swap debit and credit)
            foreach ($entry->lines as $line) {
                JournalEntryLine::create([
                    'journal_entry_id' => $reversalEntry->id,
                    'account_id' => $line->account_id,
                    'debit' => $line->credit,
                    'credit' => $line->debit,
                    'description' => "Reversal: {$line->description}",
                ]);

                // Update account balance using locked account
                $account = $accounts->get($line->account_id);
                if (! $account) {
                    throw new Exception("Account ID {$line->account_id} not found during journal entry reversal. Data integrity issue detected.");
                }
                $netChange = $line->credit - $line->debit; // Reversed

                if (in_array($account->type, ['asset', 'expense'], true)) {
                    $account->increment('balance', $netChange);
                } else {
                    $account->decrement('balance', $netChange);
                }
            }

            // Mark original entry as reversed
            $entry->update([
                'reversed_by_entry_id' => $reversalEntry->id,
            ]);

            return $reversalEntry->fresh('lines');
        });
    }

    /**
     * Create a manual journal entry
     *
     * @param  array  $data  Journal entry data with items array
     *
     * @throws Exception If entry is not balanced
     */
    public function createJournalEntry(array $data): JournalEntry
    {
        $items = $data['items'] ?? [];

        if (empty($items)) {
            throw new Exception('Journal entry must have at least one line item');
        }

        if (! $this->validateBalancedEntry($items)) {
            throw new Exception('Journal entry is not balanced - total debits must equal total credits');
        }

        return DB::transaction(function () use ($data, $items) {
            $fiscalPeriod = FiscalPeriod::getCurrentPeriod($data['branch_id'] ?? null);

            // Determine reference number with preference order
            $referenceNumber = $data['reference']
                ?? $data['reference_number']
                ?? $this->generateReferenceNumber('JE', time());

            $entry = JournalEntry::create([
                'branch_id' => $data['branch_id'] ?? null,
                'reference_number' => $referenceNumber,
                'entry_date' => $data['entry_date'] ?? now()->toDateString(),
                'description' => $data['description'] ?? '',
                'status' => $data['status'] ?? 'draft',
                'source_module' => $data['source_module'] ?? 'manual',
                'source_type' => $data['source_type'] ?? null,
                'source_id' => $data['source_id'] ?? null,
                'fiscal_year' => $fiscalPeriod?->year,
                'fiscal_period' => $fiscalPeriod?->period,
                'is_auto_generated' => false,
                // V33-CRIT-02 FIX: Use actual_user_id() for correct audit attribution during impersonation
                'created_by' => actual_user_id(),
            ]);

            foreach ($items as $item) {
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $item['account_id'],
                    'debit' => $item['debit'] ?? 0,
                    'credit' => $item['credit'] ?? 0,
                    'description' => $item['description'] ?? null,
                ]);
            }

            return $entry->fresh('lines');
        });
    }

    /**
     * Validate that journal entry lines are balanced
     *
     * @param  array  $items  Array of line items with debit and credit amounts
     * @return bool True if balanced (within 0.01 tolerance)
     */
    public function validateBalancedEntry(array $items): bool
    {
        // Critical ERP: Use bcmath for precise validation
        $totalDebit = '0';
        $totalCredit = '0';

        foreach ($items as $item) {
            $totalDebit = bcadd($totalDebit, (string) ($item['debit'] ?? 0), 2);
            $totalCredit = bcadd($totalCredit, (string) ($item['credit'] ?? 0), 2);
        }

        $difference = bcsub($totalDebit, $totalCredit, 2);

        return abs((float) $difference) < 0.01;
    }

    /**
     * Get account balance from journal entry lines
     *
     * @param  int  $accountId  Account ID
     * @return float Net balance (sum of debits minus credits)
     */
    public function getAccountBalance(int $accountId): float
    {
        $result = JournalEntryLine::where('account_id', $accountId)
            ->selectRaw('SUM(debit) - SUM(credit) as balance')
            ->value('balance');

        return (float) ($result ?? 0);
    }

    /**
     * Get account ID from mapping key
     *
     * @param  string  $key  Mapping key (e.g., 'fixed_assets.depreciation_expense')
     * @return int|null Account ID or null if not configured
     */
    public function getAccountMapping(string $key): ?int
    {
        return AccountMapping::where('mapping_key', $key)
            ->value('account_id');
    }

    /**
     * Create a journal entry with lines
     *
     * @param  array  $data  Entry data including lines array
     * @return JournalEntry Created journal entry
     *
     * @throws Exception If entry is not balanced
     */
    public function createEntry(array $data): JournalEntry
    {
        $lines = $data['lines'] ?? [];

        if (empty($lines)) {
            throw new Exception('Journal entry must have at least one line');
        }

        if (! $this->validateBalancedEntry($lines)) {
            throw new Exception('Journal entry debits and credits must balance');
        }

        return DB::transaction(function () use ($data, $lines) {
            // NEW-V15-HIGH-01 FIX: Do not default to branch_id 1. Require explicit branch_id
            // to prevent accidental posting to wrong branch for background jobs and system-generated entries.
            $branchId = $data['branch_id'] ?? auth()->user()?->branch_id;

            if ($branchId === null) {
                throw new Exception('branch_id is required for journal entries. Please provide an explicit branch_id.');
            }

            // V7-CRITICAL-N01 FIX: Create entry as 'draft' first, then post properly
            $entry = JournalEntry::create([
                'branch_id' => $branchId,
                'reference_number' => $data['reference_number'] ?? 'JE-'.now()->format('YmdHis'),
                'entry_date' => $data['entry_date'] ?? now(),
                'description' => $data['description'] ?? '',
                'status' => 'draft', // Start as draft
                // V33-CRIT-02 FIX: Use actual_user_id() for correct audit attribution during impersonation
                'created_by' => actual_user_id(),
            ]);

            foreach ($lines as $line) {
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $line['account_id'],
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'description' => $line['description'] ?? '',
                ]);
            }

            // V7-CRITICAL-N01 FIX: Properly post the entry with validation and balance updates
            $this->postAutoGeneratedEntry($entry);

            return $entry->fresh('lines');
        });
    }

    /**
     * Record Cost of Goods Sold (COGS) entry for a sale
     * BUG FIX #1: Generate COGS journal entry to properly reflect inventory cost
     * Debit: COGS Expense, Credit: Inventory Asset
     *
     * @param  Sale  $sale  The completed sale
     * @return JournalEntry|null The created COGS journal entry or null if accounts not configured
     *
     * @throws Exception If COGS entry generation fails
     */
    public function recordCogsEntry(Sale $sale): ?JournalEntry
    {
        // Load sale items with product cost information
        $sale->load('items.product');

        // Calculate total cost of goods sold
        $totalCost = '0';
        foreach ($sale->items as $item) {
            if ($item->product) {
                // Use cost_price from item if available, otherwise use product cost
                $itemCost = $item->cost_price ?? $item->product->cost ?? 0;
                $itemQty = $item->quantity ?? 0;

                // Use bcmath for precise cost calculation
                $lineCost = bcmul((string) $itemCost, (string) $itemQty, 4);
                $totalCost = bcadd($totalCost, $lineCost, 4);
            }
        }

        // V30-MED-08 FIX: Use bcround() instead of bcdiv truncation
        $totalCost = (float) bcround($totalCost, 2);

        // Skip if total cost is zero or negative
        if ($totalCost <= 0) {
            return null;
        }

        // Get COGS and Inventory accounts
        $cogsAccount = AccountMapping::getAccount('sales', 'cogs_account', $sale->branch_id);
        $inventoryAccount = AccountMapping::getAccount('sales', 'inventory_account', $sale->branch_id);

        // Skip if accounts are not configured
        if (! $cogsAccount || ! $inventoryAccount) {
            return null;
        }

        return DB::transaction(function () use ($sale, $totalCost, $cogsAccount, $inventoryAccount) {
            $fiscalPeriod = FiscalPeriod::getCurrentPeriod($sale->branch_id);

            // V7-CRITICAL-N01 FIX: Create entry as 'draft' first, then post properly
            $entry = JournalEntry::create([
                'branch_id' => $sale->branch_id,
                'reference_number' => $this->generateReferenceNumber('COGS', $sale->id),
                'entry_date' => $sale->posted_at ?? $sale->created_at,
                'description' => "COGS for Sale #{$sale->code}",
                'status' => 'draft', // Start as draft
                'source_module' => 'sales',
                'source_type' => 'Sale',
                'source_id' => $sale->id,
                'fiscal_year' => $fiscalPeriod?->year,
                'fiscal_period' => $fiscalPeriod?->period,
                'is_auto_generated' => true,
                // V33-CRIT-02 FIX: Use actual_user_id() for correct audit attribution during impersonation
                'created_by' => actual_user_id(),
            ]);

            // Debit: COGS Expense (increases expense)
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $cogsAccount->id,
                'debit' => $totalCost,
                'credit' => 0,
                'description' => 'Cost of goods sold',
            ]);

            // Credit: Inventory Asset (decreases asset)
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $inventoryAccount->id,
                'debit' => 0,
                'credit' => $totalCost,
                'description' => 'Inventory reduction',
            ]);

            // V7-CRITICAL-N01 FIX: Properly post the entry with validation and balance updates
            $this->postAutoGeneratedEntry($entry);

            return $entry->fresh('lines');
        });
    }
}
