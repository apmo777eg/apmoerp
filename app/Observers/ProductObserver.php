<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Product;
use Illuminate\Support\Str;

class ProductObserver
{
    public function creating(Product $product): void
    {
        if (! $product->getAttribute('sku')) {
            $product->sku = strtoupper(Str::random(8));
        }
        if (! $product->getAttribute('barcode')) {
            $product->barcode = 'P'.strtoupper(Str::random(11));
        }
        if ($product->getAttribute('name')) {
            $product->name = trim((string) $product->name);
        }
        if ($product->getAttribute('default_price') !== null) {
            // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
            $product->default_price = round(decimal_float($product->default_price), 2);
        }
        if ($product->getAttribute('standard_cost') !== null) {
            $product->standard_cost = round(decimal_float($product->standard_cost), 2);
        }
        if ($product->getAttribute('cost') !== null) {
            $product->cost = round(decimal_float($product->cost), 2);
        }
    }

    public function created(Product $product): void
    {
        $this->audit('created', $product);
    }

    /**
     * V23-MED-04 FIX: Move rounding logic from updated() to updating()
     * so changes are persisted to database (updating fires BEFORE save)
     */
    public function updating(Product $product): void
    {
        // Normalize numeric fields BEFORE save
        if ($product->isDirty('default_price') && $product->default_price !== null) {
            $product->default_price = round(decimal_float($product->default_price), 2);
        }
        if ($product->isDirty('standard_cost') && $product->standard_cost !== null) {
            $product->standard_cost = round(decimal_float($product->standard_cost), 2);
        }
        if ($product->isDirty('cost') && $product->cost !== null) {
            $product->cost = round(decimal_float($product->cost), 2);
        }
    }

    public function updated(Product $product): void
    {
        $changes = $product->getChanges();
        $this->audit('updated', $product, $changes);
    }

    public function deleted(Product $product): void
    {
        // NEW-HIGH-05 FIX: Do NOT delete media files on soft delete
        // Media deletion is handled in forceDeleted() event only
        // This allows products to be restored without losing their images
        $this->audit('deleted', $product);
    }

    /**
     * Handle product force deletion - clean up media files
     * NEW-HIGH-05 FIX: Media files are only deleted when product is permanently deleted
     */
    public function forceDeleted(Product $product): void
    {
        $this->deleteMediaFiles($product);
        $this->audit('force_deleted', $product);
    }

    /**
     * Delete associated media files for a product
     */
    protected function deleteMediaFiles(Product $product): void
    {
        try {
            if ($product->image) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($product->image);
            }

            if ($product->thumbnail) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($product->thumbnail);
            }

            // Delete gallery images if stored as JSON array
            if ($product->images && is_array($product->images)) {
                foreach ($product->images as $imagePath) {
                    if ($imagePath) {
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($imagePath);
                    }
                }
            }

            if ($product->gallery && is_array($product->gallery)) {
                foreach ($product->gallery as $imagePath) {
                    if ($imagePath) {
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($imagePath);
                    }
                }
            }
        } catch (\Exception $e) {
            // Log but don't fail the deletion
            \Illuminate\Support\Facades\Log::warning('Failed to delete product media files', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function audit(string $action, Product $product, array $changes = []): void
    {
        try {
            $req = request();
            AuditLog::create([
                'user_id' => optional(auth()->user())->getKey(),
                'branch_id' => $product->branch_id, // V21-HIGH-06 Fix: Include branch_id from product
                'action' => "Product:{$action}",
                'subject_type' => Product::class,
                'subject_id' => $product->getKey(),
                'auditable_type' => Product::class,
                'auditable_id' => $product->getKey(),
                'ip' => $req?->ip(),
                'user_agent' => (string) $req?->userAgent(),
                'old_values' => [],
                'new_values' => $changes ?: $product->attributesToArray(),
            ]);
        } catch (\Throwable $e) {
            // ignore audit failures
        }
    }
}
