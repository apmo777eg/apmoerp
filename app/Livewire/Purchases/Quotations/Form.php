<?php

namespace App\Livewire\Purchases\Quotations;

use App\Models\Product;
use App\Models\PurchaseRequisition;
use App\Models\Supplier;
use App\Models\SupplierQuotation;
use App\Rules\BranchScopedExists;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    use AuthorizesRequests;

    public ?SupplierQuotation $quotation = null;

    public $quotationId = null;

    // Form fields
    public $requisition_id = '';

    public $supplier_id = '';

    public $quotation_date = '';

    public $valid_until = '';

    public $validity_days = 30;

    public $payment_terms = '';

    public $delivery_terms = '';

    public $lead_time_days = '';

    public $notes = '';

    public $terms_conditions = '';

    // Items
    public $items = [];

    protected function rules()
    {
        return [
            // V57-CRITICAL-03 FIX: Use BranchScopedExists to prevent cross-branch references
            'requisition_id' => ['required', new BranchScopedExists('purchase_requisitions')],
            'supplier_id' => ['required', new BranchScopedExists('suppliers')],
            'quotation_date' => 'required|date',
            'valid_until' => 'required|date|after:quotation_date',
            'validity_days' => 'nullable|integer|min:1',
            'payment_terms' => 'nullable|string|max:500',
            'delivery_terms' => 'nullable|string|max:500',
            'lead_time_days' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
            'items' => 'required|array|min:1',
            // V57-CRITICAL-03 FIX: Use BranchScopedExists to prevent cross-branch product references
            'items.*.product_id' => ['required', new BranchScopedExists('products')],
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_percentage' => 'nullable|numeric|min:0|max:100',
            'items.*.notes' => 'nullable|string|max:500',
        ];
    }

    public function mount($id = null)
    {
        if ($id) {
            $this->quotation = SupplierQuotation::with('items')->findOrFail($id);
            $this->authorize('update', $this->quotation);
            $this->quotationId = $id;
            $this->loadQuotation();
        } else {
            $this->authorize('create', SupplierQuotation::class);
            $this->quotation_date = now()->format('Y-m-d');
            $this->calculateValidUntil();
            $this->addItem();
        }
    }

    protected function loadQuotation()
    {
        $this->requisition_id = $this->quotation->requisition_id;
        $this->supplier_id = $this->quotation->supplier_id;
        $this->quotation_date = $this->quotation->quotation_date->format('Y-m-d');
        $this->valid_until = $this->quotation->valid_until->format('Y-m-d');
        $this->payment_terms = $this->quotation->payment_terms;
        $this->delivery_terms = $this->quotation->delivery_terms;
        $this->lead_time_days = $this->quotation->lead_time_days;
        $this->notes = $this->quotation->notes;
        $this->terms_conditions = $this->quotation->terms_conditions;

        $this->items = $this->quotation->items->map(function ($item) {
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'quantity' => $item->qty,
                'unit_price' => $item->unit_cost,
                'tax_percentage' => $item->tax_rate,
                'notes' => $item->notes,
            ];
        })->toArray();
    }

    public function updatedValidityDays()
    {
        $this->calculateValidUntil();
    }

    public function updatedQuotationDate()
    {
        $this->calculateValidUntil();
    }

    protected function calculateValidUntil()
    {
        if ($this->quotation_date && $this->validity_days) {
            $this->valid_until = now()->parse($this->quotation_date)
                ->addDays($this->validity_days)
                ->format('Y-m-d');
        }
    }

    public function loadRequisitionItems()
    {
        if (! $this->requisition_id) {
            return;
        }

        $requisition = PurchaseRequisition::with('items.product')->find($this->requisition_id);

        if ($requisition) {
            $this->items = $requisition->items->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'quantity' => $item->qty,
                    'unit_price' => $item->product->default_price ?? 0,
                    'tax_percentage' => 0,
                    'notes' => $item->specifications,
                ];
            })->toArray();
        }
    }

    public function addItem()
    {
        $this->items[] = [
            'product_id' => '',
            'quantity' => 1,
            'unit_price' => 0,
            'tax_percentage' => 0,
            'notes' => '',
        ];
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function save()
    {
        $this->validate();

        // V56-CRITICAL-NEW-01 FIX: Re-authorize on save() to prevent CSRF/timing attacks
        // where authorization might change between mount() and save() calls
        if ($this->quotation) {
            $this->authorize('update', $this->quotation);
        } else {
            $this->authorize('create', SupplierQuotation::class);
        }

        // V56-CRITICAL-NEW-01 FIX: Wrap all database operations in a transaction
        // to ensure data consistency (quotation + items are saved atomically)
        try {
            DB::transaction(function () {
                $data = [
                    'requisition_id' => $this->requisition_id,
                    'supplier_id' => $this->supplier_id,
                    'quotation_date' => $this->quotation_date,
                    'valid_until' => $this->valid_until,
                    'payment_terms' => $this->payment_terms,
                    'delivery_terms' => $this->delivery_terms,
                    'lead_time_days' => $this->lead_time_days,
                    'notes' => $this->notes,
                    'terms_conditions' => $this->terms_conditions,
                    'status' => 'pending',
                ];

                if ($this->quotation) {
                    $this->quotation->update($data);
                    $this->quotation->items()->delete();
                } else {
                    $this->quotation = SupplierQuotation::create($data);
                }

                // Save items within the same transaction
                foreach ($this->items as $item) {
                    $this->quotation->items()->create([
                        'product_id' => $item['product_id'],
                        'qty' => $item['quantity'],
                        'unit_cost' => $item['unit_price'],
                        'tax_rate' => $item['tax_percentage'] ?? 0,
                        'notes' => $item['notes'] ?? null,
                    ]);
                }
            });

            session()->flash('success', __('Quotation saved successfully'));
            $this->redirectRoute('app.purchases.quotations.index', navigate: true);
        } catch (\Exception $e) {
            session()->flash('error', __('Error saving quotation'));
        }
    }

    public function render()
    {
        return view('livewire.purchases.quotations.form', [
            'requisitions' => PurchaseRequisition::where('status', 'approved')
                ->where('is_converted', false)
                ->orderBy('created_at', 'desc')
                ->get(),
            'suppliers' => Supplier::active()->orderBy('name')->get(),
            'products' => Product::active()->orderBy('name')->get(),
        ]);
    }
}
