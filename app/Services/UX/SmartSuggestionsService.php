<?php

declare(strict_types=1);

namespace App\Services\UX;

use App\Enums\SaleStatus;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * SmartSuggestionsService - AI-Powered Smart Suggestions for UX Enhancement
 *
 * PURPOSE: Provide intelligent suggestions and recommendations to improve user experience
 * FEATURES:
 *   - Smart reorder quantity suggestions based on sales velocity
 *   - Dynamic pricing recommendations based on margins and competition
 *   - Product bundling suggestions based on purchase patterns
 *   - Customer upselling recommendations
 *   - Automated discount suggestions
 */
class SmartSuggestionsService
{
    /**
     * Suggest optimal reorder quantity for a product.
     * Uses sales velocity, lead time, and safety stock calculations.
     *
     * @param  Product  $product  Product to analyze
     * @param  int  $daysToAnalyze  Days of sales history to consider
     * @return array Reorder suggestion with details
     */
    public function suggestReorderQuantity(Product $product, int $daysToAnalyze = 30): array
    {
        // Get sales velocity (average daily sales)
        $salesVelocity = $this->calculateSalesVelocity($product->id, $daysToAnalyze);

        // Get current stock level
        $currentStock = $this->getCurrentStock($product->id);

        // Get lead time (days to receive stock after ordering)
        $leadTimeDays = $product->lead_time_days ?? 7;

        // Calculate reorder point (lead time demand + safety stock)
        $leadTimeDemand = bcmul((string) $salesVelocity, (string) $leadTimeDays, 2);
        $safetyStock = bcmul($leadTimeDemand, '0.25', 2); // 25% safety buffer
        $reorderPoint = bcadd($leadTimeDemand, $safetyStock, 2);

        // Calculate Economic Order Quantity (EOQ)
        // EOQ = sqrt((2 * annual_demand * order_cost) / holding_cost)
        $annualDemand = bcmul((string) $salesVelocity, '365', 2);
        $orderCost = '100'; // Assume $100 per order (configurable)
        $holdingCost = bcmul((string) ($product->standard_cost ?? 0), '0.25', 2); // 25% of cost per year

        $eoqNumerator = bcmul(bcmul('2', $annualDemand, 2), $orderCost, 2);
        // V45-HIGH-01 FIX: Apply sqrt() to the EOQ formula as per the standard formula
        $eoq = bccomp($holdingCost, '0', 2) > 0
            ? (string) sqrt((float) bcdiv($eoqNumerator, $holdingCost, 4))
            : $annualDemand;

        // Calculate suggested order quantity
        $suggestedQty = max(decimal_float($eoq), decimal_float($product->minimum_order_quantity ?? 1));

        // Calculate days of stock coverage
        $daysOfStock = bccomp((string) $salesVelocity, '0', 2) > 0
            ? bcdiv((string) $currentStock, (string) $salesVelocity, 1)
            : '999';

        // Determine urgency level
        $urgency = $this->determineReorderUrgency(decimal_float($currentStock), decimal_float($reorderPoint), decimal_float($product->min_stock ?? 0));

        return [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'current_stock' => $currentStock,
            'min_stock' => $product->min_stock ?? 0,
            'reorder_point' => decimal_float($reorderPoint),
            'suggested_quantity' => (int) $suggestedQty,
            'sales_velocity' => decimal_float($salesVelocity),
            'days_of_stock_remaining' => decimal_float($daysOfStock),
            'lead_time_days' => $leadTimeDays,
            'urgency' => $urgency,
            'estimated_cost' => bcmul((string) $suggestedQty, (string) ($product->standard_cost ?? 0), 2),
            'recommendation' => $this->generateReorderRecommendation($urgency, decimal_float($daysOfStock), $suggestedQty),
        ];
    }

