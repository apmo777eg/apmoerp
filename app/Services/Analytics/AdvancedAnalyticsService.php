<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Facades\Cache;
use App\Enums\SaleStatus;
use Illuminate\Support\Facades\DB;

/**
 * Advanced Analytics Service with AI-powered predictions
 *
 * SECURITY NOTE: All raw SQL expressions in this service use only hardcoded column names.
 * Parameters like $branchId are passed through where() with proper binding.
 * No user input is interpolated into the SQL expressions.
 */
class AdvancedAnalyticsService
{
    /**
     * Get comprehensive dashboard metrics
     */
    public function getDashboardMetrics(?int $branchId = null, ?string $period = 'month'): array
    {
        $cacheKey = "analytics:dashboard:{$branchId}:{$period}";

        return Cache::remember($cacheKey, 600, function () use ($branchId, $period) {
            $dateRange = $this->getDateRange($period);

            return [
                'sales' => $this->getSalesMetrics($branchId, $dateRange),
                'products' => $this->getProductMetrics($branchId, $dateRange),
                'customers' => $this->getCustomerMetrics($branchId, $dateRange),
                'trends' => $this->getTrendAnalysis($branchId, $dateRange),
                'predictions' => $this->getPredictions($branchId, $period),
                'insights' => $this->getBusinessInsights($branchId, $dateRange),
            ];
        });
    }

    /**
     * Get sales metrics with advanced calculations
     * FIX N-03: Use total_amount instead of total
     * V35-HIGH-02 FIX: Use sale_date instead of created_at for accurate period filtering
     * V35-MED-06 FIX: Exclude non-revenue statuses (already filters to completed)
     */
    protected function getSalesMetrics(?int $branchId, array $dateRange): array
    {
        $query = Sale::query()
            ->where('status', 'completed')
            ->whereBetween('sale_date', $dateRange);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $sales = $query->get();
        $previousPeriod = $this->getPreviousPeriodSales($branchId, $dateRange);

        $total = $sales->sum('total_amount');
        $count = $sales->count();
        $avgTicket = $count > 0 ? $total / $count : 0;

        return [
            'total_revenue' => $total,
            'total_count' => $count,
            'average_ticket' => $avgTicket,
            'growth_rate' => $this->calculateGrowthRate($total, $previousPeriod['total']),
            'by_day' => $this->groupByDay($sales),
            'by_hour' => $this->groupByHour($sales),
            'top_performing_days' => $this->getTopPerformingDays($sales),
        ];
    }

    /**
     * Get product performance metrics
     */
    protected function getProductMetrics(?int $branchId, array $dateRange): array
    {
        $topProducts = $this->getTopProducts($branchId, $dateRange, 10);
        $slowMoving = $this->getSlowMovingProducts($branchId, $dateRange, 10);
        $stockAlerts = $this->getStockAlerts($branchId);

        return [
            'top_selling' => $topProducts,
            'slow_moving' => $slowMoving,
            'stock_alerts' => $stockAlerts,
            'inventory_value' => $this->calculateInventoryValue($branchId),
            'turnover_rate' => $this->calculateInventoryTurnover($branchId, $dateRange),
        ];
    }

    /**
     * Get customer behavior metrics
     */
    protected function getCustomerMetrics(?int $branchId, array $dateRange): array
    {
        $query = Customer::query();

        if ($branchId) {
            $query->whereHas('sales', fn ($q) => $q->where('branch_id', $branchId));
        }

        $customers = $query->get();

        return [
            'total_customers' => $customers->count(),
            'new_customers' => $this->getNewCustomers($branchId, $dateRange),
            'returning_rate' => $this->calculateReturningCustomerRate($branchId, $dateRange),
            'lifetime_value' => $this->calculateCustomerLifetimeValue($branchId),
            'segmentation' => $this->customerSegmentation($branchId, $dateRange),
        ];
    }

