<?php

declare(strict_types=1);

namespace App\Livewire\Purchases\Quotations;

use App\Models\Product;
use App\Models\PurchaseRequisition;
use App\Models\Supplier;
use App\Models\SupplierQuotation;
use App\Models\SupplierQuotationItem;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    use \App\Http\Requests\Traits\HasMultilingualValidation;
    use AuthorizesRequests;

    public ?SupplierQuotation $quotation = null;

    public bool $isEdit = false;

    public ?int $requisition_id = null;

    public ?int $supplier_id = null;

    public string $quotation_date = '';

    public int $validity_days = 30;

    public string $valid_until = '';

    public string $payment_terms = '';

    public string $delivery_terms = '';

    public int $delivery_days = 0;

    public int $lead_time_days = 0;

    public string $terms_conditions = '';

    public string $notes = '';

    public array $items = [];

    public array $products = [];

    public array $suppliers = [];

    public array $requisitions = [];

    public function getRules(): array
    {
        $branchId = (int) ($this->quotation?->branch_id ?? current_branch_id() ?? 0);
        $branchId = $branchId > 0 ? $branchId : null;

        return [
            'requisition_id' => ['required', new \App\Rules\BranchScopedExists('purchase_requisitions', 'id', $branchId)],
            'supplier_id' => ['required', new \App\Rules\BranchScopedExists('suppliers', 'id', $branchId)],
            'quotation_date' => 'required|date',
            'validity_days' => 'required|integer|min:1|max:365',
            'payment_terms' => $this->unicodeText(required: false),
            'delivery_terms' => $this->unicodeText(required: false),
            'delivery_days' => 'required|integer|min:0|max:365',
            'lead_time_days' => 'required|integer|min:0|max:365',
            'terms_conditions' => $this->unicodeText(required: false),
            'notes' => $this->unicodeText(required: false),
            'items' => 'required|array|min:1',
            'items.*.product_id' => ['required', new \App\Rules\BranchScopedExists('products', 'id', $branchId)],
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_percentage' => 'nullable|numeric|min:0|max:100',
            'items.*.notes' => $this->unicodeText(required: false),
        ];
    }

    public function mount(?int $id = null): void
    {
        $this->authorize('purchases.manage');

        if ($id) {
            $this->isEdit = true;
            $this->quotation = SupplierQuotation::with(['items.product'])->findOrFail($id);
            $this->loadQuotation();
        } else {
            $this->quotation_date = now()->format('Y-m-d');
            $this->calculateValidUntil();
            $this->addItem();
        }

        $this->loadData();
    }

    protected function loadQuotation(): void
    {
        if (! $this->quotation) {
            return;
        }

        $this->requisition_id = $this->quotation->requisition_id;
        $this->supplier_id = $this->quotation->supplier_id;
        $this->quotation_date = $this->quotation->quotation_date?->format('Y-m-d') ?? '';
        $this->valid_until = $this->quotation->valid_until?->format('Y-m-d') ?? '';

        // derive validity_days if possible
        if ($this->quotation->quotation_date && $this->quotation->valid_until) {
            $this->validity_days = (int) $this->quotation->quotation_date->diffInDays($this->quotation->valid_until);
        }

        $this->payment_terms = (string) ($this->quotation->payment_terms ?? '');
        $this->delivery_terms = (string) ($this->quotation->delivery_terms ?? '');
        $this->delivery_days = (int) ($this->quotation->delivery_days ?? 0);
        $this->lead_time_days = (int) ($this->quotation->lead_time_days ?? 0);
        $this->terms_conditions = (string) ($this->quotation->terms_conditions ?? '');
        $this->notes = (string) ($this->quotation->notes ?? '');

        $this->items = $this->quotation->items->map(function (SupplierQuotationItem $item) {
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name ?? '',
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'tax_percentage' => (float) ($item->tax_percent ?? 0),
                'notes' => (string) ($item->notes ?? ''),
            ];
        })->toArray();

        if (empty($this->items)) {
            $this->addItem();
        }
    }

    protected function loadData(): void
    {
        $branchId = (int) ($this->quotation?->branch_id ?? current_branch_id() ?? 0);

        $this->products = $branchId > 0
            ? Product::query()
                ->where('branch_id', $branchId)
                ->where('status', 'active')
                ->select('id', 'name', 'sku', 'default_price')
                ->orderBy('name')
                ->get()
                ->toArray()
            : [];

        $this->suppliers = $branchId > 0
            ? Supplier::query()
                ->where('branch_id', $branchId)
                ->where('is_active', true)
                ->select('id', 'name', 'code')
                ->orderBy('name')
                ->get()
                ->toArray()
            : [];

        $this->requisitions = $branchId > 0
            ? PurchaseRequisition::query()
                ->where('branch_id', $branchId)
                ->where('status', 'approved')
                ->where('is_converted', false)
                ->select('id', 'code', 'subject')
                ->orderByDesc('id')
                ->get()
                ->toArray()
            : [];
    }

    public function updatedValidityDays(): void
    {
        $this->calculateValidUntil();
    }

    public function updatedQuotationDate(): void
    {
        $this->calculateValidUntil();
    }


    protected function calculateValidUntil(): void
    {
        if (! $this->quotation_date) {
            $this->valid_until = '';

            return;
        }

        try {
            $this->valid_until = now()
                ->createFromFormat('Y-m-d', $this->quotation_date)
                ->addDays((int) $this->validity_days)
                ->format('Y-m-d');
        } catch (\Throwable) {
            $this->valid_until = '';
        }
    }

    public function addItem(): void
    {
        $this->items[] = [
            'product_id' => null,
            'product_name' => '',
            'quantity' => 1,
            'unit_price' => 0,
            'tax_percentage' => 0,
            'notes' => '',
        ];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);

        if (empty($this->items)) {
            $this->addItem();
        }
    }

    public function updateProductDetails(int $index): void
    {
        $productId = $this->items[$index]['product_id'] ?? null;
        if (! $productId) {
            return;
        }

        $product = Product::find($productId);
        if (! $product) {
            return;
        }

        $this->items[$index]['product_name'] = $product->name;
        if (! isset($this->items[$index]['unit_price']) || (float) $this->items[$index]['unit_price'] <= 0) {
            $this->items[$index]['unit_price'] = $product->default_price ?? 0;
        }
    }

    public function loadRequisitionItems(): void
    {
        if (! $this->requisition_id) {
            return;
        }

        $requisition = PurchaseRequisition::with('items.product')->find($this->requisition_id);
        if (! $requisition) {
            return;
        }

        $this->items = $requisition->items->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name ?? '',
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) ($item->product?->default_price ?? 0),
                'tax_percentage' => 0,
                'notes' => (string) ($item->specifications ?? ''),
            ];
        })->toArray();

        if (empty($this->items)) {
            $this->addItem();
        }
    }

    public function save(): void
    {
        $this->authorize('purchases.manage');
        $this->validate($this->getRules());
        $wasEdit = $this->isEdit;


        $branchId = (int) ($this->quotation?->branch_id ?? current_branch_id() ?? 0);
        if ($branchId <= 0) {
            $this->addError('branch_id', __('Please select a branch first.'));

            return;
        }

        $userId = function_exists('actual_user_id') ? actual_user_id() : (auth()->id() ?? null);

        // Compute totals from items
        $subTotal = '0.0000';
        $taxTotal = '0.0000';

        foreach ($this->items as $item) {
            $qty = (string) ((float) ($item['quantity'] ?? 0));
            $price = (string) ((float) ($item['unit_price'] ?? 0));
            $lineBase = bcmul($qty, $price, 4);
            $subTotal = bcadd($subTotal, $lineBase, 4);

            $taxPercent = (string) ((float) ($item['tax_percentage'] ?? 0));
            if ((float) $taxPercent > 0) {
                $lineTax = bcdiv(bcmul($lineBase, $taxPercent, 6), '100', 4);
                $taxTotal = bcadd($taxTotal, $lineTax, 4);
            }
        }

        $discountTotal = '0.0000';
        $shippingTotal = '0.0000';
        $grandTotal = bcadd(bcadd($subTotal, $taxTotal, 4), bcsub($shippingTotal, $discountTotal, 4), 4);

        $data = [
            'branch_id' => $branchId,
            'supplier_id' => $this->supplier_id,
            'requisition_id' => $this->requisition_id,
            'quotation_date' => $this->quotation_date,
            'valid_until' => $this->valid_until ?: null,
            'payment_terms' => $this->payment_terms ?: null,
            'delivery_terms' => $this->delivery_terms ?: null,
            'delivery_days' => $this->delivery_days,
            'lead_time_days' => $this->lead_time_days,
            'terms_conditions' => $this->terms_conditions ?: null,
            'notes' => $this->notes ?: null,
            'status' => $this->quotation?->status ?? 'pending',
            // totals (canonical columns only)
            'subtotal' => $subTotal,
            'tax_amount' => $taxTotal,
            'discount_amount' => $discountTotal,
            'shipping_amount' => $shippingTotal,
            'total_amount' => $grandTotal,
            'currency' => 'USD',
            'updated_by' => $userId,
        ];

        DB::transaction(function () use ($data, $branchId, $userId, $subTotal, $taxTotal): void {
            if ($this->isEdit && $this->quotation) {
                $this->quotation->update($data);
                $this->quotation->items()->delete();
            } else {
                $this->quotation = SupplierQuotation::create(array_merge($data, ['created_by' => $userId]));
                $this->isEdit = true;
            }

            foreach ($this->items as $item) {
                $qty = (string) ((float) ($item['quantity'] ?? 0));
                $price = (string) ((float) ($item['unit_price'] ?? 0));
                $base = bcmul($qty, $price, 4);
                $taxPercent = (float) ($item['tax_percentage'] ?? 0);
                $tax = $taxPercent > 0 ? bcdiv(bcmul($base, (string) $taxPercent, 6), '100', 4) : '0.0000';
                $lineTotal = bcadd($base, $tax, 4);

                SupplierQuotationItem::create([
                    'quotation_id' => $this->quotation->id,
                    'branch_id' => $branchId,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'tax_percent' => $item['tax_percentage'] ?? 0,
                    'line_total' => $lineTotal,
                    'notes' => $item['notes'] ?? null,
                            'updated_by' => $userId,
                ]);
            }
        });

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $wasEdit ? __('Quotation updated successfully') : __('Quotation created successfully'),
        ]);

        $this->redirectRoute('app.purchases.quotations.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.purchases.quotations.form');
    }
}
