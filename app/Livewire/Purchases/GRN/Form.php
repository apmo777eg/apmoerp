<?php

namespace App\Livewire\Purchases\GRN;

use App\Models\GoodsReceivedNote;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use App\Livewire\BaseComponent as Component;
#[Layout('layouts.app')]
class Form extends Component
{
    use AuthorizesRequests;

    public ?GoodsReceivedNote $grn = null;

    public ?int $grnId = null;

    public ?int $purchaseId = null;

    public ?string $receivedDate = null;

    public ?int $inspectorId = null;

    public ?string $notes = null;

    /**
     * UI items array (NOT a DB schema):
     * We intentionally keep it simple and then map it to canonical DB columns.
     */
    public array $items = [];

    public function mount(?int $id = null): void
    {
        if ($id) {
            $this->authorize('grn.update');

            $user = auth()->user();
            $this->grnId = $id;

            $this->grn = GoodsReceivedNote::with('items.product')
                ->when($user?->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
                ->findOrFail($id);

            if ($user?->branch_id && $this->grn->branch_id !== $user->branch_id) {
                abort(403);
            }

            $this->loadGRN();
        } else {
            $this->authorize('grn.create');
            $this->receivedDate = date('Y-m-d');
        }
    }

    /**
     * Livewire hook: reload PO items when purchaseId changes.
     */
    public function updatedPurchaseId(): void
    {
        $this->loadPOItems();
    }

    protected function loadGRN(): void
    {
        $this->purchaseId = $this->grn->purchase_id;
        $this->receivedDate = optional($this->grn->received_date)->format('Y-m-d');
        $this->inspectorId = $this->grn->inspected_by;
        $this->notes = $this->grn->notes;

        $this->items = $this->grn->items->map(function ($item) {
            $damaged = 0;
            $defective = 0;
            // In this DB-first version we store ONLY rejected_quantity. Split is optional in UI.

            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name ?? '',
                'purchase_item_id' => $item->purchase_item_id,

                'expected_quantity' => (float) ($item->expected_quantity ?? 0),
                'received_quantity' => (float) ($item->received_quantity ?? 0),
                'quantity_damaged' => $damaged,
                'quantity_defective' => $defective,

                'unit_cost' => (float) ($item->unit_cost ?? 0),
                'item_condition' => $item->item_condition ?? 'good',
                'rejection_reason' => $item->rejection_reason ?? '',
                'inspection_notes' => $item->inspection_notes ?? '',
            ];
        })->toArray();
    }

    public function loadPOItems(): void
    {
        if (! $this->purchaseId) {
            $this->items = [];
            return;
        }

        $purchase = Purchase::with('items.product')->findOrFail($this->purchaseId);

        $this->items = $purchase->items->map(function ($item) {
            $qty = decimal_float($item->quantity ?? 0, 4);

            return [
                'purchase_item_id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name ?? '',

                'expected_quantity' => $qty,
                'received_quantity' => $qty,
                'quantity_damaged' => 0,
                'quantity_defective' => 0,

                'unit_cost' => decimal_float($item->unit_price ?? 0, 4),
                'item_condition' => 'good',
                'rejection_reason' => '',
                'inspection_notes' => '',
            ];
        })->toArray();
    }

    public function calculateDiscrepancies(): array
    {
        $discrepancies = [];

        foreach ($this->items as $index => $item) {
            $ordered = (string) ($item['expected_quantity'] ?? '0');
            $received = (string) ($item['received_quantity'] ?? '0');
            $damaged = (string) ($item['quantity_damaged'] ?? '0');
            $defective = (string) ($item['quantity_defective'] ?? '0');

            if (bccomp($received, $ordered, 4) !== 0) {
                $discrepancies[] = "Item {$index}: Quantity mismatch";
            }

            if (bccomp($damaged, '0', 4) > 0 || bccomp($defective, '0', 4) > 0) {
                $discrepancies[] = "Item {$index}: Quality issues";
            }
        }

        return $discrepancies;
    }

    private function validateGRN(): void
    {
        $branchId = auth()->user()?->branch_id;

        $this->validate([
            'purchaseId' => ['required', new \App\Rules\BranchScopedExists('purchases', 'id', $branchId)],
            'receivedDate' => 'required|date|before_or_equal:today',
            'inspectorId' => 'nullable|exists:users,id',

            'items' => 'required|array|min:1',
            'items.*.product_id' => ['required', new \App\Rules\BranchScopedExists('products', 'id', $branchId)],
            'items.*.expected_quantity' => 'required|numeric|min:0',
            'items.*.received_quantity' => 'required|numeric|min:0',
            'items.*.quantity_damaged' => 'nullable|numeric|min:0',
            'items.*.quantity_defective' => 'nullable|numeric|min:0',
            'items.*.item_condition' => 'required|in:good,damaged,defective',
        ]);
    }

    private function saveGRNRecord(string $status): void
    {
        $data = [
            'purchase_id' => $this->purchaseId,
            'received_date' => $this->receivedDate,
            'inspected_by' => $this->inspectorId,
            'notes' => $this->notes,
            'status' => $status,
        ];

        if ($this->grn) {
            $this->grn->update($data);
        } else {
            $this->grn = GoodsReceivedNote::create($data);
            $this->grnId = $this->grn->id;
        }
    }

    private function saveGRNItems(): void
    {
        $this->grn->items()->delete();

        foreach ($this->items as $item) {
            $expected = (string) ($item['expected_quantity'] ?? '0');
            $received = (string) ($item['received_quantity'] ?? '0');
            $damaged = (string) ($item['quantity_damaged'] ?? '0');
            $defective = (string) ($item['quantity_defective'] ?? '0');

            $rejected = bcadd($damaged, $defective, 4);
            $acceptedCalc = bcsub($received, $rejected, 4);
            $accepted = bccomp($acceptedCalc, '0', 4) > 0 ? $acceptedCalc : '0';

            $this->grn->items()->create([
                'branch_id' => $this->grn->branch_id,
                'product_id' => $item['product_id'],
                'purchase_item_id' => $item['purchase_item_id'] ?? null,

                'unit_cost' => $item['unit_cost'] ?? 0,
                'expected_quantity' => $expected,
                'received_quantity' => $received,
                'rejected_quantity' => $rejected,
                'accepted_quantity' => $accepted,

                'item_condition' => $item['item_condition'] ?? 'good',
                'rejection_reason' => $item['rejection_reason'] ?? null,
                'inspection_notes' => $item['inspection_notes'] ?? null,
            ]);
        }
    }

    public function save(): void
    {
        $this->validateGRN();

        DB::transaction(function () {
            $this->saveGRNRecord(GoodsReceivedNote::STATUS_DRAFT);
            $this->saveGRNItems();
        });

        session()->flash('success', __('GRN saved as draft.'));
        $this->redirectRoute('app.purchases.grn.index', navigate: true);
    }

    public function submit(): void
    {
        $this->validateGRN();

        DB::transaction(function () {
            $this->saveGRNRecord(GoodsReceivedNote::STATUS_PENDING);
            $this->saveGRNItems();
        });

        session()->flash('success', __('GRN submitted for inspection.'));
        $this->redirectRoute('app.purchases.grn.index', navigate: true);
    }

    public function render()
    {
        $user = auth()->user();

        $purchases = Purchase::query()
            ->with('supplier')
            ->when($user?->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        $inspectors = User::query()
            ->when($user?->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
            ->orderBy('name')
            ->get();

        return view('livewire.purchases.grn.form', [
            'purchases' => $purchases,
            'inspectors' => $inspectors,
        ]);
    }
}