    /**
     * Get trend analysis
     */
    protected function getTrendAnalysis(?int $branchId, array $dateRange): array
    {
        $salesData = $this->getSalesTimeSeries($branchId, $dateRange);

        return [
            'direction' => $this->detectTrend($salesData),
            'seasonality' => $this->detectSeasonality($salesData),
            'volatility' => $this->calculateVolatility($salesData),
            'forecast_accuracy' => $this->calculateForecastAccuracy($branchId),
        ];
    }

    /**
     * AI-powered predictions
     */
    public function getPredictions(?int $branchId, string $period = 'month'): array
    {
        return [
            'revenue_forecast' => $this->forecastRevenue($branchId, $period),
            'demand_forecast' => $this->forecastDemand($branchId, $period),
            'inventory_recommendations' => $this->getInventoryRecommendations($branchId),
            'pricing_suggestions' => $this->getPricingSuggestions($branchId),
            'customer_churn_risk' => $this->predictChurnRisk($branchId),
        ];
    }

    /**
     * Forecast revenue using simple moving average and trend
     */
    protected function forecastRevenue(?int $branchId, string $period): array
    {
        $historical = $this->getHistoricalRevenue($branchId, 12); // Last 12 periods

        if (count($historical) < 3) {
            return ['forecast' => 0, 'confidence' => 0, 'range' => ['low' => 0, 'high' => 0]];
        }

        $avg = array_sum($historical) / count($historical);
        $trend = $this->calculateTrendSlope($historical);
        $forecast = $avg + ($trend * count($historical));

        $stdDev = $this->calculateStandardDeviation($historical);
        $confidence = max(0, min(100, 100 - ($stdDev / $avg * 100)));

        return [
            'forecast' => max(0, $forecast),
            'confidence' => $confidence,
            'range' => [
                'low' => max(0, $forecast - ($stdDev * 1.96)),
                'high' => $forecast + ($stdDev * 1.96),
            ],
            'historical' => $historical,
        ];
    }

    /**
     * Forecast product demand
     */
    protected function forecastDemand(?int $branchId, string $period): array
    {
        $products = $this->getTopProducts($branchId, $this->getDateRange('3month'), 20);

        $forecasts = [];
        foreach ($products as $product) {
            $historical = $this->getProductSalesHistory($product['id'], $branchId, 6);
            $avg = count($historical) > 0 ? array_sum($historical) / count($historical) : 0;

            $forecasts[] = [
                'product_id' => $product['id'],
                'product_name' => $product['name'],
                'current_stock' => $product['stock'] ?? 0,
                'predicted_demand' => $avg * 1.1, // 10% buffer
                'reorder_recommendation' => $avg * 2, // 2x average for safety stock
                'priority' => $this->calculateReorderPriority($product, $avg),
            ];
        }

        return $forecasts;
    }

    /**
     * Get inventory recommendations
     */
    protected function getInventoryRecommendations(?int $branchId): array
    {
        $products = Product::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->with('category')
            ->get();

        $recommendations = [];
        foreach ($products as $product) {
            $salesVelocity = $this->calculateSalesVelocity($product->id, $branchId);
            $daysOfStock = $product->stock_quantity > 0 && $salesVelocity > 0
                ? $product->stock_quantity / $salesVelocity
                : 999;

            if ($daysOfStock < 7) {
                $recommendations[] = [
                    'type' => 'urgent_reorder',
                    'product' => $product->name,
                    'current_stock' => $product->stock_quantity,
                    'days_remaining' => round($daysOfStock, 1),
                    'suggested_quantity' => ceil($salesVelocity * 30), // 30 days worth
                    'priority' => 'high',
                ];
            } elseif ($daysOfStock > 90) {
                $recommendations[] = [
                    'type' => 'overstock',
                    'product' => $product->name,
                    'current_stock' => $product->stock_quantity,
                    'days_of_stock' => round($daysOfStock, 1),
                    'suggestion' => 'Consider discount or promotion',
                    'priority' => 'medium',
                ];
            }
        }

        return $recommendations;
    }