    /**
     * Suggest optimal pricing for a product based on margin targets.
     * Analyzes cost, competition, and desired margins.
     *
     * @param  Product  $product  Product to price
     * @param  float|null  $targetMarginPercent  Desired margin percentage (null = auto-calculate)
     * @return array Pricing suggestions
     */
    public function suggestOptimalPricing(Product $product, ?float $targetMarginPercent = null): array
    {
        $cost = decimal_float($product->standard_cost ?? 0);

        if (bccomp((string) $cost, '0', 2) <= 0) {
            return [
                'error' => 'Product cost must be greater than zero',
                'product_id' => $product->id,
            ];
        }

        // If no target margin specified, use intelligent default
        if ($targetMarginPercent === null) {
            $targetMarginPercent = $this->calculateRecommendedMargin($product);
        }

        // Calculate suggested selling price using bcmath
        $marginMultiplier = bcdiv(bcadd('100', (string) $targetMarginPercent, 2), '100', 4);
        $suggestedPrice = bcmul((string) $cost, $marginMultiplier, 2);

        // Calculate price points at different margins
        $pricePoints = [];
        foreach ([10, 20, 30, 40, 50] as $margin) {
            $multiplier = bcdiv(bcadd('100', (string) $margin, 2), '100', 4);
            $price = bcmul((string) $cost, $multiplier, 2);
            $pricePoints["margin_{$margin}"] = [
                'price' => decimal_float($price),
                'margin_percent' => $margin,
                'profit_per_unit' => decimal_float(bcsub($price, (string) $cost, 2)),
            ];
        }

        // Get current pricing status
        $currentPrice = decimal_float($product->default_price ?? 0);
        $currentMargin = bccomp((string) $currentPrice, '0', 2) > 0
            ? bcmul(bcdiv(bcsub((string) $currentPrice, (string) $cost, 2), (string) $currentPrice, 4), '100', 2)
            : '0';

        // Compare with market/competitors (placeholder - would integrate with actual data)
        $marketAnalysis = $this->analyzeMarketPricing($product);

        return [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'cost' => $cost,
            'current_price' => $currentPrice,
            'current_margin' => decimal_float($currentMargin).'%',
            'suggested_price' => decimal_float($suggestedPrice),
            'target_margin' => $targetMarginPercent.'%',
            'profit_per_unit' => decimal_float(bcsub($suggestedPrice, (string) $cost, 2)),
            'price_points' => $pricePoints,
            'market_analysis' => $marketAnalysis,
            'recommendation' => $this->generatePricingRecommendation(decimal_float($suggestedPrice), $currentPrice, decimal_float($currentMargin)),
        ];
    }

    /**
     * Suggest product bundles based on purchase patterns.
     * Analyzes products frequently bought together.
     *
     * @param  int  $productId  Product to find bundles for
     * @param  int  $limit  Maximum number of bundle suggestions
     * @return Collection Bundle suggestions
     */
    public function suggestProductBundles(int $productId, int $limit = 5): Collection
    {
        // Find products frequently purchased together
        // V44-HIGH-02 FIX: Exclude soft-deleted sale_items and products
        $frequentlyBoughtTogether = DB::table('sale_items as si1')
            ->join('sale_items as si2', 'si1.sale_id', '=', 'si2.sale_id')
            ->join('products', 'si2.product_id', '=', 'products.id')
            ->where('si1.product_id', $productId)
            ->where('si2.product_id', '!=', $productId)
            ->whereNull('si1.deleted_at')
            ->whereNull('si2.deleted_at')
            ->whereNull('products.deleted_at')
            ->select(
                'si2.product_id',
                'products.name',
                'products.default_price',
                DB::raw('COUNT(*) as frequency'),
                DB::raw('AVG(si2.quantity) as avg_quantity')
            )
            ->groupBy('si2.product_id', 'products.name', 'products.default_price')
            ->orderByDesc('frequency')
            ->limit($limit)
            ->get();

        // Calculate bundle discounts and savings
        return $frequentlyBoughtTogether->map(function ($item) use ($productId) {
            $baseProduct = Product::find($productId);
            $bundledProduct = Product::find($item->product_id);

            // NEW-MEDIUM-03 FIX: Skip null products to prevent crash when products are deleted/inactive
            if (! $baseProduct || ! $bundledProduct) {
                return null;
            }

            $totalPrice = bcadd((string) ($baseProduct->default_price ?? 0), (string) ($bundledProduct->default_price ?? 0), 2);
            $suggestedBundlePrice = bcmul($totalPrice, '0.90', 2); // 10% bundle discount
            $savings = bcsub($totalPrice, $suggestedBundlePrice, 2);

            return [
                'bundle_with' => [
                    'product_id' => $item->product_id,
                    'product_name' => $item->name,
                    'price' => decimal_float($item->default_price),
                ],
                'frequency' => $item->frequency,
                'avg_quantity' => decimal_float($item->avg_quantity),
                'individual_total' => decimal_float($totalPrice),
                'suggested_bundle_price' => decimal_float($suggestedBundlePrice),
                'customer_savings' => decimal_float($savings),
                'discount_percent' => '10%',
            ];
        })->filter();
    }

