<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Purchase;
use App\Models\Sale;

class FinancialReportService
{
    /**
     * Generate Trial Balance report
     */
    public function getTrialBalance(?int $branchId = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = Account::active();

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $accounts = $query->orderBy('account_number')->get();

        $data = [];
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($accounts as $account) {
            $balance = $this->getAccountBalance($account, $startDate, $endDate);

            if (abs($balance) < 0.01) {
                continue; // Skip zero balance accounts
            }

            $debitAmount = 0;
            $creditAmount = 0;

            // Determine if balance is debit or credit based on account type
            if (in_array($account->type, ['asset', 'expense'])) {
                $debitAmount = $balance > 0 ? $balance : 0;
                $creditAmount = $balance < 0 ? abs($balance) : 0;
            } else { // liability, equity, revenue
                $creditAmount = $balance > 0 ? $balance : 0;
                $debitAmount = $balance < 0 ? abs($balance) : 0;
            }

            $data[] = [
                'account_number' => $account->account_number,
                'account_name' => $account->localized_name,
                'type' => $account->type,
                'debit' => $debitAmount,
                'credit' => $creditAmount,
            ];

            $totalDebit += $debitAmount;
            $totalCredit += $creditAmount;
        }

        // Use bcmath for precise financial calculations
        $totalDebitStr = (string) $totalDebit;
        $totalCreditStr = (string) $totalCredit;
        $difference = bcsub($totalDebitStr, $totalCreditStr, 2);

        return [
            'accounts' => $data,
            'total_debit' => (float) bcdiv($totalDebitStr, '1', 2),
            'total_credit' => (float) bcdiv($totalCreditStr, '1', 2),
            'difference' => (float) $difference,
            'is_balanced' => bccomp(str_replace('-', '', $difference), '0.01', 2) < 0,
        ];
    }

