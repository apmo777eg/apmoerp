<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Traits\HandlesServiceErrors;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Customer Behavior Analysis Service
 *
 * Analyzes customer purchasing patterns, preferences, and behavior
 * to provide actionable insights for marketing and sales strategies.
 */
class CustomerBehaviorService
{
    use HandlesServiceErrors;

    /**
     * Get customer purchase patterns (RFM Analysis)
     * R = Recency (how recently they bought)
     * F = Frequency (how often they buy)
     * M = Monetary (how much they spend)
     */
    public function rfmAnalysis(?int $branchId = null, ?string $startDate = null): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($branchId, $startDate) {
                $startDate = $startDate ?? now()->subYear()->toDateString();

                $query = Sale::query()
                    ->select(
                        'customer_id',
                        DB::raw('MAX(created_at) as last_purchase'),
                        DB::raw('COUNT(*) as purchase_count'),
                        DB::raw('SUM(total_amount) as total_spent'),
                        DB::raw('AVG(total_amount) as avg_order_value')
                    )
                    ->whereNotNull('customer_id')
                    ->where('status', '!=', 'cancelled')
                    ->where('created_at', '>=', $startDate);

                if ($branchId) {
                    $query->where('branch_id', $branchId);
                }

                $data = $query->groupBy('customer_id')->get();

                if ($data->isEmpty()) {
                    return [
                        'segments' => [],
                        'summary' => ['total_customers' => 0],
                    ];
                }

                // Calculate RFM scores
                $customers = $data->map(function ($row) {
                    $customer = Customer::find($row->customer_id);
                    $daysSinceLastPurchase = now()->diffInDays($row->last_purchase);

                    return [
                        'customer_id' => $row->customer_id,
                        'customer_name' => $customer?->name ?? __('Unknown'),
                        'customer_email' => $customer?->email,
                        'recency_days' => $daysSinceLastPurchase,
                        'frequency' => $row->purchase_count,
                        'monetary' => round($row->total_spent, 2),
                        'avg_order_value' => round($row->avg_order_value, 2),
                        'last_purchase' => $row->last_purchase,
                    ];
                });

                // Calculate percentile thresholds
                $recencyValues = $customers->pluck('recency_days')->sort()->values();
                $frequencyValues = $customers->pluck('frequency')->sort()->values();
                $monetaryValues = $customers->pluck('monetary')->sort()->values();

                // Assign RFM scores (1-5, with 5 being best)
                $scoredCustomers = $customers->map(function ($c) use ($recencyValues, $frequencyValues, $monetaryValues) {
                    $rScore = $this->getPercentileScore($c['recency_days'], $recencyValues, true); // Lower is better
                    $fScore = $this->getPercentileScore($c['frequency'], $frequencyValues, false); // Higher is better
                    $mScore = $this->getPercentileScore($c['monetary'], $monetaryValues, false); // Higher is better

                    $c['r_score'] = $rScore;
                    $c['f_score'] = $fScore;
                    $c['m_score'] = $mScore;
                    $c['rfm_score'] = $rScore.$fScore.$mScore;
                    $c['segment'] = $this->determineSegment($rScore, $fScore, $mScore);

                    return $c;
                });

                // Group by segment
                $segments = $scoredCustomers->groupBy('segment')->map(function ($group, $segment) {
                    return [
                        'segment' => $segment,
                        'segment_label' => $this->getSegmentLabel($segment),
                        'count' => $group->count(),
                        'total_revenue' => round($group->sum('monetary'), 2),
                        'avg_revenue' => round($group->avg('monetary'), 2),
                        'customers' => $group->take(10)->values()->toArray(),
                    ];
                });

                return [
                    'segments' => $segments->values()->toArray(),
                    'all_customers' => $scoredCustomers->sortByDesc('monetary')->values()->toArray(),
                    'summary' => [
                        'total_customers' => $customers->count(),
                        'total_revenue' => round($customers->sum('monetary'), 2),
                        'avg_order_value' => round($customers->avg('avg_order_value'), 2),
                        'period_start' => $startDate,
                    ],
                ];
            },
            operation: 'rfmAnalysis',
            context: ['branch_id' => $branchId]
        );
    }

    /**
     * Analyze customer purchase frequency
     */
    public function purchaseFrequency(?int $branchId = null): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($branchId) {
                $query = Sale::query()
                    ->select(
                        'customer_id',
                        DB::raw('COUNT(*) as purchase_count'),
                        DB::raw('MIN(created_at) as first_purchase'),
                        DB::raw('MAX(created_at) as last_purchase')
                    )
                    ->whereNotNull('customer_id')
                    ->where('status', '!=', 'cancelled')
                    ->where('created_at', '>=', now()->subYear());

                if ($branchId) {
                    $query->where('branch_id', $branchId);
                }

                $data = $query->groupBy('customer_id')
                    ->having('purchase_count', '>', 1)
                    ->get();

                $frequencies = $data->map(function ($row) {
                    $daysBetween = now()->parse($row->first_purchase)->diffInDays($row->last_purchase);
                    $avgDaysBetweenPurchases = $row->purchase_count > 1
                        ? $daysBetween / ($row->purchase_count - 1)
                        : 0;

                    return [
                        'customer_id' => $row->customer_id,
                        'purchase_count' => $row->purchase_count,
                        'avg_days_between' => round($avgDaysBetweenPurchases, 1),
                        'first_purchase' => $row->first_purchase,
                        'last_purchase' => $row->last_purchase,
                    ];
                });

                // Group by frequency
                $groups = [
                    'weekly' => $frequencies->filter(fn ($f) => $f['avg_days_between'] <= 7)->count(),
                    'biweekly' => $frequencies->filter(fn ($f) => $f['avg_days_between'] > 7 && $f['avg_days_between'] <= 14)->count(),
                    'monthly' => $frequencies->filter(fn ($f) => $f['avg_days_between'] > 14 && $f['avg_days_between'] <= 30)->count(),
                    'quarterly' => $frequencies->filter(fn ($f) => $f['avg_days_between'] > 30 && $f['avg_days_between'] <= 90)->count(),
                    'infrequent' => $frequencies->filter(fn ($f) => $f['avg_days_between'] > 90)->count(),
                ];

                return [
                    'frequency_distribution' => $groups,
                    'avg_days_between_purchases' => round($frequencies->avg('avg_days_between'), 1),
                    'repeat_customers' => $frequencies->count(),
                ];
            },
            operation: 'purchaseFrequency',
            context: ['branch_id' => $branchId]
        );
    }

    /**
     * Analyze product preferences by customer
     */
    public function productPreferences(int $customerId): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($customerId) {
                $purchases = SaleItem::query()
                    ->select(
                        'product_id',
                        DB::raw('SUM(qty) as total_qty'),
                        DB::raw('SUM(line_total) as total_spent'),
                        DB::raw('COUNT(DISTINCT sale_id) as purchase_count')
                    )
                    ->whereHas('sale', function ($q) use ($customerId) {
                        $q->where('customer_id', $customerId)
                            ->where('status', '!=', 'cancelled');
                    })
                    ->whereNotNull('product_id')
                    ->groupBy('product_id')
                    ->orderByDesc('total_spent')
                    ->limit(20)
                    ->get();

                return $purchases->map(function ($item) {
                    $product = \App\Models\Product::find($item->product_id);

                    return [
                        'product_id' => $item->product_id,
                        'product_name' => $product?->name ?? __('Unknown'),
                        'category' => $product?->category?->name,
                        'total_qty' => $item->total_qty,
                        'total_spent' => round($item->total_spent, 2),
                        'purchase_count' => $item->purchase_count,
                    ];
                })->toArray();
            },
            operation: 'productPreferences',
            context: ['customer_id' => $customerId]
        );
    }

    /**
     * Get customers at risk of churning
     */
    public function atRiskCustomers(?int $branchId = null, int $inactiveDays = 90): Collection
    {
        return $this->handleServiceOperation(
            callback: function () use ($branchId, $inactiveDays) {
                $activeThreshold = now()->subDays($inactiveDays);

                // Get customers who were active but haven't purchased recently
                $query = Customer::query()
                    ->whereHas('sales', function ($q) use ($activeThreshold) {
                        $q->where('created_at', '<', $activeThreshold)
                            ->where('status', '!=', 'cancelled');
                    })
                    ->whereDoesntHave('sales', function ($q) use ($activeThreshold) {
                        $q->where('created_at', '>=', $activeThreshold)
                            ->where('status', '!=', 'cancelled');
                    })
                    ->withCount(['sales' => function ($q) {
                        $q->where('status', '!=', 'cancelled');
                    }])
                    ->withSum(['sales' => function ($q) {
                        $q->where('status', '!=', 'cancelled');
                    }], 'total_amount');

                if ($branchId) {
                    $query->where('branch_id', $branchId);
                }

                return $query->orderByDesc('sales_sum_total_amount')
                    ->limit(50)
                    ->get()
                    ->map(function ($customer) {
                        $lastSale = $customer->sales()->latest()->first();

                        return [
                            'id' => $customer->id,
                            'name' => $customer->name,
                            'email' => $customer->email,
                            'phone' => $customer->phone,
                            'total_purchases' => $customer->sales_count,
                            'total_spent' => round($customer->sales_sum_total_amount ?? 0, 2),
                            'last_purchase' => $lastSale?->created_at?->toDateString(),
                            'days_inactive' => $lastSale ? now()->diffInDays($lastSale->created_at) : null,
                        ];
                    });
            },
            operation: 'atRiskCustomers',
            context: ['branch_id' => $branchId, 'inactive_days' => $inactiveDays]
        );
    }

    /**
     * Get customer lifetime value (CLV)
     */
    public function customerLifetimeValue(?int $branchId = null, int $limit = 50): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($branchId, $limit) {
                $query = Customer::query()
                    ->withCount(['sales' => function ($q) {
                        $q->where('status', '!=', 'cancelled');
                    }])
                    ->withSum(['sales' => function ($q) {
                        $q->where('status', '!=', 'cancelled');
                    }], 'total_amount')
                    ->having('sales_count', '>', 0);

                if ($branchId) {
                    $query->where('branch_id', $branchId);
                }

                $customers = $query->orderByDesc('sales_sum_total_amount')
                    ->limit($limit)
                    ->get();

                $totalCLV = $customers->sum('sales_sum_total_amount');

                return [
                    'customers' => $customers->map(function ($c) use ($totalCLV) {
                        $firstSale = $c->sales()->oldest()->first();
                        $monthsActive = $firstSale ? max(1, now()->diffInMonths($firstSale->created_at)) : 1;
                        $monthlyValue = ($c->sales_sum_total_amount ?? 0) / $monthsActive;

                        return [
                            'id' => $c->id,
                            'name' => $c->name,
                            'email' => $c->email,
                            'total_purchases' => $c->sales_count,
                            'lifetime_value' => round($c->sales_sum_total_amount ?? 0, 2),
                            'monthly_value' => round($monthlyValue, 2),
                            'months_active' => $monthsActive,
                            'clv_percentage' => $totalCLV > 0 ? round(($c->sales_sum_total_amount / $totalCLV) * 100, 2) : 0,
                            'tier' => $c->customer_tier ?? 'standard',
                        ];
                    })->toArray(),
                    'summary' => [
                        'total_clv' => round($totalCLV, 2),
                        // FIX N-04: Use correct withSum alias (sales_sum_total_amount, not sales_sum_grand_total)
                        'avg_clv' => round($customers->avg('sales_sum_total_amount') ?? 0, 2),
                        'customer_count' => $customers->count(),
                    ],
                ];
            },
            operation: 'customerLifetimeValue',
            context: ['branch_id' => $branchId]
        );
    }

    /**
     * Get percentile score (1-5)
     */
    protected function getPercentileScore($value, Collection $values, bool $inverse = false): int
    {
        if ($values->isEmpty()) {
            return 3;
        }

        $count = $values->count();
        $position = $values->search(function ($v) use ($value) {
            return $v >= $value;
        });

        if ($position === false) {
            $position = $count;
        }

        $percentile = ($position / $count) * 100;

        if ($inverse) {
            // For recency, lower values are better
            if ($percentile <= 20) {
                return 5;
            }
            if ($percentile <= 40) {
                return 4;
            }
            if ($percentile <= 60) {
                return 3;
            }
            if ($percentile <= 80) {
                return 2;
            }

            return 1;
        } else {
            // For frequency and monetary, higher values are better
            if ($percentile >= 80) {
                return 5;
            }
            if ($percentile >= 60) {
                return 4;
            }
            if ($percentile >= 40) {
                return 3;
            }
            if ($percentile >= 20) {
                return 2;
            }

            return 1;
        }
    }

    /**
     * Determine customer segment based on RFM scores
     */
    protected function determineSegment(int $r, int $f, int $m): string
    {
        $avgScore = ($r + $f + $m) / 3;

        if ($r >= 4 && $f >= 4 && $m >= 4) {
            return 'champions';
        }
        if ($r >= 4 && $f >= 3) {
            return 'loyal_customers';
        }
        if ($r >= 4 && $f <= 2) {
            return 'new_customers';
        }
        if ($r <= 2 && $f >= 4 && $m >= 4) {
            return 'at_risk';
        }
        if ($r <= 2 && $f >= 2) {
            return 'needs_attention';
        }
        if ($r <= 2 && $f <= 2) {
            return 'hibernating';
        }
        if ($r >= 3 && $m >= 4) {
            return 'potential_loyalists';
        }

        return 'regular';
    }

    /**
     * Get segment label
     */
    protected function getSegmentLabel(string $segment): string
    {
        return match ($segment) {
            'champions' => __('Champions'),
            'loyal_customers' => __('Loyal Customers'),
            'new_customers' => __('New Customers'),
            'at_risk' => __('At Risk'),
            'needs_attention' => __('Needs Attention'),
            'hibernating' => __('Hibernating'),
            'potential_loyalists' => __('Potential Loyalists'),
            default => __('Regular'),
        };
    }
}
