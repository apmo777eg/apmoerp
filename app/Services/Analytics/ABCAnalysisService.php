<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\Product;
use App\Models\SaleItem;
use App\Traits\HandlesServiceErrors;
use App\Enums\SaleStatus;
use Illuminate\Support\Facades\DB;

/**
 * ABC Analysis Service for Inventory Classification
 *
 * Classifies products into A, B, C categories based on their contribution to total revenue.
 * - A items: Top 20% of products contributing ~80% of revenue (high priority)
 * - B items: Next 30% of products contributing ~15% of revenue (medium priority)
 * - C items: Bottom 50% of products contributing ~5% of revenue (low priority)
 *
 * SECURITY NOTE: All raw SQL expressions in this service use only hardcoded column names.
 * Parameters like $branchId are passed through where() with proper binding.
 * No user input is interpolated into the SQL expressions.
 */
class ABCAnalysisService
{
    use HandlesServiceErrors;

    /**
     * Default thresholds for ABC classification
     */
    protected const DEFAULT_A_THRESHOLD = 80;  // Top products contributing 80% of value

    protected const DEFAULT_B_THRESHOLD = 95;  // Next products contributing 15% of value (80-95%)
    // C items are everything else (95-100%)

    /**
     * Perform ABC analysis on inventory based on revenue contribution
     */
    public function analyzeByRevenue(
        ?int $branchId = null,
        ?string $startDate = null,
        ?string $endDate = null,
        ?int $aThreshold = null,
        ?int $bThreshold = null
    ): array {
        return $this->handleServiceOperation(
            callback: function () use ($branchId, $startDate, $endDate, $aThreshold, $bThreshold) {
                $aThreshold = $aThreshold ?? self::DEFAULT_A_THRESHOLD;
                $bThreshold = $bThreshold ?? self::DEFAULT_B_THRESHOLD;

                $startDate = $startDate ?? now()->subYear()->startOfDay()->toDateString();
                $endDate = $endDate ?? now()->endOfDay()->toDateString();

                // Get product revenue data
                // V35-HIGH-02 FIX: Use sale_date instead of created_at for accurate period filtering
                // V35-MED-06 FIX: Exclude soft-deleted sales and all non-revenue statuses
                $query = SaleItem::query()
                    ->select(
                        'product_id',
                        DB::raw('SUM(line_total) as total_revenue'),
                        DB::raw('SUM(quantity) as total_qty'),
                        DB::raw('COUNT(DISTINCT sale_id) as order_count')
                    )
                    ->whereHas('sale', function ($q) use ($branchId, $startDate, $endDate) {
                        if ($branchId) {
                            $q->where('branch_id', $branchId);
                        }
                        $q->whereBetween('sale_date', [$startDate, $endDate])
                            ->whereNotIn('status', SaleStatus::nonRevenueStatuses());
                    })
                    ->whereNotNull('product_id')
                    ->groupBy('product_id')
                    ->orderByDesc('total_revenue')
                    ->get();

                $totalRevenue = $query->sum('total_revenue');

                if ($totalRevenue <= 0) {
                    return [
                        'categories' => ['A' => [], 'B' => [], 'C' => []],
                        'summary' => [
                            'total_products' => 0,
                            'total_revenue' => 0,
                            'period' => ['start' => $startDate, 'end' => $endDate],
                        ],
                        'distribution' => ['A' => 0, 'B' => 0, 'C' => 0],
                    ];
                }

                $categories = ['A' => [], 'B' => [], 'C' => []];
                $cumulativePercentage = 0;

                foreach ($query as $item) {
                    $product = Product::find($item->product_id);
                    if (! $product) {
                        continue;
                    }

                    $percentage = ($item->total_revenue / $totalRevenue) * 100;
                    $previousCumulative = $cumulativePercentage;
                    $cumulativePercentage += $percentage;

                    // Determine category
                    if ($previousCumulative < $aThreshold) {
                        $category = 'A';
                    } elseif ($previousCumulative < $bThreshold) {
                        $category = 'B';
                    } else {
                        $category = 'C';
                    }

                    $categories[$category][] = [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'sku' => $product->sku,
                        'total_revenue' => round($item->total_revenue, 2),
                        'total_qty' => $item->total_qty,
                        'order_count' => $item->order_count,
                        'revenue_percentage' => round($percentage, 2),
                        'cumulative_percentage' => round($cumulativePercentage, 2),
                        'category' => $category,
                    ];
                }

                return [
                    'categories' => $categories,
                    'summary' => [
                        'total_products' => count($query),
                        'total_revenue' => round($totalRevenue, 2),
                        'period' => ['start' => $startDate, 'end' => $endDate],
                        'thresholds' => ['A' => $aThreshold, 'B' => $bThreshold],
                    ],
                    'distribution' => [
                        'A' => [
                            'count' => count($categories['A']),
                            'percentage' => count($query) > 0 ? round(count($categories['A']) / count($query) * 100, 1) : 0,
                            'revenue_contribution' => $this->calculateRevenue($categories['A'], $totalRevenue),
                        ],
                        'B' => [
                            'count' => count($categories['B']),
                            'percentage' => count($query) > 0 ? round(count($categories['B']) / count($query) * 100, 1) : 0,
                            'revenue_contribution' => $this->calculateRevenue($categories['B'], $totalRevenue),
                        ],
                        'C' => [
                            'count' => count($categories['C']),
                            'percentage' => count($query) > 0 ? round(count($categories['C']) / count($query) * 100, 1) : 0,
                            'revenue_contribution' => $this->calculateRevenue($categories['C'], $totalRevenue),
                        ],
                    ],
                ];
            },
            operation: 'analyzeByRevenue',
            context: ['branch_id' => $branchId]
        );
    }