    /**
     * Generate Profit & Loss statement
     */
    public function getProfitLoss(?int $branchId = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = JournalEntry::posted();

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($startDate) {
            $query->where('entry_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('entry_date', '<=', $endDate);
        }

        // Get revenue accounts
        $revenueAccounts = Account::active()->type('revenue');
        if ($branchId) {
            $revenueAccounts->where('branch_id', $branchId);
        }
        $revenueAccounts = $revenueAccounts->get();

        // Get expense accounts
        $expenseAccounts = Account::active()->type('expense');
        if ($branchId) {
            $expenseAccounts->where('branch_id', $branchId);
        }
        $expenseAccounts = $expenseAccounts->get();

        $revenue = [];
        $totalRevenue = 0;

        foreach ($revenueAccounts as $account) {
            $balance = $this->getAccountBalance($account, $startDate, $endDate);
            if (abs($balance) > 0.01) {
                $revenue[] = [
                    'account_number' => $account->account_number,
                    'account_name' => $account->localized_name,
                    'amount' => $balance,
                ];
                $totalRevenue += $balance;
            }
        }

        $expenses = [];
        $totalExpenses = 0;

        foreach ($expenseAccounts as $account) {
            $balance = $this->getAccountBalance($account, $startDate, $endDate);
            if (abs($balance) > 0.01) {
                $expenses[] = [
                    'account_number' => $account->account_number,
                    'account_name' => $account->localized_name,
                    'amount' => $balance,
                ];
                $totalExpenses += $balance;
            }
        }

        // Use bcmath for profit calculation
        $netIncome = bcsub((string) $totalRevenue, (string) $totalExpenses, 2);

        return [
            'revenue' => [
                'accounts' => $revenue,
                'total' => (float) bcdiv((string) $totalRevenue, '1', 2),
            ],
            'expenses' => [
                'accounts' => $expenses,
                'total' => (float) bcdiv((string) $totalExpenses, '1', 2),
            ],
            'net_income' => (float) $netIncome,
        ];
    }

    /**
     * Generate Balance Sheet
     */
    public function getBalanceSheet(?int $branchId = null, ?string $asOfDate = null): array
    {
        $asOfDate = $asOfDate ?? now()->toDateString();

        // Get assets
        $assetAccounts = Account::active()->type('asset');
        if ($branchId) {
            $assetAccounts->where('branch_id', $branchId);
        }
        $assetAccounts = $assetAccounts->orderBy('account_number')->get();

        // Get liabilities
        $liabilityAccounts = Account::active()->type('liability');
        if ($branchId) {
            $liabilityAccounts->where('branch_id', $branchId);
        }
        $liabilityAccounts = $liabilityAccounts->orderBy('account_number')->get();

        // Get equity
        $equityAccounts = Account::active()->type('equity');
        if ($branchId) {
            $equityAccounts->where('branch_id', $branchId);
        }
        $equityAccounts = $equityAccounts->orderBy('account_number')->get();

        $assets = [];
        $totalAssets = 0;

        foreach ($assetAccounts as $account) {
            $balance = $this->getAccountBalance($account, null, $asOfDate);
            if (abs($balance) > 0.01) {
                $assets[] = [
                    'account_number' => $account->account_number,
                    'account_name' => $account->localized_name,
                    'category' => $account->account_category ?? 'general',
                    'amount' => $balance,
                ];
                $totalAssets += $balance;
            }
        }

        $liabilities = [];
        $totalLiabilities = 0;

        foreach ($liabilityAccounts as $account) {
            $balance = $this->getAccountBalance($account, null, $asOfDate);
            if (abs($balance) > 0.01) {
                $liabilities[] = [
                    'account_number' => $account->account_number,
                    'account_name' => $account->localized_name,
                    'category' => $account->account_category ?? 'general',
                    'amount' => $balance,
                ];
                $totalLiabilities += $balance;
            }
        }

        $equity = [];
        $totalEquity = 0;

        foreach ($equityAccounts as $account) {
            $balance = $this->getAccountBalance($account, null, $asOfDate);
            if (abs($balance) > 0.01) {
                $equity[] = [
                    'account_number' => $account->account_number,
                    'account_name' => $account->localized_name,
                    'amount' => $balance,
                ];
                $totalEquity += $balance;
            }
        }

        // Calculate retained earnings (net income)
        $netIncome = $this->getProfitLoss($branchId, null, $asOfDate)['net_income'];
        $totalEquity += $netIncome;

        $equity[] = [
            'account_number' => 'RET-EARN',
            'account_name' => 'Retained Earnings (Current Period)',
            'amount' => $netIncome,
        ];

        // Use bcmath for balance sheet totals
        $totalLiabilitiesAndEquity = bcadd((string) $totalLiabilities, (string) $totalEquity, 2);
        $balanceDiff = bcsub((string) $totalAssets, $totalLiabilitiesAndEquity, 2);

        return [
            'as_of_date' => $asOfDate,
            'assets' => [
                'accounts' => $assets,
                'total' => (float) bcdiv((string) $totalAssets, '1', 2),
            ],
            'liabilities' => [
                'accounts' => $liabilities,
                'total' => (float) bcdiv((string) $totalLiabilities, '1', 2),
            ],
            'equity' => [
                'accounts' => $equity,
                'total' => (float) bcdiv((string) $totalEquity, '1', 2),
            ],
            'total_liabilities_and_equity' => (float) $totalLiabilitiesAndEquity,
            'is_balanced' => bccomp(str_replace('-', '', $balanceDiff), '0.01', 2) < 0,
        ];
    }

    /**
     * Generate Accounts Receivable Aging Report
     *
     * STILL-V8-HIGH-N06 FIX: Calculate outstanding from payment tables instead of paid_amount field
     */
    public function getAccountsReceivableAging(?int $branchId = null, ?string $asOfDate = null): array
    {
        $asOfDate = $asOfDate ?? now()->toDateString();

        // Get sales (we'll calculate outstanding from payments)
        $query = Sale::query();

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $sales = $query->with(['customer', 'payments'])->get();

        // Pre-fetch all completed refunds for these sales to avoid N+1 queries
        $saleIds = $sales->pluck('id')->toArray();
        $refundsBySale = \App\Models\ReturnRefund::whereHas('returnNote', function ($q) use ($saleIds) {
            $q->whereIn('sale_id', $saleIds);
        })
            ->where('status', \App\Models\ReturnRefund::STATUS_COMPLETED)
            ->get()
            ->groupBy(fn ($refund) => $refund->returnNote?->sale_id)
            ->map(fn ($group) => $group->sum('amount'));

        $aging = [];

        foreach ($sales as $sale) {
            // STILL-V8-HIGH-N06 FIX: Calculate paid amount from SalePayment ledger
            // Only count completed/posted payments (already eager loaded)
            $totalPaid = $sale->payments
                ->whereIn('status', ['completed', 'posted', 'paid'])
                ->sum('amount');

            // STILL-V8-HIGH-N06 FIX: Get refund total from pre-fetched data
            $totalRefunded = $refundsBySale->get($sale->getKey(), 0);

            // Calculate true outstanding: total - payments + refunds (refunds reduce what was paid)
            $outstandingAmount = (float) $sale->total_amount - (float) $totalPaid + (float) $totalRefunded;

            if ($outstandingAmount <= 0) {
                continue;
            }

            // Calculate due date: use explicit payment_due_date if set, otherwise calculate from sale date + payment terms
            $saleDate = $sale->posted_at ?? $sale->created_at;
            $paymentTermsDays = (int) setting('sales.payment_terms_days', 30);
            $referenceDate = $sale->payment_due_date ?? $saleDate->copy()->addDays($paymentTermsDays);
            $asOf = \Carbon\Carbon::parse($asOfDate);
            $daysOverdue = $asOf->diffInDays($referenceDate, false);

            $bucket = $this->getAgingBucket($daysOverdue);

            $customerKey = $sale->customer_id ?? 'unknown';

            if (! isset($aging[$customerKey])) {
                $aging[$customerKey] = [
                    'customer_id' => $sale->customer_id,
                    'customer_name' => $sale->customer?->name ?? 'Walk-in Customer',
                    'current' => 0,
                    '1_30_days' => 0,
                    '31_60_days' => 0,
                    '61_90_days' => 0,
                    'over_90_days' => 0,
                    'total' => 0,
                ];
            }

            $aging[$customerKey][$bucket] += $outstandingAmount;
            $aging[$customerKey]['total'] += $outstandingAmount;
        }

        return [
            'as_of_date' => $asOfDate,
            'customers' => array_values($aging),
            'totals' => $this->calculateAgingTotals(array_values($aging)),
        ];
    }

    /**
     * Generate Accounts Payable Aging Report
     *
     * STILL-V8-HIGH-N06 FIX: Calculate outstanding from payment tables instead of paid_amount field
     */
    public function getAccountsPayableAging(?int $branchId = null, ?string $asOfDate = null): array
    {
        $asOfDate = $asOfDate ?? now()->toDateString();

        // Get purchases (we'll calculate outstanding from payments)
        $query = Purchase::query();

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $purchases = $query->with(['supplier', 'payments'])->get();

        $aging = [];

        foreach ($purchases as $purchase) {
            // STILL-V8-HIGH-N06 FIX: Calculate paid amount from PurchasePayment ledger
            // Only count completed/posted payments (already eager loaded)
            $totalPaid = $purchase->payments
                ->whereIn('status', ['completed', 'posted', 'paid'])
                ->sum('amount');

            // Calculate true outstanding: total - payments
            $outstandingAmount = (float) $purchase->total_amount - (float) $totalPaid;

            if ($outstandingAmount <= 0) {
                continue;
            }

            // Calculate due date: use explicit payment_due_date if set, otherwise calculate from purchase date + payment terms
            $purchaseDate = $purchase->posted_at ?? $purchase->created_at;
            $paymentTermsDays = (int) setting('purchases.payment_terms_days', 30);
            $referenceDate = $purchase->payment_due_date ?? $purchaseDate->copy()->addDays($paymentTermsDays);
            $asOf = \Carbon\Carbon::parse($asOfDate);
            $daysOverdue = $asOf->diffInDays($referenceDate, false);

            $bucket = $this->getAgingBucket($daysOverdue);

            $supplierKey = $purchase->supplier_id;

            if (! isset($aging[$supplierKey])) {
                $aging[$supplierKey] = [
                    'supplier_id' => $purchase->supplier_id,
                    'supplier_name' => $purchase->supplier?->name ?? 'Unknown Supplier',
                    'current' => 0,
                    '1_30_days' => 0,
                    '31_60_days' => 0,
                    '61_90_days' => 0,
                    'over_90_days' => 0,
                    'total' => 0,
                ];
            }

            $aging[$supplierKey][$bucket] += $outstandingAmount;
            $aging[$supplierKey]['total'] += $outstandingAmount;
        }

        return [
            'as_of_date' => $asOfDate,
            'suppliers' => array_values($aging),
            'totals' => $this->calculateAgingTotals(array_values($aging)),
        ];
    }

    /**
     * Generate Account Statement
     */
    public function getAccountStatement(int $accountId, ?string $startDate = null, ?string $endDate = null): array
    {
        $account = Account::findOrFail($accountId);

        $query = JournalEntryLine::where('account_id', $accountId)
            ->whereHas('journalEntry', function ($q) {
                $q->where('status', 'posted');
            });

        if ($startDate) {
            $query->whereHas('journalEntry', function ($q) use ($startDate) {
                $q->where('entry_date', '>=', $startDate);
            });
        }

        if ($endDate) {
            $query->whereHas('journalEntry', function ($q) use ($endDate) {
                $q->where('entry_date', '<=', $endDate);
            });
        }

        $lines = $query->with('journalEntry')->orderBy('created_at')->get();

        $transactions = [];
        $runningBalance = 0;
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($lines as $line) {
            $debit = (float) $line->debit;
            $credit = (float) $line->credit;

            // Calculate running balance based on account type
            if (in_array($account->type, ['asset', 'expense'])) {
                $runningBalance += ($debit - $credit);
            } else {
                $runningBalance += ($credit - $debit);
            }

            $transactions[] = [
                'date' => $line->journalEntry->entry_date,
                'reference' => $line->journalEntry->reference_number,
                'description' => $line->description ?? $line->journalEntry->description,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $runningBalance,
            ];

            $totalDebit += $debit;
            $totalCredit += $credit;
        }

        return [
            'account' => [
                'number' => $account->account_number,
                'name' => $account->localized_name,
                'type' => $account->type,
            ],
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'transactions' => $transactions,
            'summary' => [
                'total_debit' => (float) bcdiv((string) $totalDebit, '1', 2),
                'total_credit' => (float) bcdiv((string) $totalCredit, '1', 2),
                'ending_balance' => (float) bcdiv((string) $runningBalance, '1', 2),
            ],
        ];
    }

    /**
     * Get account balance for a period
     */
    protected function getAccountBalance(Account|int $account, ?string $startDate = null, ?string $endDate = null): float
    {
        // If int passed, fetch the account
        if (is_int($account)) {
            $account = Account::findOrFail($account);
        }

        $query = JournalEntryLine::where('account_id', $account->id)
            ->whereHas('journalEntry', function ($q) use ($startDate, $endDate) {
                $q->where('status', 'posted');

                if ($startDate) {
                    $q->where('entry_date', '>=', $startDate);
                }

                if ($endDate) {
                    $q->where('entry_date', '<=', $endDate);
                }
            });

        $totalDebit = (float) $query->sum('debit');
        $totalCredit = (float) $query->sum('credit');

        // Asset and Expense accounts have natural debit balance
        if (in_array($account->type, ['asset', 'expense'])) {
            return $totalDebit - $totalCredit;
        }

        // Liability, Equity, Revenue accounts have natural credit balance
        return $totalCredit - $totalDebit;
    }

    /**
     * Get aging bucket for days overdue
     */
    protected function getAgingBucket(int $daysOverdue): string
    {
        if ($daysOverdue <= 0) {
            return 'current';
        } elseif ($daysOverdue <= 30) {
            return '1_30_days';
        } elseif ($daysOverdue <= 60) {
            return '31_60_days';
        } elseif ($daysOverdue <= 90) {
            return '61_90_days';
        } else {
            return 'over_90_days';
        }
    }

    /**
     * Calculate aging totals
     */
    protected function calculateAgingTotals(array $aging): array
    {
        $totals = [
            'current' => 0,
            '1_30_days' => 0,
            '31_60_days' => 0,
            '61_90_days' => 0,
            'over_90_days' => 0,
            'total' => 0,
        ];

        foreach ($aging as $item) {
            $totals['current'] += $item['current'];
            $totals['1_30_days'] += $item['1_30_days'];
            $totals['31_60_days'] += $item['31_60_days'];
            $totals['61_90_days'] += $item['61_90_days'];
            $totals['over_90_days'] += $item['over_90_days'];
            $totals['total'] += $item['total'];
        }

        return $totals;
    }
}
