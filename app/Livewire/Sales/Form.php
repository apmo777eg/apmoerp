<?php

declare(strict_types=1);

namespace App\Livewire\Sales;

use App\Events\SaleCompleted;
use App\Livewire\Concerns\HandlesErrors;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\Warehouse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

// Financial calculation precision constants
const BCMATH_CALCULATION_SCALE = 4;  // Scale for intermediate calculations
const BCMATH_TAX_RATE_SCALE = 6;     // Higher precision for tax rate division
const BCMATH_STORAGE_SCALE = 2;       // Scale for database storage (2 decimal places)
const PRICE_COMPARISON_TOLERANCE = 0.01; // Tolerance for price comparison (1 cent)

class Form extends Component
{
    use AuthorizesRequests;
    use HandlesErrors;

    public ?Sale $sale = null;

    public bool $editMode = false;

    public string $customer_id = '';

    public string $warehouse_id = '';

    public string $reference_no = '';

    public string $status = 'completed';

    public string $currency = 'EGP';

    public string $notes = '';

    public string $customer_notes = '';

    public string $internal_notes = '';

    public string $delivery_date = '';

    public string $shipping_method = '';

    public string $tracking_number = '';

    public float $discount_total = 0;

    public float $shipping_total = 0;

    public array $items = [];

    public string $productSearch = '';

    public array $searchResults = [];

    public string $payment_method = 'cash';

