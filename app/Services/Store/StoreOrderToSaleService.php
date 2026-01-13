<?php

declare(strict_types=1);

namespace App\Services\Store;

use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StoreOrder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class StoreOrderToSaleService
{
    public function convert(StoreOrder $order): ?Sale
    {
        try {
            if (method_exists($order, 'sale') && $order->sale) {
                return $order->sale;
            }

            if (! class_exists(Sale::class)) {
                return null;
            }

            $saleModel = new Sale;

            $fillable = method_exists($saleModel, 'getFillable')
                ? $saleModel->getFillable()
                : [];

            $data = [];

            foreach ($fillable as $field) {
                switch ($field) {
                    case 'branch_id':
                        $data[$field] = $order->branch_id;
                        break;
                    case 'currency':
                        $data[$field] = $order->currency;
                        break;
                        // FIX N-05: Add mappings for current Sale schema column names
                    case 'total_amount':
                        $data[$field] = $order->total ?? $order->total_amount ?? 0;
                        break;
                    case 'discount_amount':
                        $data[$field] = $order->discount_total ?? $order->discount_amount ?? 0;
                        break;
                    case 'shipping_amount':
                        $data[$field] = $order->shipping_total ?? $order->shipping_amount ?? 0;
                        break;
                    case 'tax_amount':
                        $data[$field] = $order->tax_total ?? $order->tax_amount ?? 0;
                        break;
                    case 'subtotal':
                        $data[$field] = $order->subtotal ?? (($order->total ?? 0) - ($order->tax_total ?? 0) - ($order->shipping_total ?? 0) + ($order->discount_total ?? 0));
                        break;
                        // Legacy field names for backward compatibility
                    case 'total':
                        $data[$field] = $order->total;
                        break;
                    case 'discount_total':
                        $data[$field] = $order->discount_total;
                        break;
                    case 'shipping_total':
                        $data[$field] = $order->shipping_total;
                        break;
                    case 'tax_total':
                        $data[$field] = $order->tax_total;
                        break;
                    case 'status':
                        $data[$field] = $data[$field] ?? 'completed';
                        break;
                    case 'source_type':
                        $data[$field] = 'store_order';
                        break;
                    case 'source_id':
                        $data[$field] = $order->getKey();
                        break;
                    case 'store_order_id':
                        $data[$field] = $order->getKey();
                        break;
                }
            }

            if (in_array('status', $fillable, true) && ! isset($data['status'])) {
                $data['status'] = 'completed';
            }

            /** @var Sale $sale */
            $sale = $saleModel->newQuery()->create($data);

            try {
                if (class_exists(SaleItem::class)) {
                    $this->syncItems($order, $sale);
                }
            } catch (\Throwable $e) {
                Log::warning('StoreOrderToSaleService: items sync failed', [
                    'order_id' => $order->getKey(),
                    'message' => $e->getMessage(),
                ]);
            }

            try {
                $order->update(['status' => 'processed']);
            } catch (\Throwable $e) {
                // ignore
            }

            return $sale;
        } catch (\Throwable $e) {
            Log::error('StoreOrderToSaleService: convert failed', [
                'order_id' => $order->getKey(),
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function syncItems(StoreOrder $order, Sale $sale): void
    {
        $payload = $order->payload;

        if (! is_array($payload) || ! isset($payload['items']) || ! is_array($payload['items'])) {
            return;
        }

        $saleItemModel = new SaleItem;

        $fillable = method_exists($saleItemModel, 'getFillable')
            ? $saleItemModel->getFillable()
            : [];

        if (method_exists($sale, 'items')) {
            try {
                $sale->items()->delete();
            } catch (\Throwable $e) {
                // ignore
            }
        }

        foreach ($payload['items'] as $item) {
            if (! is_array($item)) {
                continue;
            }

            $qty = (float) Arr::get($item, 'qty', 0);

            if ($qty <= 0) {
                continue;
            }

            $price = (float) Arr::get($item, 'price', 0);
            $discount = (float) Arr::get($item, 'discount', 0);
            $total = Arr::get($item, 'total');

            if ($total === null) {
                $total = ($qty * $price) - $discount;
            }

            $productId = null;
            $variationId = null;

            try {
                if (! empty($item['variation_id'])) {
                    $variation = ProductVariation::query()->find((int) $item['variation_id']);
                    if ($variation) {
                        $variationId = $variation->getKey();
                        $productId = $variation->product_id;
                    }
                } elseif (! empty($item['variation_sku'])) {
                    $variation = ProductVariation::query()->where('sku', $item['variation_sku'])->first();
                    if ($variation) {
                        $variationId = $variation->getKey();
                        $productId = $variation->product_id;
                    }
                } elseif (! empty($item['sku'])) {
                    $product = Product::query()->where('sku', $item['sku'])->first();
                    if ($product) {
                        $productId = $product->getKey();
                    }
                }
            } catch (\Throwable $e) {
            }

            $data = [];

            foreach ($fillable as $field) {
                switch ($field) {
                    case 'sale_id':
                        $data[$field] = $sale->getKey();
                        break;
                    case 'product_id':
                        $data[$field] = $productId;
                        break;
                    case 'product_variation_id':
                    case 'variation_id':
                        $data[$field] = $variationId;
                        break;
                    case 'quantity':
                    case 'qty':
                    case 'qty_total':
                        $data[$field] = $qty;
                        break;
                    case 'unit_price':
                    case 'price':
                        $data[$field] = $price;
                        break;
                    case 'discount':
                    case 'discount_total':
                        $data[$field] = $discount;
                        break;
                    case 'total':
                    case 'line_total':
                    case 'subtotal':
                        $data[$field] = $total;
                        break;
                }
            }

            $saleItemModel->newQuery()->create($data);
        }
    }
}
