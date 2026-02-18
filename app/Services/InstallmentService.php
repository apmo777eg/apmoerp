<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Customer;
use App\Models\InstallmentPayment;
use App\Models\InstallmentPlan;
use App\Models\Sale;
use App\Traits\HandlesServiceErrors;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InstallmentService
{
    use HandlesServiceErrors;

    public function createPlan(
        Sale $sale,
        Customer $customer,
        int $numInstallments,
        float $downPayment = 0,
        float $interestRate = 0,
        ?Carbon $startDate = null,
        ?int $userId = null
    ): InstallmentPlan {
        return $this->handleServiceOperation(
            callback: function () use ($sale, $customer, $numInstallments, $downPayment, $interestRate, $startDate, $userId) {
                // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
                $totalAmount = decimal_float($sale->total_amount);

                if ($numInstallments < 1) {
                    throw new InvalidArgumentException(__('Number of installments must be at least 1'));
                }

                if ($downPayment < 0) {
                    throw new InvalidArgumentException(__('Down payment cannot be negative'));
                }

                if ($downPayment > $totalAmount) {
                    throw new InvalidArgumentException(__('Down payment cannot exceed the sale total'));
                }

                if ($interestRate < 0) {
                    throw new InvalidArgumentException(__('Interest rate cannot be negative'));
                }

                if ($interestRate > 100) {
                    throw new InvalidArgumentException(__('Interest rate cannot exceed 100%'));
                }

                $startDate = $startDate ?? now();

                // Use bcmath for precise interest and installment calculations
                $interestMultiplier = bcadd('1', bcdiv((string) $interestRate, '100', 6), 6);
                $totalWithInterest = bcmul((string) $totalAmount, $interestMultiplier, 2);
                $remainingAmount = bcsub($totalWithInterest, (string) $downPayment, 2);

                // Use bcmath comparison for precision
                if (bccomp($remainingAmount, '0', 2) <= 0) {
                    throw new InvalidArgumentException(__('Remaining amount must be greater than zero'));
                }

                $installmentAmount = bcdiv($remainingAmount, (string) $numInstallments, 2);
                $endDate = $startDate->copy()->addMonths($numInstallments);

                return DB::transaction(function () use (
                    $sale, $customer, $numInstallments, $downPayment,
                    $totalWithInterest, $remainingAmount, $installmentAmount,
                    $interestRate, $startDate, $endDate, $userId
                ) {
                    $plan = InstallmentPlan::create([
                        'sale_id' => $sale->id,
                        'customer_id' => $customer->id,
                        'branch_id' => $sale->branch_id,
                        'total_amount' => $totalWithInterest,
                        'down_payment' => $downPayment,
                        'remaining_amount' => $remainingAmount,
                        'num_installments' => $numInstallments,
                        'installment_amount' => $installmentAmount,
                        'interest_rate' => $interestRate,
                        'status' => 'active',
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'created_by' => $userId,
                    ]);

                    for ($i = 1; $i <= $numInstallments; $i++) {
                        $dueDate = $startDate->copy()->addMonths($i);

                        // Use bcmath for final installment to account for rounding differences
                        if ($i === $numInstallments) {
                            $previousTotal = bcmul($installmentAmount, (string) ($numInstallments - 1), 2);
                            $amount = bcsub($remainingAmount, $previousTotal, 2);
                        } else {
                            $amount = $installmentAmount;
                        }

                        InstallmentPayment::create([
                            'installment_plan_id' => $plan->id,
                            'installment_number' => $i,
                            // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
                            'amount_due' => max(0, decimal_float($amount)),
                            'due_date' => $dueDate,
                            'status' => 'pending',
                        ]);
                    }

                    return $plan;
                });
            },
            operation: 'createPlan',
            context: ['sale_id' => $sale->id, 'customer_id' => $customer->id, 'num_installments' => $numInstallments]
        );
    }

    public function recordPayment(
        InstallmentPayment $payment,
        float $amount,
        string $paymentMethod,
        ?string $reference = null,
        ?int $userId = null
    ): InstallmentPayment {
        return $this->handleServiceOperation(
            callback: function () use ($payment, $amount, $paymentMethod, $reference, $userId) {
                if ($amount <= 0) {
                    throw new InvalidArgumentException(__('Payment amount must be greater than zero'));
                }

                // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
                $remainingAmount = decimal_float($payment->remaining_amount);

                if ($remainingAmount <= 0) {
                    throw new InvalidArgumentException(__('This payment has already been fully paid'));
                }

                $actualPayment = min($amount, $remainingAmount);

                return DB::transaction(function () use ($payment, $actualPayment, $paymentMethod, $reference, $userId) {
                    // Use bcmath for precise payment calculations
                    $amountPaid = (string) ($payment->amount_paid ?? 0);
                    $newAmountPaid = bcadd($amountPaid, (string) $actualPayment, 2);
                    $amountDue = (string) $payment->amount_due;
                    $newStatus = bccomp($newAmountPaid, $amountDue, 2) >= 0 ? 'paid' : 'partial';

                    $payment->update([
                        // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
                        'amount_paid' => decimal_float($newAmountPaid),
                        'paid_at' => now(),
                        'payment_method' => $paymentMethod,
                        'payment_reference' => $reference,
                        'paid_by' => $userId,
                        'status' => $newStatus,
                    ]);

                    $plan = $payment->plan;
                    $plan->refresh();

                    $totalPaid = (string) $plan->payments()->sum('amount_paid');
                    // Use bcmath to calculate remaining amount precisely
                    $totalAfterDown = bcsub((string) $plan->total_amount, (string) $plan->down_payment, 2);
                    $planRemainingAmount = bcsub($totalAfterDown, $totalPaid, 2);
                    // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
                    $planRemainingAmount = max(0, decimal_float($planRemainingAmount));

                    $plan->update([
                        'remaining_amount' => $planRemainingAmount,
                    ]);

                    $allPaid = $plan->payments()->where('status', '!=', 'paid')->count() === 0;

                    if ($allPaid) {
                        $plan->update(['status' => 'completed']);
                    }

                    return $payment->fresh();
                });
            },
            operation: 'recordPayment',
            context: ['payment_id' => $payment->id, 'amount' => $amount]
        );
    }

    public function updateOverduePayments(): int
    {
        return $this->handleServiceOperation(
            callback: fn () => InstallmentPayment::where('status', 'pending')
                ->where('due_date', '<', now())
                ->update(['status' => 'overdue']),
            operation: 'updateOverduePayments',
            context: []
        );
    }

    public function getOverduePayments(?int $branchId = null): \Illuminate\Database\Eloquent\Collection
    {
        return $this->handleServiceOperation(
            callback: fn () => InstallmentPayment::with(['plan.customer', 'plan.sale'])
                ->where('status', 'overdue')
                ->when($branchId, function ($q) use ($branchId) {
                    $q->whereHas('plan', fn ($p) => $p->where('branch_id', $branchId));
                })
                ->orderBy('due_date')
                ->get(),
            operation: 'getOverduePayments',
            context: ['branch_id' => $branchId]
        );
    }

    public function getUpcomingPayments(?int $branchId = null, int $days = 7): \Illuminate\Database\Eloquent\Collection
    {
        return $this->handleServiceOperation(
            callback: fn () => InstallmentPayment::with(['plan.customer', 'plan.sale'])
                ->whereIn('status', ['pending', 'partial'])
                ->whereBetween('due_date', [now(), now()->addDays($days)])
                ->when($branchId, function ($q) use ($branchId) {
                    $q->whereHas('plan', fn ($p) => $p->where('branch_id', $branchId));
                })
                ->orderBy('due_date')
                ->get(),
            operation: 'getUpcomingPayments',
            context: ['branch_id' => $branchId, 'days' => $days]
        );
    }

    public function getCustomerPlans(Customer $customer): \Illuminate\Database\Eloquent\Collection
    {
        return $this->handleServiceOperation(
            callback: fn () => InstallmentPlan::with(['sale', 'payments'])
                ->where('customer_id', $customer->id)
                ->orderByDesc('created_at')
                ->get(),
            operation: 'getCustomerPlans',
            context: ['customer_id' => $customer->id]
        );
    }

    public function getCustomerStatement(Customer $customer): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($customer) {
                $plans = $this->getCustomerPlans($customer);

                $totalOwed = $plans->where('status', 'active')->sum('remaining_amount');
                $totalPaid = $plans->sum(fn ($plan) => $plan->payments->sum('amount_paid'));
                $overdueAmount = $plans->where('status', 'active')
                    ->flatMap->payments
                    ->where('status', 'overdue')
                    ->sum(fn ($payment) => $payment->amount_due - ($payment->amount_paid ?? 0));

                return [
                    'total_plans' => $plans->count(),
                    'active_plans' => $plans->where('status', 'active')->count(),
                    'total_owed' => max(0, $totalOwed),
                    'total_paid' => $totalPaid,
                    'overdue_amount' => max(0, $overdueAmount),
                    'plans' => $plans,
                ];
            },
            operation: 'getCustomerStatement',
            context: ['customer_id' => $customer->id]
        );
    }

    public function cancelPlan(InstallmentPlan $plan, ?string $reason = null): void
    {
        $this->handleServiceOperation(
            callback: function () use ($plan, $reason) {
                if ($plan->status === 'completed') {
                    throw new InvalidArgumentException(__('Cannot cancel a completed plan'));
                }

                $plan->update([
                    'status' => 'cancelled',
                    'notes' => $reason ? ($plan->notes."\n".__('Cancelled').': '.$reason) : $plan->notes,
                ]);
            },
            operation: 'cancelPlan',
            context: ['plan_id' => $plan->id]
        );
    }

    public function markAsDefaulted(InstallmentPlan $plan): void
    {
        $this->handleServiceOperation(
            callback: function () use ($plan) {
                if ($plan->status !== 'active') {
                    throw new InvalidArgumentException(__('Only active plans can be marked as defaulted'));
                }

                $plan->update(['status' => 'defaulted']);
            },
            operation: 'markAsDefaulted',
            context: ['plan_id' => $plan->id]
        );
    }

    public function getPlanStats(?int $branchId = null): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($branchId) {
                $query = InstallmentPlan::query()
                    ->when($branchId, fn ($q) => $q->where('branch_id', $branchId));

                return [
                    'total_active' => (clone $query)->where('status', 'active')->count(),
                    'total_completed' => (clone $query)->where('status', 'completed')->count(),
                    'total_defaulted' => (clone $query)->where('status', 'defaulted')->count(),
                    'total_outstanding' => max(0, (clone $query)->where('status', 'active')->sum('remaining_amount')),
                    'overdue_payments_count' => InstallmentPayment::where('status', 'overdue')
                        ->when($branchId, function ($q) use ($branchId) {
                            $q->whereHas('plan', fn ($p) => $p->where('branch_id', $branchId));
                        })
                        ->count(),
                ];
            },
            operation: 'getPlanStats',
            context: ['branch_id' => $branchId]
        );
    }
}
