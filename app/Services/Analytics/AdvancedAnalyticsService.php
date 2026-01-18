<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Support\Facades\Cache;

/**
 * Advanced Analytics Service with AI-powered predictions
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
                    ->whereNotIn('status', ['draft', 'cancelled', 'void', 'refunded'])
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

    // Placeholder methods to be implemented with actual business logic
    protected function getPreviousPeriodSales(?int $branchId, array $dateRange): array
    {
        return ['total' => 0];
    }

    protected function groupByDay($sales): array
    {
        return [];
    }

    protected function groupByHour($sales): array
    {
        return [];
    }

    protected function getTopPerformingDays($sales): array
    {
        return [];
    }

    protected function getTopProducts(?int $branchId, array $dateRange, int $limit): array
    {
        return [];
    }

    protected function getSlowMovingProducts(?int $branchId, array $dateRange, int $limit): array
    {
        return [];
    }

    protected function getStockAlerts(?int $branchId): array
    {
        return [];
    }

    protected function calculateInventoryValue(?int $branchId): float
    {
        return 0;
    }

    protected function calculateInventoryTurnover(?int $branchId, array $dateRange): float
    {
        return 0;
    }

    protected function getNewCustomers(?int $branchId, array $dateRange): int
    {
        return 0;
    }

    protected function calculateReturningCustomerRate(?int $branchId, array $dateRange): float
    {
        return 0;
    }

    protected function calculateCustomerLifetimeValue(?int $branchId): float
    {
        return 0;
    }

    protected function customerSegmentation(?int $branchId, array $dateRange): array
    {
        return [];
    }

    protected function getSalesTimeSeries(?int $branchId, array $dateRange): array
    {
        return [];
    }

    protected function detectTrend(array $data): string
    {
        return 'stable';
    }

    protected function detectSeasonality(array $data): bool
    {
        return false;
    }

    protected function calculateVolatility(array $data): float
    {
        return 0;
    }

    protected function calculateForecastAccuracy(?int $branchId): float
    {
        return 0;
    }

    protected function getHistoricalRevenue(?int $branchId, int $periods): array
    {
        return [];
    }

    protected function getProductSalesHistory(int $productId, ?int $branchId, int $months): array
    {
        return [];
    }

    protected function calculateReorderPriority(array $product, float $avgDemand): string
    {
        return 'medium';
    }

    protected function calculateSalesVelocity(int $productId, ?int $branchId): float
    {
        return 0;
    }

    protected function estimatePriceElasticity(int $productId, ?int $branchId): float
    {
        return -1.0;
    }

    protected function getCompetitorPrice(int $productId): ?float
    {
        return null;
    }

    protected function estimateRevenueImpact(float $currentPrice, float $newPrice, float $elasticity): array
    {
        return ['revenue_change' => 0, 'volume_change' => 0];
    }

    protected function calculateAvgDaysBetweenPurchases(Customer $customer): float
    {
        return 30;
    }

    protected function getBestSellingCategory(?int $branchId, array $dateRange): ?string
    {
        return null;
    }

    protected function getPeakHours(?int $branchId, array $dateRange): array
    {
        return [];
    }

    protected function analyzeMargins(?int $branchId, array $dateRange): array
    {
        return [];
    }
}
