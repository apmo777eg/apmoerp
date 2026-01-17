<?php

namespace App\Livewire\Purchases\Quotations;

use App\Models\PurchaseRequisition;
use App\Models\SupplierQuotation;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Compare extends Component
{
    use AuthorizesRequests;

    public $requisition_id = '';

    public $quotations = [];

    public $comparisonData = [];

    public function mount()
    {
        $this->authorize('view', SupplierQuotation::class);
    }

    public function updatedRequisitionId()
    {
        $this->loadQuotations();
    }

    public function loadQuotations()
    {
        if (! $this->requisition_id) {
            $this->quotations = [];
            $this->comparisonData = [];

            return;
        }

        $this->quotations = SupplierQuotation::with(['supplier', 'items.product'])
            ->where('requisition_id', $this->requisition_id)
            ->whereIn('status', ['pending', 'accepted'])
            ->where('valid_until', '>=', now())
            ->get();

        $this->buildComparisonMatrix();
    }

    protected function buildComparisonMatrix()
    {
        if ($this->quotations->isEmpty()) {
            $this->comparisonData = [];

            return;
        }

        // Build comparison matrix
        $matrix = [];

        foreach ($this->quotations as $quotation) {
            foreach ($quotation->items as $item) {
                $productId = $item->product_id;

                if (! isset($matrix[$productId])) {
                    $matrix[$productId] = [
                        'product' => $item->product,
                        'quotations' => [],
                    ];
                }

                $matrix[$productId]['quotations'][$quotation->id] = [
                    'quantity' => $item->qty,
                    'unit_price' => $item->unit_cost,
                    'tax_percentage' => $item->tax_rate,
                    'total' => $item->qty * $item->unit_cost * (1 + ($item->tax_rate / 100)),
                ];
            }
        }

        $this->comparisonData = $matrix;
    }

    public function acceptBest()
    {
        $this->authorize('update', SupplierQuotation::class);

        if ($this->quotations->isEmpty()) {
            session()->flash('error', __('No quotations to compare'));

            return;
        }

        // Find quotation with lowest total price
        $bestQuotation = $this->quotations->sortBy(function ($quotation) {
            return $quotation->items->sum(function ($item) {
                return $item->qty * $item->unit_cost * (1 + ($item->tax_rate / 100));
            });
        })->first();

        if ($bestQuotation) {
            // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
            $bestQuotation->update([
                'status' => 'accepted',
                'accepted_at' => now(),
                'accepted_by' => actual_user_id(),
            ]);

            // Reject other quotations
            // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
            $this->quotations->where('id', '!=', $bestQuotation->id)->each(function ($quotation) {
                $quotation->update([
                    'status' => 'rejected',
                    'rejected_at' => now(),
                    'rejected_by' => actual_user_id(),
                    'rejection_reason' => 'Better quotation selected',
                ]);
            });

            session()->flash('success', __('Best quotation accepted and others rejected'));
            $this->loadQuotations();
        }
    }

    public function render()
    {
        return view('livewire.purchases.quotations.compare', [
            'requisitions' => PurchaseRequisition::where('status', 'approved')
                ->whereHas('quotations')
                ->orderBy('created_at', 'desc')
                ->get(),
        ]);
    }
}