    /**
     * Analyze inventory by units sold
     */
    public function analyzeByQuantity(
        ?int $branchId = null,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        return $this->handleServiceOperation(
            callback: function () use ($branchId, $startDate, $endDate) {
                $startDate = $startDate ?? now()->subYear()->toDateString();
                $endDate = $endDate ?? now()->toDateString();

                // V35-HIGH-02 FIX: Use sale_date instead of created_at for accurate period filtering
                // V35-MED-06 FIX: Exclude soft-deleted sales and all non-revenue statuses
                $query = SaleItem::query()
                    ->select(
                        'product_id',
                        DB::raw('SUM(quantity) as total_qty'),
                        DB::raw('SUM(line_total) as total_revenue')
                    )
                    ->whereHas('sale', function ($q) use ($branchId, $startDate, $endDate) {
                        if ($branchId) {
                            $q->where('branch_id', $branchId);
                        }
                        $q->whereBetween('sale_date', [$startDate, $endDate])
                            ->whereNotIn('status', SaleStatus::nonRevenueStatuses());
                    })
                    ->whereNotNull('product_id')
                    ->groupBy('product_id')
                    ->orderByDesc('total_qty')
                    ->get();

                $totalQty = $query->sum('total_qty');

                if ($totalQty <= 0) {
                    return ['categories' => ['A' => [], 'B' => [], 'C' => []], 'total_qty' => 0];
                }

                $categories = ['A' => [], 'B' => [], 'C' => []];
                $cumulativePercentage = 0;

                foreach ($query as $item) {
                    $product = Product::find($item->product_id);
                    if (! $product) {
                        continue;
                    }

                    $percentage = ($item->total_qty / $totalQty) * 100;
                    $previousCumulative = $cumulativePercentage;
                    $cumulativePercentage += $percentage;

                    if ($previousCumulative < self::DEFAULT_A_THRESHOLD) {
                        $category = 'A';
                    } elseif ($previousCumulative < self::DEFAULT_B_THRESHOLD) {
                        $category = 'B';
                    } else {
                        $category = 'C';
                    }

                    $categories[$category][] = [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'sku' => $product->sku,
                        'total_qty' => $item->total_qty,
                        'total_revenue' => round($item->total_revenue, 2),
                        'qty_percentage' => round($percentage, 2),
                        'category' => $category,
                    ];
                }

                return [
                    'categories' => $categories,
                    'total_qty' => $totalQty,
                    'period' => ['start' => $startDate, 'end' => $endDate],
                ];
            },
            operation: 'analyzeByQuantity',
            context: ['branch_id' => $branchId]
        );
    }

    /**
     * Get recommendations based on ABC analysis
     */
    public function getRecommendations(?int $branchId = null): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($branchId) {
                $analysis = $this->analyzeByRevenue($branchId);

                $recommendations = [];

                // A items recommendations
                if (count($analysis['categories']['A']) > 0) {
                    $recommendations[] = [
                        'category' => 'A',
                        'title' => __('High-Value Items'),
                        'description' => __(':count products generate :percent% of your revenue', [
                            'count' => count($analysis['categories']['A']),
                            'percent' => $analysis['distribution']['A']['revenue_contribution'],
                        ]),
                        'actions' => [
                            __('Maintain optimal stock levels'),
                            __('Negotiate better supplier terms'),
                            __('Consider safety stock buffer'),
                            __('Regular demand forecasting'),
                        ],
                    ];
                }

                // B items recommendations
                if (count($analysis['categories']['B']) > 0) {
                    $recommendations[] = [
                        'category' => 'B',
                        'title' => __('Medium-Value Items'),
                        'description' => __(':count products with moderate contribution', [
                            'count' => count($analysis['categories']['B']),
                        ]),
                        'actions' => [
                            __('Standard reorder policies'),
                            __('Monitor for category shifts'),
                            __('Review periodically'),
                        ],
                    ];
                }

                // C items recommendations
                if (count($analysis['categories']['C']) > 0) {
                    $recommendations[] = [
                        'category' => 'C',
                        'title' => __('Low-Value Items'),
                        'description' => __(':count products with minimal revenue impact', [
                            'count' => count($analysis['categories']['C']),
                        ]),
                        'actions' => [
                            __('Consider reducing variety'),
                            __('Evaluate discontinuation'),
                            __('Minimize inventory investment'),
                            __('Order less frequently in bulk'),
                        ],
                    ];
                }

                return [
                    'recommendations' => $recommendations,
                    'summary' => $analysis['summary'],
                    'distribution' => $analysis['distribution'],
                ];
            },
            operation: 'getRecommendations',
            context: ['branch_id' => $branchId]
        );
    }

    /**
     * Calculate revenue percentage for a category
     */
    protected function calculateRevenue(array $items, float $totalRevenue): float
    {
        if ($totalRevenue <= 0) {
            return 0;
        }

        $categoryRevenue = array_sum(array_column($items, 'total_revenue'));

        return round(($categoryRevenue / $totalRevenue) * 100, 1);
    }
}