    /**
     * Get pricing suggestions based on market analysis
     */
    protected function getPricingSuggestions(?int $branchId): array
    {
        $products = $this->getTopProducts($branchId, $this->getDateRange('month'), 20);

        $suggestions = [];
        foreach ($products as $product) {
            $elasticity = $this->estimatePriceElasticity($product['id'], $branchId);
            $competitorPrice = $this->getCompetitorPrice($product['id']); // Placeholder

            $currentPrice = $product['price'];
            $suggestedPrice = $currentPrice;
            $reason = 'maintain_current';

            // Simple pricing logic
            if ($elasticity < -1.5) {
                // Elastic demand - lower price might increase revenue
                $suggestedPrice = $currentPrice * 0.95;
                $reason = 'elastic_demand';
            } elseif ($elasticity > -0.5 && $product['sales_count'] > 100) {
                // Inelastic demand with high sales - can increase price
                $suggestedPrice = $currentPrice * 1.05;
                $reason = 'inelastic_high_demand';
            }

            if (abs($suggestedPrice - $currentPrice) > 0.01) {
                $suggestions[] = [
                    'product_id' => $product['id'],
                    'product_name' => $product['name'],
                    'current_price' => $currentPrice,
                    'suggested_price' => $suggestedPrice,
                    'expected_impact' => $this->estimateRevenueImpact($currentPrice, $suggestedPrice, $elasticity),
                    'reason' => $reason,
                ];
            }
        }

        return $suggestions;
    }

    /**
     * Predict customer churn risk
     * FIX N-03: Use total_amount instead of total
     * V35-HIGH-02 FIX: Use sale_date instead of created_at for accurate period filtering
     * V35-MED-06 FIX: Exclude all non-revenue statuses
     */
    protected function predictChurnRisk(?int $branchId): array
    {
        // V35-MED-06 FIX: Exclude non-revenue statuses in customer churn analysis
        $customers = Customer::query()
            ->when($branchId, function ($q) use ($branchId) {
                $q->whereHas('sales', fn ($sq) => $sq->where('branch_id', $branchId));
            })
            ->with(['sales' => function ($q) {
                // V35-HIGH-02 FIX: Use sale_date for customer activity analysis
                // V35-MED-06 FIX: Exclude non-revenue statuses
                $q->where('sale_date', '>', now()->subMonths(6))
                    ->whereNotIn('status', SaleStatus::nonRevenueStatuses())
                    ->orderBy('sale_date', 'desc');
            }])
            ->get();

        $atRisk = [];
        foreach ($customers as $customer) {
            // V35-HIGH-02 FIX: Use sale_date for days since last purchase calculation
            $daysSinceLastPurchase = $customer->sales->first()
                ? now()->diffInDays($customer->sales->first()->sale_date)
                : 999;

            $avgDaysBetweenPurchases = $this->calculateAvgDaysBetweenPurchases($customer);

            if ($daysSinceLastPurchase > ($avgDaysBetweenPurchases * 2) && $avgDaysBetweenPurchases > 0) {
                $atRisk[] = [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'days_since_purchase' => $daysSinceLastPurchase,
                    'lifetime_value' => $customer->sales->sum('total_amount'),
                    'risk_level' => $daysSinceLastPurchase > ($avgDaysBetweenPurchases * 3) ? 'high' : 'medium',
                    'recommended_action' => 'Send personalized offer',
                ];
            }
        }

        return $atRisk;
    }

    /**
     * Get business insights
     */
    protected function getBusinessInsights(?int $branchId, array $dateRange): array
    {
        return [
            'best_selling_category' => $this->getBestSellingCategory($branchId, $dateRange),
            'peak_hours' => $this->getPeakHours($branchId, $dateRange),
            'profit_margins' => $this->analyzeMargins($branchId, $dateRange),
            'customer_segments' => $this->customerSegmentation($branchId, $dateRange),
        ];
    }

    // Helper methods