    /**
     * Suggest upsell opportunities for a customer.
     * Recommends higher-value or complementary products.
     *
     * @param  Customer  $customer  Customer to analyze
     * @param  int  $limit  Number of suggestions
     * @return Collection Upsell suggestions
     */
    public function suggestUpsellOpportunities(Customer $customer, int $limit = 5): Collection
    {
        // Get customer's purchase history
        // V44-HIGH-02 FIX: Exclude soft-deleted sale_items and sales
        $purchasedProducts = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.customer_id', $customer->id)
            ->whereNull('sale_items.deleted_at')
            ->whereNull('sales.deleted_at')
            ->pluck('sale_items.product_id')
            ->unique();

        if ($purchasedProducts->isEmpty()) {
            return collect([]);
        }

        // Find premium alternatives and complementary products
        $suggestions = Product::whereNotIn('id', $purchasedProducts)
            ->where('status', 'active')
            ->whereNotNull('default_price')
            ->select('id', 'name', 'default_price', 'standard_cost', 'category_id')
            ->limit($limit * 3) // Get more to filter
            ->get()
            ->map(function ($product) {
                $margin = bccomp((string) $product->default_price, '0', 2) > 0
                    ? bcmul(bcdiv(bcsub((string) $product->default_price, (string) $product->standard_cost, 2), (string) $product->default_price, 4), '100', 2)
                    : '0';

                return [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'price' => decimal_float($product->default_price),
                    'margin' => decimal_float($margin),
                    'category_id' => $product->category_id,
                    'upsell_potential' => $this->calculateUpsellPotential($product, $customer),
                ];
            })
            ->sortByDesc('upsell_potential')
            ->take($limit);

        return $suggestions;
    }

    /**
     * Calculate sales velocity for a product.
     * Returns average units sold per day.
     *
     * @param  int  $productId  Product ID
     * @param  int  $days  Days to analyze
     * @return float Average daily sales using bcmath
     *               V35-HIGH-02 FIX: Use sale_date instead of created_at
     *               V35-MED-06 FIX: Exclude soft-deleted sales and non-revenue statuses
     *               V46-HIGH-02 FIX: Exclude soft-deleted sale_items
     */
    protected function calculateSalesVelocity(int $productId, int $days): float
    {
        $totalSold = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sale_items.product_id', $productId)
            ->whereNull('sales.deleted_at')
            ->whereNull('sale_items.deleted_at')
            ->whereNotIn('sales.status', SaleStatus::nonRevenueStatuses())
            ->where('sales.sale_date', '>=', now()->subDays($days))
            ->sum('sale_items.quantity');

        return decimal_float(bcdiv((string) ($totalSold ?? 0), (string) $days, 2));
    }

    /**
     * Get current stock level for a product.
     *
     * @param  int  $productId  Product ID
     * @return float Current stock quantity
     */
    protected function getCurrentStock(int $productId): float
    {
        // quantity is signed: positive = in, negative = out
        // V44-HIGH-03 FIX: Exclude soft-deleted stock_movements
        $totalStock = DB::table('stock_movements')
            ->where('product_id', $productId)
            ->whereNull('deleted_at')
            ->sum('quantity');

        return decimal_float($totalStock ?? 0);
    }

