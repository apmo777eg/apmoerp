<?php

declare(strict_types=1);

namespace App\Livewire\Purchases\Requisitions;

use App\Models\Product;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionItem;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Form extends Component
{
    use \App\Http\Requests\Traits\HasMultilingualValidation;
    use AuthorizesRequests;

    public ?PurchaseRequisition $requisition = null;

    public bool $isEdit = false;

    public string $subject = '';

    public string $priority = 'medium';

    public string $justification = '';

    public string $required_by = '';

    public string $notes = '';

    public ?int $department_id = null;

    public ?int $cost_center_id = null;

    public array $items = [];

    public array $products = [];

    public function getRules(): array
    {
        return [
            'subject' => $this->multilingualString(required: true, max: 255),
            'priority' => 'required|in:low,medium,high,urgent',
            'justification' => $this->unicodeText(required: true),
            'required_by' => 'required|date|after:today',
            'notes' => $this->unicodeText(required: false),
            'department_id' => 'nullable|exists:departments,id',
            'cost_center_id' => 'nullable|exists:cost_centers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.specifications' => $this->unicodeText(required: false),
        ];
    }

    protected array $rules = [
        'subject' => 'required|string|max:255',
        'priority' => 'required|in:low,medium,high,urgent',
        'justification' => 'required|string',
        'required_by' => 'required|date|after:today',
        'notes' => 'nullable|string',
        'department_id' => 'nullable|exists:departments,id',
        'cost_center_id' => 'nullable|exists:cost_centers,id',
        'items' => 'required|array|min:1',
        'items.*.product_id' => 'required|exists:products,id',
        'items.*.quantity' => 'required|numeric|min:1',
        'items.*.unit_price' => 'required|numeric|min:0',
        'items.*.specifications' => 'nullable|string',
    ];

    public function mount(?int $requisition = null): void
    {
        if ($requisition) {
            $this->authorize('purchases.requisitions.create');
            $this->isEdit = true;
            $this->requisition = PurchaseRequisition::with('items')->findOrFail($requisition);
            $this->loadRequisition();
        } else {
            $this->authorize('purchases.requisitions.create');
            $this->addItem();
        }

        $this->loadProducts();
    }

    protected function loadRequisition(): void
    {
        $this->subject = $this->requisition->subject;
        $this->priority = $this->requisition->priority;
        $this->justification = $this->requisition->justification;
        $this->required_by = $this->requisition->required_by?->format('Y-m-d') ?? '';
        $this->notes = $this->requisition->notes ?? '';
        $this->department_id = $this->requisition->department_id;
        $this->cost_center_id = $this->requisition->cost_center_id;

        $this->items = $this->requisition->items->map(function ($item) {
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product->name ?? '',
                'quantity' => $item->qty,
                'unit_price' => $item->estimated_unit_cost,
                'specifications' => $item->specifications ?? '',
            ];
        })->toArray();
    }

    protected function loadProducts(): void
    {
        $this->products = Product::where('branch_id', auth()->user()->branch_id)
            ->where('status', 'active')
            ->select('id', 'name', 'sku', 'default_price')
            ->get()
            ->toArray();
    }

    public function addItem(): void
    {
        $this->items[] = [
            'product_id' => null,
            'product_name' => '',
            'quantity' => 1,
            'unit_price' => 0,
            'specifications' => '',
        ];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function updateProductPrice(int $index): void
    {
        if (isset($this->items[$index]['product_id'])) {
            $product = Product::find($this->items[$index]['product_id']);
            if ($product) {
                $this->items[$index]['unit_price'] = $product->default_price ?? 0;
                $this->items[$index]['product_name'] = $product->name;
            }
        }
    }

    public function save(): RedirectResponse
    {
        $this->validate($this->getRules());

        $branchId = auth()->user()->branch_id;
        // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
        $userId = actual_user_id();

        $data = [
            'branch_id' => $branchId,
            'employee_id' => $userId,
            'subject' => $this->subject,
            'priority' => $this->priority,
            'justification' => $this->justification,
            'required_by' => $this->required_by,
            'notes' => $this->notes,
            'department_id' => $this->department_id,
            'cost_center_id' => $this->cost_center_id,
            'status' => 'draft',
        ];

        if ($this->isEdit && $this->requisition) {
            $this->requisition->update($data);
            // Delete existing items and recreate
            $this->requisition->items()->delete();
        } else {
            $this->requisition = PurchaseRequisition::create($data);
        }

        // Create items
        foreach ($this->items as $item) {
            PurchaseRequisitionItem::create([
                'requisition_id' => $this->requisition->id,
                'product_id' => $item['product_id'],
                'qty' => $item['quantity'],
                'estimated_unit_cost' => $item['unit_price'],
                'specifications' => $item['specifications'] ?? null,
            ]);
        }

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $this->isEdit ? __('Purchase requisition updated successfully') : __('Purchase requisition created successfully'),
        ]);

        $this->redirectRoute('app.purchases.requisitions.index', navigate: true);
    }

    public function submit(): RedirectResponse
    {
        $this->validate($this->getRules());

        $branchId = auth()->user()->branch_id;
        // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
        $userId = actual_user_id();

        $data = [
            'branch_id' => $branchId,
            'employee_id' => $userId,
            'subject' => $this->subject,
            'priority' => $this->priority,
            'justification' => $this->justification,
            'required_by' => $this->required_by,
            'notes' => $this->notes,
            'department_id' => $this->department_id,
            'cost_center_id' => $this->cost_center_id,
            'status' => 'pending_approval',
        ];

        if ($this->isEdit && $this->requisition) {
            $this->requisition->update($data);
            // Delete existing items and recreate
            $this->requisition->items()->delete();
        } else {
            $this->requisition = PurchaseRequisition::create($data);
        }

        // Create items
        foreach ($this->items as $item) {
            PurchaseRequisitionItem::create([
                'requisition_id' => $this->requisition->id,
                'product_id' => $item['product_id'],
                'qty' => $item['quantity'],
                'estimated_unit_cost' => $item['unit_price'],
                'specifications' => $item['specifications'] ?? null,
            ]);
        }

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => __('Purchase requisition submitted for approval'),
        ]);

        $this->redirectRoute('app.purchases.requisitions.index', navigate: true);
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.purchases.requisitions.form');
    }
}
