<?php

declare(strict_types=1);

namespace App\Livewire\Purchases\Requisitions;

use App\Models\CostCenter;
use App\Models\Department;
use App\Models\Product;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionItem;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use App\Livewire\BaseComponent as Component;
#[Layout('layouts.app')]
class Form extends Component
{
    use \App\Http\Requests\Traits\HasMultilingualValidation;
    use AuthorizesRequests;

    public ?PurchaseRequisition $requisition = null;

    public bool $isEdit = false;

    public string $subject = '';

    public string $priority = 'normal';

    public ?string $required_date = null;

    public string $justification = '';

    public string $notes = '';

    public $department_id = null;

    public $cost_center_id = null;

    public array $items = [];

    public array $products = [];

    public function getRules(): array
    {
        $branchId = (int) ($this->requisition?->branch_id ?? current_branch_id() ?? 0);
        $branchId = $branchId > 0 ? $branchId : null;

        return [
            'subject' => $this->multilingualString(required: true, max: 255),
            'priority' => 'required|in:low,normal,high,urgent',
            'required_date' => 'nullable|date',
            'justification' => $this->unicodeText(required: false),
            'notes' => $this->unicodeText(required: false),
            'department_id' => 'nullable|exists:departments,id',
            'cost_center_id' => 'nullable|exists:cost_centers,id',
            'items' => 'required|array|min:1',
            // Branch-aware product validation
            'items.*.product_id' => ['required', new \App\Rules\BranchScopedExists('products', 'id', $branchId)],
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.estimated_price' => 'required|numeric|min:0',
            'items.*.specifications' => $this->unicodeText(required: false),
        ];
    }

    public function mount(?int $requisition = null): void
    {
        if ($requisition) {
            $this->authorize('purchases.requisitions.manage');
            $this->isEdit = true;
            $this->requisition = PurchaseRequisition::with(['items.product'])->findOrFail($requisition);
            $this->loadRequisition();
        } else {
            $this->authorize('purchases.requisitions.create');
            $this->addItem();
        }

        $this->loadProducts();
    }

    protected function loadRequisition(): void
    {
        if (! $this->requisition) {
            return;
        }

        $this->subject = (string) ($this->requisition->subject ?? '');
        $this->priority = (string) ($this->requisition->priority ?? 'normal');
        $this->required_date = $this->requisition->required_date?->format('Y-m-d');
        $this->justification = (string) ($this->requisition->justification ?? '');
        $this->notes = (string) ($this->requisition->notes ?? '');
        $this->department_id = $this->requisition->department_id;
        $this->cost_center_id = $this->requisition->cost_center_id;

        $this->items = $this->requisition->items->map(function (PurchaseRequisitionItem $item) {
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name ?? '',
                'quantity' => (float) $item->quantity,
                'estimated_price' => (float) $item->estimated_price,
                'specifications' => (string) ($item->specifications ?? ''),
            ];
        })->toArray();

        if (empty($this->items)) {
            $this->addItem();
        }
    }

    protected function loadProducts(): void
    {
        $branchId = (int) ($this->requisition?->branch_id ?? current_branch_id() ?? 0);

        if ($branchId <= 0) {
            $this->products = [];

            return;
        }

        $this->products = Product::query()
            ->where('branch_id', $branchId)
            ->where('status', 'active')
            ->select('id', 'name', 'sku', 'default_price')
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    public function addItem(): void
    {
        $this->items[] = [
            'product_id' => null,
            'product_name' => '',
            'quantity' => 1,
            'estimated_price' => 0,
            'specifications' => '',
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

    public function updateProductPrice(int $index): void
    {
        $productId = $this->items[$index]['product_id'] ?? null;
        if (! $productId) {
            return;
        }

        $product = Product::find($productId);
        if (! $product) {
            return;
        }

        $this->items[$index]['estimated_price'] = $product->default_price ?? 0;
        $this->items[$index]['product_name'] = $product->name;
    }

    public function save(): void
    {
        $this->persist('draft');
    }

    public function submit(): void
    {
        $this->authorize('purchases.requisitions.create');
        $this->persist('pending');
    }

    protected function persist(string $status): void
    {
        $this->validate($this->getRules());
        $wasEdit = $this->isEdit;


        $branchId = (int) ($this->requisition?->branch_id ?? current_branch_id() ?? 0);
        if ($branchId <= 0) {
            $this->addError('branch_id', __('Please select a branch first.'));

            return;
        }

        // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
        $userId = function_exists('actual_user_id') ? actual_user_id() : (auth()->id() ?? null);

        // Compute estimated_total from items
        $estimatedTotal = 0;
        foreach ($this->items as $item) {
            $qty = (float) ($item['quantity'] ?? 0);
            $price = (float) ($item['estimated_price'] ?? 0);
            $estimatedTotal += $qty * $price;
        }

        $data = [
            'branch_id' => $branchId,
            'requested_by' => $userId,
            'subject' => $this->subject,
            'priority' => $this->priority,
            'required_date' => $this->required_date ?: null,
            'justification' => $this->justification ?: null,
            'notes' => $this->notes ?: null,
            'department_id' => $this->department_id ?: null,
            'cost_center_id' => $this->cost_center_id ?: null,
            'estimated_total' => $estimatedTotal,
            'status' => $status,
        ];

        DB::transaction(function () use ($data, $branchId, $userId): void {
            if ($this->isEdit && $this->requisition) {
                $this->requisition->update($data);
                $this->requisition->items()->delete();
            } else {
                $this->requisition = PurchaseRequisition::create($data);
                $this->isEdit = true;
            }

            foreach ($this->items as $item) {
                PurchaseRequisitionItem::create([
                    'requisition_id' => $this->requisition->id,
                    'branch_id' => $branchId,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'estimated_price' => $item['estimated_price'],
                    'specifications' => $item['specifications'] ?? null,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
            }
        });

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $wasEdit ? __('Purchase requisition updated successfully') : __('Purchase requisition created successfully'),
        ]);

        $this->redirectRoute('app.purchases.requisitions.index', navigate: true);
    }

    public function render()
    {
        $branchId = (int) ($this->requisition?->branch_id ?? current_branch_id() ?? 0);

        return view('livewire.purchases.requisitions.form', [
            'departments' => $branchId > 0
                ? Department::query()->active()->orderBy('name')->get(['id', 'name', 'name_ar'])
                : collect(),
            'costCenters' => $branchId > 0
                ? CostCenter::query()->active()->orderBy('name')->get(['id', 'name', 'name_ar'])
                : collect(),
        ]);
    }
}
