<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\ProductFieldValue;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Services\Contracts\ModuleFieldServiceInterface;
use App\Services\Contracts\ProductServiceInterface;
use App\Traits\HandlesServiceErrors;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductService implements ProductServiceInterface
{
    use HandlesServiceErrors;

    public function __construct(
        protected ProductRepositoryInterface $productRepository,
        protected ModuleFieldServiceInterface $moduleFields,
    ) {}

    /** @return \Illuminate\Contracts\Pagination\LengthAwarePaginator */
    public function search(int $branchId, string $q = '', int $perPage = 15)
    {
        return $this->handleServiceOperation(
            callback: fn () => $this->productRepository->search($branchId, $q, $perPage),
            operation: 'search',
            context: ['branch_id' => $branchId, 'query' => $q, 'per_page' => $perPage]
        );
    }

    public function importCsv(int $branchId, string $disk, string $path): int
    {
        return $this->handleServiceOperation(
            callback: function () use ($branchId, $disk, $path) {
                if (! Storage::disk($disk)->exists($path)) {
                    return 0;
                }

                $stream = Storage::disk($disk)->readStream($path);
                if (! $stream) {
                    return 0;
                }

                $header = fgetcsv($stream);
                if (! $header) {
                    fclose($stream);

                    return 0;
                }

                $normalized = [];
                foreach ($header as $index => $column) {
                    $key = strtolower(trim((string) $column));
                    if ($key === '') {
                        continue;
                    }
                    $normalized[$key] = $index;
                }

                $baseColumns = ['sku', 'name', 'price', 'cost', 'barcode'];

                $exportableDynamic = $this->moduleFields->exportColumns('inventory', 'products', null);
                $exportableDynamicLower = array_map('strtolower', $exportableDynamic);

                $dynamicColumns = [];
                foreach ($normalized as $key => $index) {
                    if (in_array($key, $baseColumns, true)) {
                        continue;
                    }
                    if (in_array($key, $exportableDynamicLower, true)) {
                        $dynamicColumns[$key] = $index;
                    }
                }

                $imported = 0;

                DB::beginTransaction();

                try {
                    while (($row = fgetcsv($stream)) !== false) {
                        if (! array_filter($row, fn ($value) => $value !== null && $value !== '')) {
                            continue;
                        }

                        $sku = $this->valueOrNull($row, $normalized, 'sku');
                        $name = $this->valueOrNull($row, $normalized, 'name');

                        if ($sku === null && $name === null) {
                            continue;
                        }

                        /** @var Product $product */
                        $product = $sku
                            ? $this->productRepository->findBySku($sku, $branchId) ?? new Product
                            : new Product;

                        $product->branch_id = $branchId;

                        if ($sku !== null) {
                            $product->sku = $sku;
                        }

                        if ($name !== null) {
                            $product->name = $name;
                        }

                        $price = $this->valueOrNull($row, $normalized, 'price');
                        if ($price !== null) {
                            $product->default_price = decimal_float($price);
                        }

                        $cost = $this->valueOrNull($row, $normalized, 'cost');
                        if ($cost !== null) {
                            $product->cost = decimal_float($cost);
                        }

                        $barcode = $this->valueOrNull($row, $normalized, 'barcode');
                        if ($barcode !== null) {
                            $product->barcode = $barcode;
                        }

                        $attrs = (array) ($product->extra_attributes ?? []);
                        foreach ($dynamicColumns as $key => $index) {
                            $attrs[$key] = $row[$index] ?? null;
                        }
                        $product->extra_attributes = $attrs;

                        $product->save();
                        $imported++;
                    }

                    fclose($stream);
                    DB::commit();

                    $this->logServiceInfo('importCsv', 'CSV import completed', [
                        'disk' => $disk,
                        'path' => $path,
                        'imported_count' => $imported,
                    ]);
                } catch (\Throwable $e) {
                    fclose($stream);
                    DB::rollBack();
                    throw $e;
                }

                return $imported;
            },
            operation: 'importCsv',
            context: ['branch_id' => $branchId, 'disk' => $disk, 'path' => $path],
            defaultValue: 0
        );
    }

    public function exportCsv(string $disk, string $path): string
    {
        return $this->handleServiceOperation(
            callback: function () use ($disk, $path) {
                $dynamicKeys = $this->moduleFields->exportColumns('inventory', 'products', null);
                $dynamicKeys = array_values(array_unique($dynamicKeys));

                $fh = fopen('php://temp', 'w+');

                $header = ['sku', 'name', 'price', 'cost', 'barcode'];
                foreach ($dynamicKeys as $key) {
                    $header[] = $key;
                }
                fputcsv($fh, $header);

                $this->productRepository->getAllChunked(500, function ($chunk) use ($fh, $dynamicKeys) {
                    foreach ($chunk as $p) {
                        $row = [
                            $p->sku,
                            $p->name,
                            $p->default_price,
                            $p->cost,
                            $p->barcode,
                        ];

                        $attrs = (array) ($p->extra_attributes ?? []);
                        foreach ($dynamicKeys as $key) {
                            $row[] = $attrs[$key] ?? null;
                        }

                        fputcsv($fh, $row);
                    }
                });

                rewind($fh);
                $content = stream_get_contents($fh);
                fclose($fh);

                Storage::disk($disk)->put($path, $content);

                $this->logServiceInfo('exportCsv', 'CSV export completed', [
                    'disk' => $disk,
                    'path' => $path,
                ]);

                return $path;
            },
            operation: 'exportCsv',
            context: ['disk' => $disk, 'path' => $path]
        );
    }

    /**
     * Create a product for a specific module with module-aware validation and fields
     *
     * @param  Module  $module  The module to create the product for
     * @param  array  $data  Product data including basic fields and custom fields
     * @param  \Illuminate\Http\UploadedFile|null  $thumbnail  Optional thumbnail file
     *
     * @throws \Exception If module doesn't support items
     */
    public function createProductForModule(\App\Models\Module $module, array $data, ?\Illuminate\Http\UploadedFile $thumbnail = null): Product
    {
        return $this->handleServiceOperation(
            callback: function () use ($module, $data, $thumbnail) {
                // Verify module supports items
                if (! $module->supportsItems()) {
                    throw new \Exception("Module {$module->key} does not support items/products");
                }

                DB::beginTransaction();

                try {
                    // Extract custom fields from data
                    $customFields = $data['custom_fields'] ?? [];
                    unset($data['custom_fields']);

                    // Prepare product data
                    $productData = [
                        'name' => $data['name'],
                        'sku' => $data['sku'] ?? null,
                        'barcode' => $data['barcode'] ?? null,
                        'default_price' => $data['price'] ?? 0,
                        'standard_cost' => $data['cost'] ?? 0,
                        'price_currency' => $data['price_currency'] ?? 'EGP',
                        'cost_currency' => $data['cost_currency'] ?? 'EGP',
                        'status' => $data['status'] ?? 'active',
                        'type' => $module->is_service ? 'service' : ($data['type'] ?? 'stock'),
                        'branch_id' => $data['branch_id'],
                        'module_id' => $module->id,
                        'category_id' => $data['category_id'] ?? null,
                        'unit_id' => $data['unit_id'] ?? null,
                        // V33-CRIT-02 FIX: Use actual_user_id() for correct audit attribution during impersonation
                        'created_by' => actual_user_id(),
                    ];

                    // Handle thumbnail upload if provided
                    if ($thumbnail) {
                        $productData['thumbnail'] = $thumbnail->store('products/thumbnails', 'public');
                    }

                    // Create product through repository
                    $product = $this->productRepository->create($productData);

                    // Save custom fields if module supports them
                    if ($module->supports_custom_fields && ! empty($customFields)) {
                        $this->saveCustomFields($product, $module, $customFields);
                    }

                    DB::commit();

                    $this->logServiceInfo('createProductForModule', 'Product created for module', [
                        'product_id' => $product->id,
                        'module_key' => $module->key,
                    ]);

                    return $product;
                } catch (\Exception $e) {
                    DB::rollBack();
                    throw $e;
                }
            },
            operation: 'createProductForModule',
            context: ['module_key' => $module->key, 'data' => $data]
        );
    }

    /**
     * Update a product with module-aware validation
     *
     * @param  Product  $product  The product to update
     * @param  array  $data  Updated product data
     * @param  \Illuminate\Http\UploadedFile|null  $thumbnail  Optional new thumbnail file
     */
    public function updateProductForModule(Product $product, array $data, ?\Illuminate\Http\UploadedFile $thumbnail = null): Product
    {
        return $this->handleServiceOperation(
            callback: function () use ($product, $data, $thumbnail) {
                DB::beginTransaction();

                try {
                    // Extract custom fields from data
                    $customFields = $data['custom_fields'] ?? [];
                    unset($data['custom_fields']);

                    // Prepare update data
                    $updateData = [
                        'name' => $data['name'] ?? $product->name,
                        'sku' => $data['sku'] ?? $product->sku,
                        'barcode' => $data['barcode'] ?? $product->barcode,
                        'default_price' => $data['price'] ?? $product->default_price,
                        'standard_cost' => $data['cost'] ?? $product->standard_cost,
                        'price_currency' => $data['price_currency'] ?? $product->price_currency,
                        'cost_currency' => $data['cost_currency'] ?? $product->cost_currency,
                        'status' => $data['status'] ?? $product->status,
                        'type' => $data['type'] ?? $product->type,
                        'category_id' => $data['category_id'] ?? $product->category_id,
                        'unit_id' => $data['unit_id'] ?? $product->unit_id,
                        // V33-CRIT-02 FIX: Use actual_user_id() for correct audit attribution during impersonation
                        'updated_by' => actual_user_id(),
                    ];

                    // Handle thumbnail update
                    if ($thumbnail) {
                        // Delete old thumbnail if exists
                        if ($product->thumbnail) {
                            Storage::disk('public')->delete($product->thumbnail);
                        }
                        $updateData['thumbnail'] = $thumbnail->store('products/thumbnails', 'public');
                    }

                    // Update product through repository
                    $product = $this->productRepository->update($product, $updateData);

                    // Update custom fields if module supports them
                    if ($product->module && $product->module->supports_custom_fields && ! empty($customFields)) {
                        $this->saveCustomFields($product, $product->module, $customFields);
                    }

                    DB::commit();

                    return $product;
                } catch (\Exception $e) {
                    DB::rollBack();
                    throw $e;
                }
            },
            operation: 'updateProductForModule',
            context: ['product_id' => $product->id, 'data' => $data]
        );
    }

    /**
     * Save custom fields for a product
     */
    private function saveCustomFields(Product $product, \App\Models\Module $module, array $customFields): void
    {
        // Delete existing field values
        ProductFieldValue::where('product_id', $product->id)->delete();

        // Get module fields
        $fields = $this->moduleFields->getModuleFields($module->id, true);

        foreach ($fields as $field) {
            $value = $customFields[$field->field_key] ?? null;

            if ($value !== null && $value !== '') {
                ProductFieldValue::create([
                    'product_id' => $product->id,
                    'module_product_field_id' => $field->id,
                    'value' => is_array($value) ? json_encode($value) : (string) $value,
                ]);
            }
        }
    }

    private function valueOrNull(array $row, array $normalized, string $key): ?string
    {
        if (! isset($normalized[$key])) {
            return null;
        }

        $value = $row[$normalized[$key]] ?? null;

        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
