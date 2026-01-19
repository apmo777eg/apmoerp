<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Customer;
use App\Models\LoyaltySetting;
use App\Models\LoyaltyTransaction;
use App\Models\Sale;
use App\Traits\HandlesServiceErrors;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LoyaltyService
{
    use HandlesServiceErrors;

    public function earnPoints(Customer $customer, Sale $sale, ?int $userId = null): ?LoyaltyTransaction
    {
        return $this->handleServiceOperation(
            callback: function () use ($customer, $sale, $userId) {
                $settings = LoyaltySetting::getForBranch($customer->branch_id);

                if (! $settings || ! $settings->is_active) {
                    return null;
                }

                $amountPerPoint = (string) $settings->amount_per_point;
                if (bccomp($amountPerPoint, '0', 2) <= 0) {
                    return null;
                }

                // Calculate points: (grand_total / amount_per_point) * points_per_amount
                $ratio = bcdiv((string) $sale->grand_total, $amountPerPoint, 4);
                $pointsDecimal = bcmul($ratio, (string) $settings->points_per_amount, 2);
                $points = (int) floor(decimal_float($pointsDecimal));

                if ($points <= 0) {
                    return null;
                }

                return DB::transaction(function () use ($customer, $sale, $points, $userId) {
                    $customer->increment('loyalty_points', $points);
                    $customer->refresh();

                    $this->updateCustomerTier($customer);

                    return LoyaltyTransaction::create([
                        'customer_id' => $customer->id,
                        'branch_id' => $sale->branch_id,
                        'sale_id' => $sale->id,
                        'type' => 'earn',
                        'points' => $points,
                        'balance_after' => $customer->loyalty_points,
                        'description' => __('Points earned from sale #:invoice', ['invoice' => $sale->code]),
                        'created_by' => $userId,
                    ]);
                });
            },
            operation: 'earnPoints',
            context: ['customer_id' => $customer->id, 'sale_id' => $sale->id],
            defaultValue: null
        );
    }

    public function redeemPoints(Customer $customer, int $points, ?int $saleId = null, ?int $userId = null): ?LoyaltyTransaction
    {
        return $this->handleServiceOperation(
            callback: function () use ($customer, $points, $saleId, $userId) {
                $settings = LoyaltySetting::getForBranch($customer->branch_id);

                if (! $settings || ! $settings->is_active) {
                    return null;
                }

                if ($points <= 0) {
                    throw new InvalidArgumentException(__('Points must be greater than zero'));
                }

                if ($points < $settings->min_points_redeem) {
                    throw new InvalidArgumentException(__('Minimum points to redeem is :min', ['min' => $settings->min_points_redeem]));
                }

                $currentPoints = (int) $customer->loyalty_points;
                if ($currentPoints < $points) {
                    throw new InvalidArgumentException(__('Insufficient points. You have :current points but trying to redeem :requested', [
                        'current' => $currentPoints,
                        'requested' => $points,
                    ]));
                }

                return DB::transaction(function () use ($customer, $points, $saleId, $userId, $settings) {
                    $customer->decrement('loyalty_points', $points);
                    $customer->refresh();

                    $this->updateCustomerTier($customer);

                    $monetaryValue = bcmul((string) $points, (string) $settings->redemption_rate, 2);

                    return LoyaltyTransaction::create([
                        'customer_id' => $customer->id,
                        'branch_id' => $customer->branch_id,
                        'sale_id' => $saleId,
                        'type' => 'redeem',
                        'points' => -$points,
                        'balance_after' => $customer->loyalty_points,
                        'description' => __('Redeemed :points points for :amount', [
                            'points' => $points,
                            'amount' => number_format($monetaryValue, 2),
                        ]),
                        'created_by' => $userId,
                    ]);
                });
            },
            operation: 'redeemPoints',
            context: ['customer_id' => $customer->id, 'points' => $points]
        );
    }

    public function addBonusPoints(Customer $customer, int $points, string $reason, ?int $userId = null): LoyaltyTransaction
    {
        return $this->handleServiceOperation(
            callback: function () use ($customer, $points, $reason, $userId) {
                if ($points <= 0) {
                    throw new InvalidArgumentException(__('Bonus points must be greater than zero'));
                }

                if (empty(trim($reason))) {
                    throw new InvalidArgumentException(__('Reason is required for bonus points'));
                }

                return DB::transaction(function () use ($customer, $points, $reason, $userId) {
                    $customer->increment('loyalty_points', $points);
                    $customer->refresh();

                    $this->updateCustomerTier($customer);

                    return LoyaltyTransaction::create([
                        'customer_id' => $customer->id,
                        'branch_id' => $customer->branch_id,
                        'type' => 'bonus',
                        'points' => $points,
                        'balance_after' => $customer->loyalty_points,
                        'description' => $reason,
                        'created_by' => $userId,
                    ]);
                });
            },
            operation: 'addBonusPoints',
            context: ['customer_id' => $customer->id, 'points' => $points]
        );
    }

    public function adjustPoints(Customer $customer, int $points, string $reason, ?int $userId = null): LoyaltyTransaction
    {
        return $this->handleServiceOperation(
            callback: function () use ($customer, $points, $reason, $userId) {
                if ($points === 0) {
                    throw new InvalidArgumentException(__('Adjustment points cannot be zero'));
                }

                if (empty(trim($reason))) {
                    throw new InvalidArgumentException(__('Reason is required for point adjustments'));
                }

                $currentPoints = (int) $customer->loyalty_points;
                if ($points < 0 && abs($points) > $currentPoints) {
                    throw new InvalidArgumentException(__('Cannot deduct more points than the customer has. Current balance: :current', [
                        'current' => $currentPoints,
                    ]));
                }

                return DB::transaction(function () use ($customer, $points, $reason, $userId) {
                    if ($points > 0) {
                        $customer->increment('loyalty_points', $points);
                    } else {
                        $customer->decrement('loyalty_points', abs($points));
                    }
                    $customer->refresh();

                    $this->updateCustomerTier($customer);

                    return LoyaltyTransaction::create([
                        'customer_id' => $customer->id,
                        'branch_id' => $customer->branch_id,
                        'type' => 'adjust',
                        'points' => $points,
                        'balance_after' => max(0, $customer->loyalty_points),
                        'description' => $reason,
                        'created_by' => $userId,
                    ]);
                });
            },
            operation: 'adjustPoints',
            context: ['customer_id' => $customer->id, 'points' => $points]
        );
    }

    public function calculateRedemptionValue(Customer $customer, int $points): float
    {
        $settings = LoyaltySetting::getForBranch($customer->branch_id);

        if (! $settings) {
            return 0;
        }

        return decimal_float(bcmul((string) $points, (string) $settings->redemption_rate, 2));
    }

    protected function updateCustomerTier(Customer $customer): void
    {
        $totalPoints = (int) $customer->loyalty_points;

        // Get tier thresholds from config (configurable in admin settings)
        $premiumThreshold = (int) config('loyalty.tier_thresholds.premium', 10000);
        $vipThreshold = (int) config('loyalty.tier_thresholds.vip', 5000);
        $regularThreshold = (int) config('loyalty.tier_thresholds.regular', 1000);

        $tier = match (true) {
            $totalPoints >= $premiumThreshold => 'premium',
            $totalPoints >= $vipThreshold => 'vip',
            $totalPoints >= $regularThreshold => 'regular',
            default => 'new',
        };

        $currentTier = $customer->customer_tier ?? 'new';
        if ($currentTier !== $tier) {
            $customer->update([
                'customer_tier' => $tier,
                'tier_updated_at' => now(),
            ]);
        }
    }

    public function getCustomerHistory(Customer $customer, int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return $this->handleServiceOperation(
            callback: fn () => LoyaltyTransaction::where('customer_id', $customer->id)
                ->with('sale')
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get(),
            operation: 'getCustomerHistory',
            context: ['customer_id' => $customer->id, 'limit' => $limit]
        );
    }

    /**
     * Reverse loyalty points when a sale is returned.
     *
     * BUG FIX: Addresses the "points mining" loophole where customers could
     * earn points from a purchase, then return the item while keeping the points.
     * This method must be called when processing sales returns to deduct the
     * points that were originally earned from the returned sale.
     *
     * @param Customer $customer The customer who made the return
     * @param Sale $sale The original sale being returned
     * @param float|null $returnAmount The return amount (if partial return)
     * @param int|null $userId The user processing the return
     * @return LoyaltyTransaction|null The reversal transaction, or null if no points to reverse
     */
    public function reversePointsForReturn(
        Customer $customer,
        Sale $sale,
        ?float $returnAmount = null,
        ?int $userId = null
    ): ?LoyaltyTransaction {
        return $this->handleServiceOperation(
            callback: function () use ($customer, $sale, $returnAmount, $userId) {
                // Find the original earn transaction for this sale
                $originalTransaction = LoyaltyTransaction::where('customer_id', $customer->id)
                    ->where('sale_id', $sale->id)
                    ->where('type', 'earn')
                    ->where('points', '>', 0)
                    ->first();

                if (! $originalTransaction) {
                    // No points were earned from this sale
                    return null;
                }

                // Calculate points to reverse
                if ($returnAmount !== null && $sale->grand_total > 0) {
                    // Partial return - reverse proportional points
                    $returnRatio = $returnAmount / $sale->grand_total;
                    $pointsToReverse = (int) round($originalTransaction->points * $returnRatio);
                } else {
                    // Full return - reverse all points from this sale
                    $pointsToReverse = $originalTransaction->points;
                }

                if ($pointsToReverse <= 0) {
                    return null;
                }

                // Check if points have already been reversed for this sale
                $existingReversal = LoyaltyTransaction::where('customer_id', $customer->id)
                    ->where('sale_id', $sale->id)
                    ->where('type', 'return_reversal')
                    ->exists();

                if ($existingReversal) {
                    throw new InvalidArgumentException(
                        __('Points have already been reversed for this sale return')
                    );
                }

                // Cap reversal at customer's current balance to prevent negative
                $currentPoints = (int) $customer->loyalty_points;
                $pointsToReverse = min($pointsToReverse, $currentPoints);

                if ($pointsToReverse <= 0) {
                    // Customer doesn't have enough points to reverse
                    // Log this as a potential fraud indicator
                    \Log::warning('Loyalty points reversal skipped - customer has insufficient points', [
                        'customer_id' => $customer->id,
                        'sale_id' => $sale->id,
                        'points_earned' => $originalTransaction->points,
                        'current_balance' => $currentPoints,
                    ]);

                    return null;
                }

                return DB::transaction(function () use ($customer, $sale, $pointsToReverse, $userId, $returnAmount) {
                    // Deduct the points from customer balance
                    $customer->decrement('loyalty_points', $pointsToReverse);
                    $customer->refresh();

                    // Update customer tier based on new balance
                    $this->updateCustomerTier($customer);

                    // Create reversal transaction record
                    return LoyaltyTransaction::create([
                        'customer_id' => $customer->id,
                        'branch_id' => $sale->branch_id,
                        'sale_id' => $sale->id,
                        'type' => 'return_reversal',
                        'points' => -$pointsToReverse, // Negative to indicate deduction
                        'balance_after' => $customer->loyalty_points,
                        'description' => $returnAmount !== null
                            ? __('Points reversed for partial return of sale #:invoice (:amount)', [
                                'invoice' => $sale->code,
                                'amount' => number_format($returnAmount, 2),
                            ])
                            : __('Points reversed for return of sale #:invoice', ['invoice' => $sale->code]),
                        'created_by' => $userId,
                    ]);
                });
            },
            operation: 'reversePointsForReturn',
            context: [
                'customer_id' => $customer->id,
                'sale_id' => $sale->id,
                'return_amount' => $returnAmount,
            ],
            defaultValue: null
        );
    }
}