    public float $payment_amount = 0;

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
            'customer_id' => [
                'nullable',
                'exists:customers,id',
                function ($attribute, $value, $fail) use ($branchId) {
                    if ($value && $branchId) {
                        $customer = Customer::find($value);
                        if ($customer && $customer->branch_id && $customer->branch_id !== $branchId) {
                            $fail(__('The selected customer does not belong to your branch.'));
                        }
                    }
                },
            ],
            'warehouse_id' => [
                'nullable',
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
            'status' => 'required|in:draft,pending,completed,cancelled,refunded',
            'currency' => 'nullable|string|max:3',
            'notes' => 'nullable|string',
            'customer_notes' => 'nullable|string|max:1000',
            'internal_notes' => 'nullable|string|max:1000',
            'delivery_date' => 'nullable|date',
            'shipping_method' => 'nullable|string|max:191',
            'tracking_number' => 'nullable|string|max:191',
            'discount_total' => 'nullable|numeric|min:0',
            'shipping_total' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            // V57-CRITICAL-03 FIX: Use BranchScopedExists for efficient branch-aware validation
            'items.*.product_id' => [
                'required',
                new \App\Rules\BranchScopedExists('products', 'id', $branchId),
            ],
            'items.*.qty' => 'required|numeric|min:0.0001',
            'items.*.unit_price' => 'required|numeric|min:0',
            'payment_method' => 'required|string|in:cash,card,bank_transfer,cheque',
            'payment_amount' => [
                'required',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) {
                    if ($value > $this->grandTotal) {
                        $fail(__('Payment amount cannot exceed the grand total.'));
                    }
                },
            ],
        ];
    }

    public function mount(?Sale $sale = null): void
    {
        $this->authorize('sales.manage');

        // BUG-001 Fix: Validate user has a branch assigned
        $branchId = $this->getUserBranchId();
        if (! $branchId) {
            abort(403, __('You must be assigned to a branch to create or edit sales.'));
        }

        if ($sale && $sale->exists) {
            // BUG-001 Fix: Verify sale belongs to user's branch
            if ($sale->branch_id !== $branchId) {
                abort(403, __('You do not have permission to edit this sale.'));
            }

            $this->sale = $sale;
            $this->editMode = true;
            $this->customer_id = (string) ($sale->customer_id ?? '');
            $this->warehouse_id = (string) ($sale->warehouse_id ?? '');
            $this->reference_no = $sale->reference_number ?? '';
            $this->status = $sale->status ?? 'completed';
            $this->currency = $sale->currency ?? 'EGP';
            $this->notes = $sale->notes ?? '';
            $this->customer_notes = $sale->customer_notes ?? '';
            $this->internal_notes = $sale->internal_notes ?? '';
            $this->delivery_date = $sale->delivery_date?->format('Y-m-d') ?? '';
            $this->shipping_method = $sale->shipping_method ?? '';
            $this->tracking_number = $sale->tracking_number ?? '';
            // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
            $this->discount_total = decimal_float($sale->discount_total ?? 0);
            $this->shipping_total = decimal_float($sale->shipping_total ?? 0);

            $this->items = $sale->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name ?? '',
                'sku' => $item->product?->sku ?? '',
                // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
                'qty' => decimal_float($item->qty, 4),
                'unit_price' => decimal_float($item->unit_price, 4),
                'discount' => decimal_float($item->discount ?? 0),
                'tax_rate' => decimal_float($item->tax_rate ?? 0),
            ])->toArray();

            if ($sale->payments->isNotEmpty()) {
                $firstPayment = $sale->payments->first();
                $this->payment_method = $firstPayment->payment_method ?? 'cash';
                // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
                $this->payment_amount = decimal_float($firstPayment->amount ?? 0);
            }
        }
    }

    public function updatedProductSearch(): void
    {
        if (strlen($this->productSearch) < 2) {
            $this->searchResults = [];

            return;
        }

        $branchId = $this->getUserBranchId();

        // BUG-003 Fix: Filter products by branch
        $query = Product::query()
            ->where(function ($q) {
                $q->where('name', 'like', "%{$this->productSearch}%")
                    ->orWhere('sku', 'like', "%{$this->productSearch}%");
            })
            ->where('status', 'active');

        // Filter by branch if user has one
        if ($branchId) {
            $query->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                    ->orWhereNull('branch_id');
            });
        }

        $this->searchResults = $query
            ->limit(10)
            ->get(['id', 'name', 'sku', 'default_price'])
            ->toArray();
    }

    public function addProduct(int $productId): void
    {
        $branchId = $this->getUserBranchId();

        // BUG-003 Fix: Validate product belongs to user's branch
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
                // V51-CRIT-04 FIX: Use decimal_float() with scale 4 to match decimal:4 schema for prices
                'unit_price' => decimal_float($product->default_price ?? 0, 4),
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

    /**
     * V22-HIGH-06 FIX: Use BCMath for consistent precision with line item calculations
     */
    public function getSubTotalProperty(): float
    {
        $total = '0.00';
        foreach ($this->items as $item) {
            $qty = (string) ($item['qty'] ?? 0);
            $price = (string) ($item['unit_price'] ?? 0);
            $discount = (string) ($item['discount'] ?? 0);

            $lineSubtotal = bcmul($qty, $price, BCMATH_CALCULATION_SCALE);
            $lineAfterDiscount = bcsub($lineSubtotal, $discount, BCMATH_CALCULATION_SCALE);
            $total = bcadd($total, $lineAfterDiscount, BCMATH_CALCULATION_SCALE);
        }

        // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
        return decimal_float(bcdiv($total, '1', BCMATH_STORAGE_SCALE));
    }

    /**
     * V22-HIGH-06 FIX: Use BCMath for consistent precision with line item calculations
     */
    public function getTaxTotalProperty(): float
    {
        $total = '0.00';
        foreach ($this->items as $item) {
            $qty = (string) ($item['qty'] ?? 0);
            $price = (string) ($item['unit_price'] ?? 0);
            $discount = (string) ($item['discount'] ?? 0);
            $taxRate = (string) ($item['tax_rate'] ?? 0);

            $lineSubtotal = bcmul($qty, $price, BCMATH_CALCULATION_SCALE);
            $lineAfterDiscount = bcsub($lineSubtotal, $discount, BCMATH_CALCULATION_SCALE);
            $taxRateDecimal = bcdiv($taxRate, '100', BCMATH_TAX_RATE_SCALE);
            $taxAmount = bcmul($lineAfterDiscount, $taxRateDecimal, BCMATH_CALCULATION_SCALE);
            $total = bcadd($total, $taxAmount, BCMATH_CALCULATION_SCALE);
        }

        // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
        return decimal_float(bcdiv($total, '1', BCMATH_STORAGE_SCALE));
    }

    /**
     * V22-HIGH-06 FIX: Use BCMath for consistent precision
     */
    public function getGrandTotalProperty(): float
    {
        $subTotal = (string) $this->subTotal;
        $taxTotal = (string) $this->taxTotal;
        $discountTotal = (string) $this->discount_total;
        $shippingTotal = (string) $this->shipping_total;

        // grandTotal = subTotal + taxTotal - discount_total + shipping_total
        $result = bcadd($subTotal, $taxTotal, BCMATH_CALCULATION_SCALE);
        $result = bcsub($result, $discountTotal, BCMATH_CALCULATION_SCALE);
        $result = bcadd($result, $shippingTotal, BCMATH_CALCULATION_SCALE);

        // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
        return decimal_float(bcdiv($result, '1', BCMATH_STORAGE_SCALE));
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

            // BUG-001 Fix: Strict branch validation
            $branchId = $this->getUserBranchId();
            if (! $branchId) {
                throw ValidationException::withMessages([
                    'branch' => [__('You must be assigned to a branch to create or edit sales.')],
                ]);
            }

            return $this->handleOperation(
                operation: function () use ($user, $branchId) {
                    DB::transaction(function () use ($user, $branchId) {
                        // BUG-005 Fix: Ensure due_total is non-negative
                        $dueTotal = max(0, $this->grandTotal - $this->payment_amount);

                        // V24-HIGH-04 FIX: Preserve original sale_date in edit mode
                        // Only set to now() when creating a new sale
                        $saleDate = $this->editMode && $this->sale?->sale_date 
                            ? $this->sale->sale_date->toDateString() 
                            : now()->toDateString();

                        $saleData = [
                            'branch_id' => $branchId,
                            'customer_id' => $this->customer_id ?: null,
                            'warehouse_id' => $this->warehouse_id ?: null,
                            'reference_number' => $this->reference_no ?: null,
                            'status' => $this->status,
                            'currency' => $this->currency,
                            'notes' => $this->notes,
                            'internal_notes' => $this->internal_notes,
                            'delivery_date' => $this->delivery_date ?: null,
                            'shipping_method' => $this->shipping_method,
                            'tracking_number' => $this->tracking_number,
                            'sale_date' => $saleDate,
                            // Use correct migration column names
                            'subtotal' => $this->subTotal,
                            'discount_amount' => $this->discount_total,
                            'tax_amount' => $this->taxTotal,
                            'shipping_amount' => $this->shipping_total,
                            'total_amount' => $this->grandTotal,
                            'paid_amount' => $this->payment_amount,
                            // payment_status handled by updatePaymentStatus()
                        ];

                        if ($this->editMode) {
                            // BUG-008 Fix: Verify branch hasn't changed on edit
                            if ($this->sale->branch_id !== $branchId) {
                                throw ValidationException::withMessages([
                                    'branch' => [__('You do not have permission to edit this sale.')],
                                ]);
                            }

                            // V21-CRITICAL-01 Fix: Prevent editing sales after certain states
                            // to avoid destroying audit trail and financial data integrity
                            $nonEditableStatuses = ['completed', 'posted', 'paid', 'closed', 'cancelled', 'refunded'];
                            if (in_array($this->sale->status, $nonEditableStatuses)) {
                                throw ValidationException::withMessages([
                                    'status' => [__('Cannot edit a sale with status: :status. Create a credit note or reversal instead.', ['status' => $this->sale->status])],
                                ]);
                            }

                            // V21-CRITICAL-01 Fix: Also check if there are any payments
                            // Sales with payments should not be modified to preserve financial trail
                            if ($this->sale->payments()->exists()) {
                                throw ValidationException::withMessages([
                                    'payments' => [__('Cannot edit a sale that has payments. Create a credit note or reversal instead.')],
                                ]);
                            }

                            $this->sale->update($saleData);
                            $sale = $this->sale;
                            // V21-CRITICAL-01 Fix: Only delete items for draft sales without payments
                            // This is safe because we've already verified the sale is editable
                            $sale->items()->delete();
                        } else {
                            $saleData['created_by'] = $user->id;
                            $sale = Sale::create($saleData);
                        }

                        foreach ($this->items as $item) {
                            // SECURITY FIX: Validate price from database to prevent frontend manipulation
                            // Always fetch the current product price from database, never trust client-side values
                            $product = Product::find($item['product_id']);

                            if (! $product) {
                                throw ValidationException::withMessages([
                                    'items' => [__('Product not found: :id', ['id' => $item['product_id']])],
                                ]);
                            }

                            // Use the database price, not the client-provided price
                            // V51-CRIT-04 FIX: Use decimal_float() with scale 4 to match decimal:4 schema for prices
                            $validatedPrice = decimal_float($product->default_price ?? 0, 4);

                            // Optional: Check if user has permission to override prices
                            if (abs($validatedPrice - ($item['unit_price'] ?? 0)) > PRICE_COMPARISON_TOLERANCE) {
                                if (! $user->can_modify_price) {
                                    throw ValidationException::withMessages([
                                        'items' => [__('You are not allowed to modify product prices')],
                                    ]);
                                }
                                // If user can modify prices, use their price but log it for audit
                                $validatedPrice = decimal_float($item['unit_price'], 4);
                            }

                            // Use bcmath for precise financial calculations
                            $lineSubtotal = bcmul((string) $item['qty'], (string) $validatedPrice, BCMATH_CALCULATION_SCALE);
                            $discountAmount = (string) ($item['discount'] ?? 0);
                            $lineAfterDiscount = bcsub($lineSubtotal, $discountAmount, BCMATH_CALCULATION_SCALE);
                            $taxRate = bcdiv((string) ($item['tax_rate'] ?? 0), '100', BCMATH_TAX_RATE_SCALE);
                            $taxAmount = bcmul($lineAfterDiscount, $taxRate, BCMATH_CALCULATION_SCALE);
                            $lineTotal = bcadd($lineAfterDiscount, $taxAmount, BCMATH_CALCULATION_SCALE);

                            SaleItem::create([
                                'sale_id' => $sale->id,
                                'product_id' => $item['product_id'],
                                'warehouse_id' => $sale->warehouse_id,
                                'product_name' => $product->name ?? $item['product_name'] ?? '',
                                'sku' => $product->sku ?? $item['sku'] ?? null,
                                'quantity' => $item['qty'],
                                'unit_price' => $validatedPrice,
                                'discount_percent' => 0,
                                // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
                                'discount_amount' => decimal_float(bcdiv($discountAmount, '1', BCMATH_STORAGE_SCALE)),
                                'tax_percent' => $item['tax_rate'] ?? 0,
                                'tax_amount' => decimal_float(bcdiv($taxAmount, '1', BCMATH_STORAGE_SCALE)),
                                'line_total' => decimal_float(bcdiv($lineTotal, '1', BCMATH_STORAGE_SCALE)),
                            ]);
                        }

                        if ($this->payment_amount > 0) {
                            SalePayment::create([
                                'sale_id' => $sale->id,
                                'payment_method' => $this->payment_method,
                                'amount' => $this->payment_amount,
                                'payment_date' => now()->toDateString(),
                                'status' => 'completed',
                                'received_by' => $user->id,
                            ]);
                        }

                        // BUG-005 Fix: Update payment status after payment
                        $sale->updatePaymentStatus();

                        // BUG-006 Fix: Dispatch SaleCompleted event for inventory updates
                        // Only dispatch for completed sales to trigger stock deduction
                        if ($sale->status === 'completed') {
                            event(new SaleCompleted($sale->fresh()));
                        }
                    });
                },
                successMessage: $this->editMode ? __('Sale updated successfully') : __('Sale created successfully'),
                redirectRoute: 'app.sales.index'
            );
        } finally {
            $this->isSubmitting = false;
        }
    }

    public function render()
    {
        $branchId = $this->getUserBranchId();

        // BUG-003 Fix: Filter customers by branch
        $customersQuery = Customer::active()->orderBy('name');
        if ($branchId) {
            $customersQuery->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                    ->orWhereNull('branch_id');
            });
        }
        // BUG-009 Fix: Limit results for performance
        $customers = $customersQuery->limit(100)->get(['id', 'name']);

        // BUG-003 Fix: Filter warehouses by branch
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

        return view('livewire.sales.form', [
            'customers' => $customers,
            'warehouses' => $warehouses,
            'currencies' => $currencies,
            'subTotal' => $this->subTotal,
            'taxTotal' => $this->taxTotal,
            'grandTotal' => $this->grandTotal,
        ])->layout('layouts.app', ['title' => $this->editMode ? __('Edit Sale') : __('New Sale')]);
    }
}
