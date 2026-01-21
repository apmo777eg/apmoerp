<?php

declare(strict_types=1);

namespace App\Livewire\Purchases;

use App\Events\PurchaseReceived;
use App\Livewire\Concerns\HandlesErrors;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Form extends Component
{
    use AuthorizesRequests;
    use HandlesErrors;

    public ?Purchase $purchase = null;

    public bool $editMode = false;

    public string $supplier_id = '';

    public string $warehouse_id = '';

    public string $reference_no = '';

    public string $status = 'draft';

    public string $currency = 'EGP';

    public string $notes = '';

    public string $supplier_notes = '';

    public string $internal_notes = '';

    public string $expected_delivery_date = '';

    public string $actual_delivery_date = '';

    public string $shipping_method = '';

    public float $discount_total = 0;

    public float $shipping_total = 0;

    public array $items = [];

    public string $productSearch = '';

    public array $searchResults = [];

    public bool $isSubmitting = false;

    /**
     * Get the user's branch ID with strict validation.
     * Returns null if user has no branch assigned.
     */
    protected function getUserBranchId(): ?int
    {
        $user = auth()->user();
        if (! $user) {
            return null;
        }

        // First check direct branch_id assignment
        if ($user->branch_id) {
            return (int) $user->branch_id;
        }

        // Then check branches relationship
        $firstBranch = $user->branches()->first();
        if ($firstBranch) {
            return (int) $firstBranch->id;
        }

        return null;
    }

    protected function rules(): array
    {
        $branchId = $this->getUserBranchId();

        return [
            'supplier_id' => [
                'required',
                'exists:suppliers,id',
                function ($attribute, $value, $fail) use ($branchId) {
                    if ($value && $branchId) {
                        $supplier = Supplier::find($value);
                        if ($supplier && $supplier->branch_id && $supplier->branch_id !== $branchId) {
                            $fail(__('The selected supplier does not belong to your branch.'));
                        }
                    }
                },
            ],
            'warehouse_id' => [
                'required',
                'exists:warehouses,id',
                function ($attribute, $value, $fail) use ($branchId) {
                    if ($value && $branchId) {
                        $warehouse = Warehouse::find($value);
                        if ($warehouse && $warehouse->branch_id && $warehouse->branch_id !== $branchId) {
                            $fail(__('The selected warehouse does not belong to your branch.'));
                        }
                    }
                },
            ],
            'reference_no' => 'nullable|string|max:100',
            'status' => 'required|in:draft,pending,posted,received,cancelled',
            'currency' => 'nullable|string|max:3',
            'notes' => 'nullable|string',
            'supplier_notes' => 'nullable|string|max:1000',
            'internal_notes' => 'nullable|string|max:1000',
            'expected_delivery_date' => 'nullable|date',
            'actual_delivery_date' => 'nullable|date',
            'shipping_method' => 'nullable|string|max:191',
            'discount_total' => 'nullable|numeric|min:0',
            'shipping_total' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            // V57-CRITICAL-03 FIX: Use BranchScopedExists for efficient branch-aware validation
            'items.*.product_id' => [
                'required',
                new \App\Rules\BranchScopedExists('products', 'id', $branchId),
            ],
            'items.*.qty' => 'required|numeric|min:0.0001',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ];
    }

    public function mount(?Purchase $purchase = null): void
    {
        $this->authorize('purchases.manage');

        // BUG-002 Fix: Validate user has a branch assigned
        $branchId = $this->getUserBranchId();
        if (! $branchId) {
            abort(403, __('You must be assigned to a branch to create or edit purchases.'));
        }

        if ($purchase && $purchase->exists) {
            // BUG-002 Fix: Verify purchase belongs to user's branch
            if ($purchase->branch_id !== $branchId) {
                abort(403, __('You do not have permission to edit this purchase.'));
            }

            $this->purchase = $purchase;
            $this->editMode = true;
            $this->supplier_id = (string) ($purchase->supplier_id ?? '');
            $this->warehouse_id = (string) ($purchase->warehouse_id ?? '');
            $this->reference_no = $purchase->reference_no ?? '';
            $this->status = $purchase->status ?? 'draft';
            $this->currency = $purchase->currency ?? 'EGP';
            $this->notes = $purchase->notes ?? '';
            $this->supplier_notes = $purchase->supplier_notes ?? '';
            $this->internal_notes = $purchase->internal_notes ?? '';
            $this->expected_delivery_date = $purchase->expected_delivery_date?->format('Y-m-d') ?? '';
            $this->actual_delivery_date = $purchase->actual_delivery_date?->format('Y-m-d') ?? '';
            $this->shipping_method = $purchase->shipping_method ?? '';
            $this->discount_total = decimal_float($purchase->discount_total ?? 0);
            $this->shipping_total = decimal_float($purchase->shipping_total ?? 0);

            $this->items = $purchase->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name ?? '',
                'sku' => $item->product?->sku ?? '',
                'qty' => decimal_float($item->qty, 4),
                'unit_cost' => decimal_float($item->unit_cost, 4),
                'discount' => decimal_float($item->discount ?? 0),
                'tax_rate' => decimal_float($item->tax_rate ?? 0),
            ])->toArray();
        }
    }

    public function updatedProductSearch(): void
    {
        if (strlen($this->productSearch) < 2) {
            $this->searchResults = [];

            return;
        }

        $branchId = $this->getUserBranchId();

        // BUG-004 Fix: Filter products by branch
        $query = Product::query()
            ->where(function ($q) {
                $q->where('name', 'like', "%{$this->productSearch}%")
                    ->orWhere('sku', 'like', "%{$this->productSearch}%");
            });

        // Filter by branch if user has one
        if ($branchId) {
            $query->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                    ->orWhereNull('branch_id');
            });
        }

        $this->searchResults = $query
            ->limit(10)
            ->get(['id', 'name', 'sku', 'cost'])
            ->toArray();
    }

    public function addProduct(int $productId): void
    {
        $branchId = $this->getUserBranchId();

        // BUG-004 Fix: Validate product belongs to user's branch
        $query = Product::query()->where('id', $productId);
        if ($branchId) {
            $query->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                    ->orWhereNull('branch_id');
            });
        }

        $product = $query->first();
        if (! $product) {
            return;
        }

        $existingIndex = collect($this->items)->search(fn ($item) => $item['product_id'] == $productId);

        if ($existingIndex !== false) {
            $this->items[$existingIndex]['qty'] += 1;
        } else {
            $this->items[] = [
                'id' => null,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'sku' => $product->sku ?? '',
                'qty' => 1,
                // V51-CRIT-03 FIX: Use decimal_float() with scale 4 to match decimal:4 schema for unit_cost
                'unit_cost' => decimal_float($product->cost ?? 0, 4),
                'discount' => 0,
                'tax_rate' => 0,
            ];
        }

        $this->productSearch = '';
        $this->searchResults = [];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function getSubTotalProperty(): float
    {
        return collect($this->items)->sum(function ($item) {
            return ($item['qty'] ?? 0) * ($item['unit_cost'] ?? 0) - ($item['discount'] ?? 0);
        });
    }

    public function getTaxTotalProperty(): float
    {
        return collect($this->items)->sum(function ($item) {
            $lineTotal = ($item['qty'] ?? 0) * ($item['unit_cost'] ?? 0) - ($item['discount'] ?? 0);

            return $lineTotal * (($item['tax_rate'] ?? 0) / 100);
        });
    }

    public function getGrandTotalProperty(): float
    {
        return $this->subTotal + $this->taxTotal - $this->discount_total + $this->shipping_total;
    }

    public function save(): mixed
    {
        // BUG-010 Fix: Prevent double submission
        if ($this->isSubmitting) {
            return null;
        }
        $this->isSubmitting = true;

        try {
            $this->validate();

            $user = auth()->user();

            // BUG-002 Fix: Strict branch validation
            $branchId = $this->getUserBranchId();
            if (! $branchId) {
                throw ValidationException::withMessages([
                    'branch' => [__('You must be assigned to a branch to create or edit purchases.')],
                ]);
            }

            return $this->handleOperation(
                operation: function () use ($user, $branchId) {
                    DB::transaction(function () use ($user, $branchId) {
                        // V24-HIGH-05 FIX: Preserve original purchase_date in edit mode
                        // Only set to now() when creating a new purchase
                        $purchaseDate = $this->editMode && $this->purchase?->purchase_date 
                            ? $this->purchase->purchase_date->toDateString() 
                            : now()->toDateString();

                        $purchaseData = [
                            'branch_id' => $branchId,
                            'supplier_id' => $this->supplier_id,
                            'warehouse_id' => $this->warehouse_id,
                            'reference_number' => $this->reference_no ?: null,
                            'status' => $this->status,
                            'currency' => $this->currency,
                            'notes' => $this->notes,
                            'expected_date' => $this->expected_delivery_date ?: null,
                            'purchase_date' => $purchaseDate,
                            // Use correct migration column names
                            'subtotal' => $this->subTotal,
                            'discount_amount' => $this->discount_total,
                            'tax_amount' => $this->taxTotal,
                            'shipping_amount' => $this->shipping_total,
                            'total_amount' => $this->grandTotal,
                            'paid_amount' => 0,
                            // payment_status handled by updatePaymentStatus()
                        ];

                        if ($this->editMode) {
                            // BUG-008 Fix: Verify branch hasn't changed on edit
                            if ($this->purchase->branch_id !== $branchId) {
                                throw ValidationException::withMessages([
                                    'branch' => [__('You do not have permission to edit this purchase.')],
                                ]);
                            }

                            // V21-CRITICAL-02 Fix: Prevent editing purchases after certain states
                            // to avoid destroying audit trail and financial data integrity
                            $nonEditableStatuses = ['posted', 'received', 'closed', 'cancelled'];
                            if (in_array($this->purchase->status, $nonEditableStatuses)) {
                                throw ValidationException::withMessages([
                                    'status' => [__('Cannot edit a purchase with status: :status. Create a debit note or reversal instead.', ['status' => $this->purchase->status])],
                                ]);
                            }

                            $this->purchase->update($purchaseData);
                            $purchase = $this->purchase;
                            // V21-CRITICAL-02 Fix: Only delete items for editable purchases
                            // This is safe because we've already verified the purchase is editable
                            $purchase->items()->delete();
                        } else {
                            $purchaseData['created_by'] = $user->id;
                            $purchase = Purchase::create($purchaseData);
                        }

                        foreach ($this->items as $item) {
                            // V22-HIGH-05 FIX: Calculate discount_percent from discount amount
                            // The UI allows entering a discount amount, which needs to be converted to percentage
                            $lineSubtotal = $item['qty'] * $item['unit_cost'];
                            $discountAmount = max(0, decimal_float($item['discount'] ?? 0));

                            // Calculate discount_percent from the discount amount (if lineSubtotal > 0)
                            $discountPercent = 0;
                            if ($lineSubtotal > 0 && $discountAmount > 0) {
                                // Ensure discount doesn't exceed line subtotal
                                $discountAmount = min($discountAmount, $lineSubtotal);
                                $discountPercent = ($discountAmount / $lineSubtotal) * 100;
                            }

                            $lineAfterDiscount = $lineSubtotal - $discountAmount;
                            $taxAmount = $lineAfterDiscount * (($item['tax_rate'] ?? 0) / 100);
                            $lineTotal = $lineAfterDiscount + $taxAmount;

                            // Get product info
                            $product = Product::find($item['product_id']);

                            // V22-HIGH-09 FIX: Set received_quantity to match quantity when status is 'received'
                            // This ensures the listener uses the correct quantity for stock additions
                            $receivedQty = ($this->status === 'received') ? $item['qty'] : 0;

                            PurchaseItem::create([
                                'purchase_id' => $purchase->id,
                                'product_id' => $item['product_id'],
                                'product_name' => $product?->name ?? $item['product_name'] ?? '',
                                'sku' => $product?->sku ?? $item['sku'] ?? null,
                                'quantity' => $item['qty'],
                                'received_quantity' => $receivedQty,
                                'unit_price' => $item['unit_cost'],
                                // V22-HIGH-05 FIX: Store the calculated discount_percent
                                'discount_percent' => round($discountPercent, 2),
                                'tax_percent' => $item['tax_rate'] ?? 0,
                                'tax_amount' => $taxAmount,
                                'line_total' => $lineTotal,
                            ]);
                        }

                        // BUG-007 Fix: Dispatch PurchaseReceived event for inventory updates
                        // Only dispatch for received purchases to trigger stock addition
                        if ($purchase->status === 'received') {
                            event(new PurchaseReceived($purchase->fresh()));
                        }
                    });
                },
                successMessage: $this->editMode ? __('Purchase updated successfully') : __('Purchase created successfully'),
                redirectRoute: 'app.purchases.index'
            );
        } finally {
            $this->isSubmitting = false;
        }
    }

    public function render()
    {
        $branchId = $this->getUserBranchId();

        // BUG-004 Fix: Filter suppliers by branch
        $suppliersQuery = Supplier::where('is_active', true)->orderBy('name');
        if ($branchId) {
            $suppliersQuery->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                    ->orWhereNull('branch_id');
            });
        }
        // BUG-009 Fix: Limit results for performance
        $suppliers = $suppliersQuery->limit(100)->get(['id', 'name']);

        // BUG-004 Fix: Filter warehouses by branch
        $warehousesQuery = Warehouse::where('is_active', true)->orderBy('name');
        if ($branchId) {
            $warehousesQuery->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                    ->orWhereNull('branch_id');
            });
        }
        // BUG-009 Fix: Limit results for performance
        $warehouses = $warehousesQuery->limit(50)->get(['id', 'name']);

        $currencies = \App\Models\Currency::active()->ordered()->get(['code', 'name', 'symbol']);

        return view('livewire.purchases.form', [
            'suppliers' => $suppliers,
            'warehouses' => $warehouses,
            'currencies' => $currencies,
            'subTotal' => $this->subTotal,
            'taxTotal' => $this->taxTotal,
            'grandTotal' => $this->grandTotal,
        ])->layout('layouts.app', ['title' => $this->editMode ? __('Edit Purchase') : __('New Purchase')]);
    }
}