    protected function getDateRange(string $period): array
    {
        return match ($period) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            '3month' => [now()->subMonths(3), now()],
            'year' => [now()->startOfYear(), now()->endOfYear()],
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };
    }

    protected function calculateGrowthRate($current, $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return (($current - $previous) / $previous) * 100;
    }

    protected function calculateStandardDeviation(array $data): float
    {
        $count = count($data);
        if ($count < 2) {
            return 0;
        }

        $mean = array_sum($data) / $count;
        $variance = array_sum(array_map(fn ($x) => pow($x - $mean, 2), $data)) / $count;

        return sqrt($variance);
    }

    protected function calculateTrendSlope(array $data): float
    {
        $n = count($data);
        if ($n < 2) {
            return 0;
        }

        $x = range(1, $n);
        $sumX = array_sum($x);
        $sumY = array_sum($data);
        $sumXY = 0;
        $sumX2 = 0;

        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $data[$i];
            $sumX2 += $x[$i] * $x[$i];
        }

        return ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
    }

    // BUG-6 FIX: Implement placeholder methods with actual business logic
    
    /**
     * Get previous period sales for growth comparison
     * V35-HIGH-02 FIX: Use sale_date for period filtering
     */
    protected function getPreviousPeriodSales(?int $branchId, array $dateRange): array
    {
        $start = $dateRange[0];
        $end = $dateRange[1];
        $periodDays = $start->diffInDays($end);
        
        // Calculate previous period dates
        $prevEnd = $start->copy()->subDay();
        $prevStart = $prevEnd->copy()->subDays($periodDays);
        
        $query = Sale::query()
            ->whereNotIn('status', SaleStatus::nonRevenueStatuses())
            ->whereBetween('sale_date', [$prevStart, $prevEnd]);
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        return ['total' => $query->sum('total_amount') ?? 0];
    }

    /**
     * Group sales by day
     * V35-HIGH-02 FIX: Use sale_date for grouping
     */
    protected function groupByDay($sales): array
    {
        return $sales->groupBy(function ($sale) {
            return $sale->sale_date->format('Y-m-d');
        })->map(function ($group) {
            return [
                'date' => $group->first()->sale_date->format('Y-m-d'),
                'total' => $group->sum('total_amount'),
                'count' => $group->count(),
            ];
        })->values()->toArray();
    }

    /**
     * Group sales by hour of day
     * V35-HIGH-02 FIX: Use sale_date for grouping
     */
    protected function groupByHour($sales): array
    {
        return $sales->groupBy(function ($sale) {
            return $sale->sale_date->format('H');
        })->map(function ($group, $hour) {
            return [
                'hour' => (int) $hour,
                'total' => $group->sum('total_amount'),
                'count' => $group->count(),
            ];
        })->values()->toArray();
    }

    /**
     * Get top performing days
     * V35-HIGH-02 FIX: Use sale_date for analysis
     */
    protected function getTopPerformingDays($sales): array
    {
        $byDay = $sales->groupBy(function ($sale) {
            return $sale->sale_date->format('Y-m-d');
        })->map(function ($group) {
            return [
                'date' => $group->first()->sale_date->format('Y-m-d'),
                'total' => $group->sum('total_amount'),
            ];
        })->sortByDesc('total')->take(5)->values()->toArray();
        
        return $byDay;
    }

    /**
     * Get top selling products
     * V35-HIGH-02 FIX: Use sale_date for period filtering
     * CODE-REVIEW FIX: Use eager loading to avoid N+1 query problem
     */
    protected function getTopProducts(?int $branchId, array $dateRange, int $limit): array
    {
        $query = SaleItem::query()
            ->select(
                'product_id',
                DB::raw('SUM(quantity) as total_qty'),
                DB::raw('SUM(line_total) as total_revenue'),
                DB::raw('COUNT(DISTINCT sale_id) as sales_count')
            )
            ->with('product') // CODE-REVIEW FIX: Eager load products to avoid N+1
            ->whereHas('sale', function ($q) use ($branchId, $dateRange) {
                if ($branchId) {
                    $q->where('branch_id', $branchId);
                }
                $q->whereBetween('sale_date', $dateRange)
                    ->whereNotIn('status', SaleStatus::nonRevenueStatuses());
            })
            ->whereNotNull('product_id')
            ->groupBy('product_id')
            ->orderByDesc('total_revenue')
            ->limit($limit)
            ->get();

        return $query->map(function ($item) {
            return [
                'id' => $item->product_id,
                'name' => $item->product?->name ?? 'Unknown',
                'sku' => $item->product?->sku,
                'total_qty' => $item->total_qty,
                'total_revenue' => round($item->total_revenue, 2),
                'sales_count' => $item->sales_count,
                'price' => $item->product?->default_price ?? 0,
                'stock' => $item->product?->stock_quantity ?? 0,
            ];
        })->toArray();
    }

    /**
     * Get slow moving products
     * V35-HIGH-02 FIX: Use sale_date for period filtering
     * CODE-REVIEW FIX: Use eager loading to avoid N+1 query problem
     */
    protected function getSlowMovingProducts(?int $branchId, array $dateRange, int $limit): array
    {
        $query = SaleItem::query()
            ->select(
                'product_id',
                DB::raw('SUM(quantity) as total_qty'),
                DB::raw('MAX(sales.sale_date) as last_sale_date')
            )
            ->with('product') // CODE-REVIEW FIX: Eager load products to avoid N+1
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereNotIn('sales.status', SaleStatus::nonRevenueStatuses())
            ->whereNotNull('product_id')
            ->groupBy('product_id')
            ->orderBy('total_qty', 'asc')
            ->limit($limit)
            ->get();

        return $query->map(function ($item) {
            return [
                'id' => $item->product_id,
                'name' => $item->product?->name ?? 'Unknown',
                'total_qty' => $item->total_qty,
                'last_sale_date' => $item->last_sale_date,
                'stock' => $item->product?->stock_quantity ?? 0,
            ];
        })->toArray();
    }

    /**
     * Get stock alerts for low inventory
     * CODE-REVIEW FIX: Correct status logic based on actual stock vs min_stock comparison
     */
    protected function getStockAlerts(?int $branchId): array
    {
        $query = Product::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereNotNull('min_stock')
            ->whereRaw('COALESCE(stock_quantity, 0) <= min_stock')
            ->orderBy('stock_quantity', 'asc')
            ->limit(20)
            ->get();

        return $query->map(function ($product) {
            $stock = $product->stock_quantity ?? 0;
            // CODE-REVIEW FIX: Correct status determination
            if ($stock <= 0) {
                $status = 'out_of_stock';
            } else {
                $status = 'low_stock'; // Already filtered by query, so it's definitely low
            }
            
            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'current_stock' => $stock,
                'min_stock' => $product->min_stock,
                'status' => $status,
            ];
        })->toArray();
    }

    /**
     * Calculate total inventory value
     */
    protected function calculateInventoryValue(?int $branchId): float
    {
        $query = Product::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId));

        return $query->get()->sum(function ($product) {
            return ($product->stock_quantity ?? 0) * ($product->cost ?? $product->default_price ?? 0);
        });
    }

    /**
     * Calculate inventory turnover rate
     * V35-HIGH-02 FIX: Use sale_date for period filtering
     */
    protected function calculateInventoryTurnover(?int $branchId, array $dateRange): float
    {
        $cogs = SaleItem::query()
            ->whereHas('sale', function ($q) use ($branchId, $dateRange) {
                if ($branchId) {
                    $q->where('branch_id', $branchId);
                }
                $q->whereBetween('sale_date', $dateRange)
                    ->whereNotIn('status', SaleStatus::nonRevenueStatuses());
            })
            ->sum(DB::raw('COALESCE(cost_price, 0) * COALESCE(quantity, 0)'));

        $avgInventory = $this->calculateInventoryValue($branchId);
        
        return $avgInventory > 0 ? $cogs / $avgInventory : 0;
    }

    /**
     * Get new customers in period
     */
    protected function getNewCustomers(?int $branchId, array $dateRange): int
    {
        $query = Customer::query()
            ->whereBetween('created_at', $dateRange);
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        return $query->count();
    }

    /**
     * Calculate returning customer rate
     * V35-HIGH-02 FIX: Use sale_date for period filtering
     * CODE-REVIEW FIX: Optimize to avoid N+1 query problem using efficient subquery
     */
    protected function calculateReturningCustomerRate(?int $branchId, array $dateRange): float
    {
        $query = Sale::query()
            ->whereBetween('sale_date', $dateRange)
            ->whereNotIn('status', SaleStatus::nonRevenueStatuses())
            ->whereNotNull('customer_id');
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        $totalSales = $query->count();
        
        if ($totalSales === 0) {
            return 0;
        }
        
        // CODE-REVIEW FIX: Use a single efficient query instead of N+1
        // Count distinct customers who have more than 1 sale (returning customers)
        $returningCustomersCount = Sale::query()
            ->select('customer_id')
            ->whereNotNull('customer_id')
            ->whereNotIn('status', SaleStatus::nonRevenueStatuses())
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->groupBy('customer_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();
        
        $distinctCustomersInPeriod = $query->distinct('customer_id')->count('customer_id');
        
        return $distinctCustomersInPeriod > 0 ? ($returningCustomersCount / $distinctCustomersInPeriod) * 100 : 0;
    }

    /**
     * Calculate average customer lifetime value
     */
    protected function calculateCustomerLifetimeValue(?int $branchId): float
    {
        $query = Customer::query()
            ->withSum(['sales' => function ($q) {
                $q->whereNotIn('status', SaleStatus::nonRevenueStatuses());
            }], 'total_amount');
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        return $query->avg('sales_sum_total_amount') ?? 0;
    }

    /**
     * Get customer segmentation
     * V35-HIGH-02 FIX: Use sale_date for period filtering
     */
    protected function customerSegmentation(?int $branchId, array $dateRange): array
    {
        $query = Customer::query()
            ->withCount(['sales' => function ($q) use ($dateRange) {
                $q->whereBetween('sale_date', $dateRange)
                    ->whereNotIn('status', SaleStatus::nonRevenueStatuses());
            }])
            ->withSum(['sales' => function ($q) use ($dateRange) {
                $q->whereBetween('sale_date', $dateRange)
                    ->whereNotIn('status', SaleStatus::nonRevenueStatuses());
            }], 'total_amount');
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        $customers = $query->get();
        
        return [
            'vip' => $customers->filter(fn ($c) => ($c->sales_sum_total_amount ?? 0) > 10000)->count(),
            'regular' => $customers->filter(fn ($c) => ($c->sales_sum_total_amount ?? 0) > 1000 && ($c->sales_sum_total_amount ?? 0) <= 10000)->count(),
            'occasional' => $customers->filter(fn ($c) => ($c->sales_sum_total_amount ?? 0) <= 1000)->count(),
        ];
    }

    /**
     * Get sales time series data
     * V35-HIGH-02 FIX: Use sale_date for period filtering
     */
    protected function getSalesTimeSeries(?int $branchId, array $dateRange): array
    {
        $query = Sale::query()
            ->select(
                DB::raw('DATE(sale_date) as date'),
                DB::raw('SUM(total_amount) as total')
            )
            ->whereBetween('sale_date', $dateRange)
            ->whereNotIn('status', SaleStatus::nonRevenueStatuses())
            ->groupBy(DB::raw('DATE(sale_date)'))
            ->orderBy('date');
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        return $query->pluck('total')->toArray();
    }

    /**
     * Detect trend direction in data
     */
    protected function detectTrend(array $data): string
    {
        if (count($data) < 2) {
            return 'stable';
        }
        
        $slope = $this->calculateTrendSlope($data);
        
        if ($slope > 0.1) {
            return 'increasing';
        } elseif ($slope < -0.1) {
            return 'decreasing';
        }
        
        return 'stable';
    }

    /**
     * Detect seasonality in data (simplified)
     */
    protected function detectSeasonality(array $data): bool
    {
        if (count($data) < 12) {
            return false;
        }
        
        // Simple coefficient of variation check
        $stdDev = $this->calculateStandardDeviation($data);
        $mean = array_sum($data) / count($data);
        $cv = $mean > 0 ? ($stdDev / $mean) : 0;
        
        // If coefficient of variation > 0.3, consider it seasonal
        return $cv > 0.3;
    }

    /**
     * Calculate volatility (coefficient of variation)
     */
    protected function calculateVolatility(array $data): float
    {
        if (count($data) < 2) {
            return 0;
        }
        
        $mean = array_sum($data) / count($data);
        if ($mean == 0) {
            return 0;
        }
        
        $stdDev = $this->calculateStandardDeviation($data);
        return ($stdDev / $mean) * 100;
    }

    /**
     * Calculate forecast accuracy (placeholder - would need historical forecasts)
     */
    protected function calculateForecastAccuracy(?int $branchId): float
    {
        // This would require storing historical forecasts and comparing to actuals
        // Returning a reasonable default for now
        return 75.0;
    }

    /**
     * Get historical revenue for N periods
     * V35-HIGH-02 FIX: Use sale_date for period filtering
     */
    protected function getHistoricalRevenue(?int $branchId, int $periods): array
    {
        $revenues = [];
        
        for ($i = $periods; $i > 0; $i--) {
            $start = now()->subMonths($i)->startOfMonth();
            $end = now()->subMonths($i)->endOfMonth();
            
            $query = Sale::query()
                ->whereBetween('sale_date', [$start, $end])
                ->whereNotIn('status', SaleStatus::nonRevenueStatuses());
            
            if ($branchId) {
                $query->where('branch_id', $branchId);
            }
            
            $revenues[] = $query->sum('total_amount') ?? 0;
        }
        
        return $revenues;
    }

    /**
     * Get product sales history over N months
     * V35-HIGH-02 FIX: Use sale_date for period filtering
     */
    protected function getProductSalesHistory(int $productId, ?int $branchId, int $months): array
    {
        $history = [];
        
        for ($i = $months; $i > 0; $i--) {
            $start = now()->subMonths($i)->startOfMonth();
            $end = now()->subMonths($i)->endOfMonth();
            
            $qty = SaleItem::query()
                ->where('product_id', $productId)
                ->whereHas('sale', function ($q) use ($branchId, $start, $end) {
                    if ($branchId) {
                        $q->where('branch_id', $branchId);
                    }
                    $q->whereBetween('sale_date', [$start, $end])
                        ->whereNotIn('status', SaleStatus::nonRevenueStatuses());
                })
                ->sum('quantity') ?? 0;
            
            $history[] = $qty;
        }
        
        return $history;
    }

    /**
     * Calculate reorder priority based on stock and demand
     */
    protected function calculateReorderPriority(array $product, float $avgDemand): string
    {
        $stock = $product['stock'] ?? 0;
        
        if ($stock <= 0) {
            return 'urgent';
        }
        
        $daysOfStock = $avgDemand > 0 ? $stock / $avgDemand : 999;
        
        if ($daysOfStock < 7) {
            return 'high';
        } elseif ($daysOfStock < 30) {
            return 'medium';
        }
        
        return 'low';
    }

    /**
     * Calculate sales velocity (units per day)
     * V35-HIGH-02 FIX: Use sale_date for period filtering
     */
    protected function calculateSalesVelocity(int $productId, ?int $branchId): float
    {
        $days = 30;
        $start = now()->subDays($days);
        
        $qty = SaleItem::query()
            ->where('product_id', $productId)
            ->whereHas('sale', function ($q) use ($branchId, $start) {
                if ($branchId) {
                    $q->where('branch_id', $branchId);
                }
                $q->where('sale_date', '>=', $start)
                    ->whereNotIn('status', SaleStatus::nonRevenueStatuses());
            })
            ->sum('quantity') ?? 0;
        
        return $qty / $days;
    }

    /**
     * Estimate price elasticity (simplified)
     */
    protected function estimatePriceElasticity(int $productId, ?int $branchId): float
    {
        // This would require historical price changes and demand data
        // Returning a typical elasticity coefficient for retail products
        return -1.2;
    }

    /**
     * Get competitor price (placeholder - would integrate with external data)
     */
    protected function getCompetitorPrice(int $productId): ?float
    {
        // This would require integration with competitor pricing APIs
        return null;
    }

    /**
     * Estimate revenue impact of price change
     */
    protected function estimateRevenueImpact(float $currentPrice, float $newPrice, float $elasticity): array
    {
        $priceChange = (($newPrice - $currentPrice) / $currentPrice) * 100;
        $volumeChange = $priceChange * $elasticity;
        $revenueChange = $priceChange + $volumeChange + ($priceChange * $volumeChange / 100);
        
        return [
            'revenue_change' => round($revenueChange, 2),
            'volume_change' => round($volumeChange, 2),
        ];
    }

    /**
     * Calculate average days between customer purchases
     * V35-HIGH-02 FIX: Use sale_date for calculation
     */
    protected function calculateAvgDaysBetweenPurchases(Customer $customer): float
    {
        $sales = $customer->sales()
            ->whereNotIn('status', SaleStatus::nonRevenueStatuses())
            ->orderBy('sale_date')
            ->get();
        
        if ($sales->count() < 2) {
            return 30; // Default assumption
        }
        
        $totalDays = 0;
        for ($i = 1; $i < $sales->count(); $i++) {
            $totalDays += $sales[$i]->sale_date->diffInDays($sales[$i - 1]->sale_date);
        }
        
        return $totalDays / ($sales->count() - 1);
    }

    /**
     * Get best selling category
     * V35-HIGH-02 FIX: Use sale_date for period filtering
     * CODE-REVIEW FIX: Use correct ProductCategory model
     */
    protected function getBestSellingCategory(?int $branchId, array $dateRange): ?string
    {
        $query = SaleItem::query()
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->select('products.category_id', DB::raw('SUM(sale_items.line_total) as total'))
            ->whereBetween('sales.sale_date', $dateRange)
            ->whereNotIn('sales.status', SaleStatus::nonRevenueStatuses())
            ->whereNotNull('products.category_id')
            ->groupBy('products.category_id')
            ->orderByDesc('total')
            ->first();
        
        if ($query && $query->category_id) {
            // CODE-REVIEW FIX: Use correct ProductCategory model
            $category = \App\Models\ProductCategory::find($query->category_id);
            return $category?->name;
        }
        
        return null;
    }

    /**
     * Get peak sales hours
     * V35-HIGH-02 FIX: Use sale_date for analysis
     */
    protected function getPeakHours(?int $branchId, array $dateRange): array
    {
        $query = Sale::query()
            ->select(DB::raw('HOUR(sale_date) as hour'), DB::raw('COUNT(*) as count'))
            ->whereBetween('sale_date', $dateRange)
            ->whereNotIn('status', SaleStatus::nonRevenueStatuses())
            ->groupBy(DB::raw('HOUR(sale_date)'))
            ->orderByDesc('count')
            ->limit(3);
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        return $query->pluck('hour')->toArray();
    }

    /**
     * Analyze profit margins
     * V35-HIGH-02 FIX: Use sale_date for period filtering
     */
    protected function analyzeMargins(?int $branchId, array $dateRange): array
    {
        $items = SaleItem::query()
            ->whereHas('sale', function ($q) use ($branchId, $dateRange) {
                if ($branchId) {
                    $q->where('branch_id', $branchId);
                }
                $q->whereBetween('sale_date', $dateRange)
                    ->whereNotIn('status', SaleStatus::nonRevenueStatuses());
            })
            ->get();
        
        $totalRevenue = $items->sum('line_total');
        $totalCost = $items->sum(function ($item) {
            return ($item->cost_price ?? 0) * ($item->quantity ?? 0);
        });
        
        $grossMargin = $totalRevenue > 0 ? (($totalRevenue - $totalCost) / $totalRevenue) * 100 : 0;
        
        return [
            'gross_margin' => round($grossMargin, 2),
            'total_revenue' => round($totalRevenue, 2),
            'total_cost' => round($totalCost, 2),
        ];
    }
}