    /**
     * Determine reorder urgency level.
     *
     * @param  float  $currentStock  Current stock level
     * @param  float  $reorderPoint  Calculated reorder point
     * @param  float  $minStock  Minimum stock level
     * @return string Urgency level (CRITICAL/HIGH/MEDIUM/LOW)
     */
    protected function determineReorderUrgency(float $currentStock, float $reorderPoint, float $minStock): string
    {
        if ($currentStock <= 0) {
            return 'CRITICAL';
        }

        if ($currentStock <= $minStock) {
            return 'HIGH';
        }

        if ($currentStock <= $reorderPoint) {
            return 'MEDIUM';
        }

        return 'LOW';
    }

    /**
     * Generate reorder recommendation text.
     *
     * @param  string  $urgency  Urgency level
     * @param  float  $daysOfStock  Days of stock remaining
     * @param  int  $suggestedQty  Suggested order quantity
     * @return string Recommendation message
     */
    protected function generateReorderRecommendation(string $urgency, float $daysOfStock, int $suggestedQty): string
    {
        return match ($urgency) {
            'CRITICAL' => "⚠️ OUT OF STOCK - Order {$suggestedQty} units immediately!",
            'HIGH' => "🔴 LOW STOCK - Order {$suggestedQty} units within 24 hours. Only {$daysOfStock} days remaining.",
            'MEDIUM' => "🟡 REORDER SOON - Order {$suggestedQty} units within a week. {$daysOfStock} days of stock left.",
            'LOW' => "🟢 STOCK OK - Consider ordering {$suggestedQty} units in advance. {$daysOfStock} days remaining.",
            default => "Order {$suggestedQty} units as needed.",
        };
    }

    /**
     * Calculate recommended margin based on product characteristics.
     *
     * @param  Product  $product  Product to analyze
     * @return float Recommended margin percentage
     */
    protected function calculateRecommendedMargin(Product $product): float
    {
        // Default margin based on product type
        $baseMargin = 30.0;

        // Adjust based on product characteristics
        // Higher margin for premium/specialty items
        // Lower margin for commodity items

        return $baseMargin;
    }

    /**
     * Analyze market pricing (placeholder for future implementation).
     *
     * @param  Product  $product  Product to analyze
     * @return array Market analysis results
     */
    protected function analyzeMarketPricing(Product $product): array
    {
        return [
            'market_average' => null,
            'competitive_position' => 'unknown',
            'note' => 'Market analysis requires external data integration',
        ];
    }

    /**
     * Generate pricing recommendation text.
     *
     * @param  float  $suggestedPrice  Suggested selling price
     * @param  float  $currentPrice  Current selling price
     * @param  float  $currentMargin  Current margin percentage
     * @return string Recommendation message
     */
    protected function generatePricingRecommendation(float $suggestedPrice, float $currentPrice, float $currentMargin): string
    {
        if ($currentPrice <= 0) {
            return 'Set initial price at '.number_format($suggestedPrice, 2).' for healthy margins.';
        }

        $priceDiff = $suggestedPrice - $currentPrice;
        $diffPercent = abs($priceDiff / $currentPrice * 100);

        if ($diffPercent < 5) {
            return '✅ Current pricing is optimal. No changes needed.';
        }

        if ($priceDiff > 0) {
            return '📈 Consider increasing price by '.number_format($diffPercent, 1).'% to improve margins.';
        }

        return '📉 Consider decreasing price by '.number_format($diffPercent, 1).'% to stay competitive.';
    }

    /**
     * Calculate upsell potential score.
     *
     * @param  Product  $product  Product being evaluated
     * @param  Customer  $customer  Customer to upsell to
     * @return float Upsell potential score (0-100)
     */
    protected function calculateUpsellPotential(Product $product, Customer $customer): float
    {
        // Simple scoring algorithm (can be enhanced with ML)
        $score = 50.0; // Base score

        // Higher score for products with good margins
        $margin = bccomp((string) $product->default_price, '0', 2) > 0
            ? decimal_float(bcmul(bcdiv(bcsub((string) $product->default_price, (string) $product->standard_cost, 2), (string) $product->default_price, 4), '100', 2))
            : 0;

        if ($margin > 30) {
            $score += 20;
        } elseif ($margin > 20) {
            $score += 10;
        }

        return min($score, 100.0);
    }
}
