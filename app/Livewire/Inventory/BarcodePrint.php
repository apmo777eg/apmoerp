<?php

declare(strict_types=1);

namespace App\Livewire\Inventory;

use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use App\Livewire\BaseComponent as Component;
#[Layout('layouts.app')]
class BarcodePrint extends Component
{
    public string $search = '';

    public array $selectedProducts = [];

    public array $printQuantities = [];

    public string $labelSize = 'medium';

    public bool $showPrice = true;

    public bool $showName = true;

    public bool $showSku = true;

    public string $barcodeType = 'barcode';

    public bool $showPreview = false;

    protected ?int $userBranchId = null;

    /**
     * Maximum number of products that can be selected for printing.
     */
    protected const MAX_SELECTED_PRODUCTS = 100;

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->can('inventory.products.view')) {
            abort(403);
        }

        // Require user to have a branch assignment
        if (! $user->branch_id) {
            abort(403, __('User must be assigned to a branch to access this feature'));
        }

        $this->userBranchId = $user->branch_id;
    }

    /**
     * Get the user's branch ID.
     */
    protected function getUserBranchId(): int
    {
        if ($this->userBranchId === null) {
            $user = Auth::user();
            if (! $user || ! $user->branch_id) {
                abort(403, __('User must be assigned to a branch'));
            }
            $this->userBranchId = $user->branch_id;
        }

        return $this->userBranchId;
    }

    public function render()
    {
        $branchId = $this->getUserBranchId();

        // BUG-003 FIX: Add branch filter and properly group search conditions
        $products = Product::query()
            ->where('branch_id', $branchId)
            ->when($this->search, function ($q) {
                // Group OR conditions to prevent bypassing the branch filter
                $q->where(function ($subQuery) {
                    $subQuery->where('name', 'like', "%{$this->search}%")
                        ->orWhere('sku', 'like', "%{$this->search}%")
                        ->orWhere('barcode', 'like', "%{$this->search}%");
                });
            })
            ->orderBy('name')
            ->limit(50)
            ->get();

        // BUG-007 FIX: Eager load relations and apply branch filter
        // Selection limit is enforced in addProduct(), so selectedProducts is already bounded
        $selectedProductDetails = Product::query()
            ->where('branch_id', $branchId)
            ->whereIn('id', $this->selectedProducts)
            ->with(['tax', 'priceGroup']) // Eager load relations used in view
            ->get();

        return view('livewire.inventory.barcode-print', [
            'products' => $products,
            'selectedProductDetails' => $selectedProductDetails,
        ]);
    }

    public function addProduct(int $productId): void
    {
        // Limit selection size
        if (count($this->selectedProducts) >= self::MAX_SELECTED_PRODUCTS) {
            return;
        }

        // Verify product belongs to user's branch before adding
        $branchId = $this->getUserBranchId();
        $product = Product::where('id', $productId)
            ->where('branch_id', $branchId)
            ->first();

        if (! $product) {
            return; // Silently ignore products from other branches
        }

        if (! in_array($productId, $this->selectedProducts)) {
            $this->selectedProducts[] = $productId;
            $this->printQuantities[$productId] = 1;
        }
    }

    public function removeProduct(int $productId): void
    {
        $this->selectedProducts = array_values(array_filter($this->selectedProducts, fn ($id) => $id !== $productId));
        unset($this->printQuantities[$productId]);
    }

    public function updateQuantity(int $productId, int $qty): void
    {
        // Only update if product is in selected list (already branch-verified)
        if (in_array($productId, $this->selectedProducts)) {
            $this->printQuantities[$productId] = max(1, min(100, $qty));
        }
    }

    public function clearAll(): void
    {
        $this->selectedProducts = [];
        $this->printQuantities = [];
    }

    public function togglePreview(): void
    {
        $this->showPreview = ! $this->showPreview;
    }

    public function getTotalLabelsProperty(): int
    {
        return array_sum($this->printQuantities);
    }
}
