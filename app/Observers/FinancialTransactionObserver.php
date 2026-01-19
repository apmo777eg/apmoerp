<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Customer;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use Illuminate\Support\Facades\Log;

/**
 * FinancialTransactionObserver - Track financial transaction changes
 *
 * NEW FEATURE: Enhanced audit trail for financial transactions
 *
 * FEATURES:
 * - Auto-update customer/supplier balances
 * - Auto-update payment status
 * - Track financial changes for audit
 * - Prevent unauthorized modifications
 */
class FinancialTransactionObserver
{
    /**
     * Handle Sale created event.
     */
    public function created(Sale|Purchase $model): void
    {
        $this->updateRelatedBalance($model, 'add');
        $this->updatePaymentStatus($model);

        // V7-MEDIUM-N09 FIX: Use reference_number or id instead of code
        // code attribute may not exist on all models and causes errors with preventAccessingMissingAttributes()
        // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
        Log::info('Financial transaction created', [
            'type' => get_class($model),
            'id' => $model->id,
            'reference' => $model->reference_number ?? $model->id,
            'amount' => $model->total_amount,
            'user_id' => actual_user_id(),
        ]);
    }

    /**
     * Handle Sale updated event.
     * STILL-HIGH-08 FIX: Use wasChanged() instead of isDirty() after model is saved
     */
    public function updated(Sale|Purchase $model): void
    {
        // STILL-HIGH-08 FIX: Use wasChanged() in updated event - isDirty is unreliable after save
        if ($model->wasChanged('total_amount')) {
            $oldTotal = $model->getOriginal('total_amount');
            $newTotal = $model->total_amount;
            $difference = $newTotal - $oldTotal;

            if ($difference != 0) {
                $this->adjustRelatedBalance($model, $difference);

                // V7-MEDIUM-N09 FIX: Use reference_number or id instead of code
                // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
                Log::warning('Financial transaction amount changed', [
                    'type' => get_class($model),
                    'id' => $model->id,
                    'reference' => $model->reference_number ?? $model->id,
                    'old_amount' => $oldTotal,
                    'new_amount' => $newTotal,
                    'difference' => $difference,
                    'user_id' => actual_user_id(),
                ]);
            }
        }

        // Only update payment status if relevant fields changed
        if ($model->wasChanged(['total_amount', 'paid_amount', 'status'])) {
            $this->updatePaymentStatus($model);
        }

        // Track status changes
        if ($model->wasChanged('status')) {
            // V7-MEDIUM-N09 FIX: Use reference_number or id instead of code
            // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
            Log::info('Financial transaction status changed', [
                'type' => get_class($model),
                'id' => $model->id,
                'reference' => $model->reference_number ?? $model->id,
                'old_status' => $model->getOriginal('status'),
                'new_status' => $model->status,
                'user_id' => actual_user_id(),
            ]);
        }
    }

    /**
     * Handle Sale deleted event.
     */
    public function deleted(Sale|Purchase $model): void
    {
        $this->updateRelatedBalance($model, 'subtract');

        // V7-MEDIUM-N09 FIX: Use reference_number or id instead of code
        // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
        Log::warning('Financial transaction deleted', [
            'type' => get_class($model),
            'id' => $model->id,
            'reference' => $model->reference_number ?? $model->id,
            'amount' => $model->total_amount,
            'user_id' => actual_user_id(),
        ]);
    }

    /**
     * Handle Sale/Purchase restored event.
     * STILL-HIGH-08 FIX: Add restored handler to recalculate payment status when soft-deleted records are restored
     */
    public function restored(Sale|Purchase $model): void
    {
        $this->updateRelatedBalance($model, 'add');
        $this->updatePaymentStatus($model);

        // V7-MEDIUM-N09 FIX: Use reference_number or id instead of code
        // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
        Log::info('Financial transaction restored', [
            'type' => get_class($model),
            'id' => $model->id,
            'reference' => $model->reference_number ?? $model->id,
            'amount' => $model->total_amount,
            'user_id' => actual_user_id(),
        ]);
    }

    /**
     * Update customer or supplier balance.
     */
    private function updateRelatedBalance(Sale|Purchase $model, string $operation): void
    {
        if ($model instanceof Sale && $model->customer_id) {
            $customer = Customer::find($model->customer_id);
            if ($customer) {
                if ($operation === 'add') {
                    $customer->addBalance(decimal_float($model->total_amount));
                } else {
                    $customer->subtractBalance(decimal_float($model->total_amount));
                }
            }
        } elseif ($model instanceof Purchase && $model->supplier_id) {
            $supplier = Supplier::find($model->supplier_id);
            if ($supplier) {
                if ($operation === 'add') {
                    $supplier->addBalance(decimal_float($model->total_amount));
                } else {
                    $supplier->subtractBalance(decimal_float($model->total_amount));
                }
            }
        }
    }

    /**
     * Adjust balance when amount changes.
     */
    private function adjustRelatedBalance(Sale|Purchase $model, float $difference): void
    {
        if ($model instanceof Sale && $model->customer_id) {
            $customer = Customer::find($model->customer_id);
            if ($customer) {
                if ($difference > 0) {
                    $customer->addBalance($difference);
                } else {
                    $customer->subtractBalance(abs($difference));
                }
            }
        } elseif ($model instanceof Purchase && $model->supplier_id) {
            $supplier = Supplier::find($model->supplier_id);
            if ($supplier) {
                if ($difference > 0) {
                    $supplier->addBalance($difference);
                } else {
                    $supplier->subtractBalance(abs($difference));
                }
            }
        }
    }

    /**
     * Auto-update payment status based on payments.
     */
    private function updatePaymentStatus(Sale|Purchase $model): void
    {
        if (method_exists($model, 'updatePaymentStatus')) {
            $model->updatePaymentStatus();
        }
    }
}
