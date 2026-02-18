<?php

declare(strict_types=1);

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

    public ?int $requisition_id = null;

    public array $comparisonData = [];

    public bool $showComparison = false;

    public function mount(?int $requisition = null): void
    {
        $this->authorize('purchases.view');

        if ($requisition) {
            $this->requisition_id = $requisition;
            $this->loadComparison();
        }
    }

    public function updatedRequisitionId(): void
    {
        $this->loadComparison();
    }

    public function loadComparison(): void
    {
        $this->showComparison = false;
        $this->comparisonData = [];

        if (! $this->requisition_id) {
            return;
        }

        $quotations = SupplierQuotation::with(['supplier', 'items.product'])
            ->where('requisition_id', $this->requisition_id)
            ->whereIn('status', ['pending', 'accepted'])
            ->get();

        if ($quotations->isEmpty()) {
            return;
        }

        $this->comparisonData = [
            'requisition' => PurchaseRequisition::find($this->requisition_id),
            'quotations' => $quotations,
            'matrix' => $this->buildComparisonMatrix($quotations),
            'bestQuotation' => $this->findBestQuotation($quotations),
        ];

        $this->showComparison = true;
    }

    protected function buildComparisonMatrix($quotations): array
    {
        $products = [];

        foreach ($quotations as $quotation) {
            foreach ($quotation->items as $item) {
                $productId = $item->product_id;

                if (! isset($products[$productId])) {
                    $products[$productId] = [
                        'product' => $item->product,
                        'quotations' => [],
                    ];
                }

                $products[$productId]['quotations'][$quotation->id] = [
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'tax_percent' => $item->tax_percent,
                    'line_total' => $item->line_total,
                    'notes' => $item->notes,
                ];
            }
        }

        return $products;
    }

    protected function findBestQuotation($quotations): ?SupplierQuotation
    {
        // Simple scoring based on grand total
        return $quotations->sortBy(function ($quotation) {
            return $quotation->total_amount ?? 0;
        })->first();
    }

    public function acceptBestQuotation(): void
    {
        $this->authorize('purchases.manage');

        if (! $this->comparisonData || ! isset($this->comparisonData['bestQuotation'])) {
            return;
        }

        /** @var SupplierQuotation $bestQuotation */
        $bestQuotation = $this->comparisonData['bestQuotation'];

        // Accept best quotation
        $bestQuotation->accept(function_exists('actual_user_id') ? actual_user_id() : auth()->id());

        // Reject others
        SupplierQuotation::where('requisition_id', $this->requisition_id)
            ->where('id', '!=', $bestQuotation->id)
            ->where('status', 'pending')
            ->get()
            ->each(function (SupplierQuotation $q): void {
                $q->reject(__('Better offer accepted'), function_exists('actual_user_id') ? actual_user_id() : auth()->id());
            });

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => __('Best quotation accepted successfully'),
        ]);

        $this->loadComparison();
    }

    public function render()
    {
        $requisitions = PurchaseRequisition::query()
            ->where('status', 'approved')
            ->whereHas('quotations')
            ->orderByDesc('id')
            ->get(['id', 'code', 'subject']);

        return view('livewire.purchases.quotations.compare', [
            'requisitions' => $requisitions,
        ]);
    }
}
