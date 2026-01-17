<?php

namespace App\Livewire\Purchases\GRN;

use App\Models\GoodsReceivedNote;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Inspection extends Component
{
    use AuthorizesRequests, WithFileUploads;

    public GoodsReceivedNote $grn;

    public array $inspectionData = [];

    public array $photos = [];

    public ?string $inspectorNotes = null;

    public ?string $finalDecision = 'pending';

    // Inspection criteria checklist
    public array $checklist = [
        'quantity_verified' => false,
        'quality_verified' => false,
        'packaging_intact' => false,
        'documentation_complete' => false,
        'no_visible_damage' => false,
    ];

    public function mount(int $id): void
    {
        $this->authorize('grn.inspect');

        $this->grn = GoodsReceivedNote::with(['items.product', 'purchaseOrder', 'supplier'])
            ->findOrFail($id);

        // Initialize inspection data for each item
        foreach ($this->grn->items as $item) {
            $this->inspectionData[$item->id] = [
                'pass' => null,
                'defect_category' => '',
                'defect_description' => '',
                'notes' => '',
            ];
        }
    }

    public function updateChecklistItem(string $item, bool $value): void
    {
        $this->checklist[$item] = $value;
    }

    public function updateInspection(int $itemId, string $field, $value): void
    {
        if (! isset($this->inspectionData[$itemId])) {
            $this->inspectionData[$itemId] = [];
        }

        $this->inspectionData[$itemId][$field] = $value;
    }

    public function acceptGRN(): ?RedirectResponse
    {
        $this->authorize('grn.approve');

        $this->validate([
            'inspectorNotes' => 'nullable|string|max:1000',
        ]);

        // Check if all checklist items are verified
        $allVerified = collect($this->checklist)->every(fn ($item) => $item === true);

        if (! $allVerified) {
            session()->flash('error', __('Please complete all inspection checklist items before accepting.'));

            return null;
        }

        // Update GRN status
        // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
        $this->grn->update([
            'status' => 'approved',
            'inspection_notes' => $this->inspectorNotes,
            'inspected_at' => now(),
            'inspected_by' => actual_user_id(),
        ]);

        // Update item inspection results
        foreach ($this->inspectionData as $itemId => $data) {
            $item = $this->grn->items()->find($itemId);
            if ($item) {
                $item->update([
                    'inspection_pass' => $data['pass'] ?? true,
                    'inspection_notes' => $data['notes'] ?? null,
                ]);
            }
        }

        session()->flash('success', __('GRN inspection completed and approved.'));

        $this->redirectRoute('app.purchases.grn.index', navigate: true);
    }

    public function rejectGRN(): ?RedirectResponse
    {
        $this->authorize('grn.reject');

        $this->validate([
            'inspectorNotes' => 'required|string|max:1000',
        ]);

        // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
        $this->grn->update([
            'status' => 'rejected',
            'rejection_reason' => $this->inspectorNotes,
            'inspected_at' => now(),
            'inspected_by' => actual_user_id(),
        ]);

        session()->flash('success', __('GRN rejected with inspection notes.'));

        $this->redirectRoute('app.purchases.grn.index', navigate: true);
    }

    public function partialAccept(): ?RedirectResponse
    {
        $this->authorize('grn.approve');

        $this->validate([
            'inspectorNotes' => 'nullable|string|max:1000',
        ]);

        // Check which items passed/failed
        $passedItems = collect($this->inspectionData)
            ->filter(fn ($data) => ($data['pass'] ?? false) === true)
            ->count();

        $totalItems = $this->grn->items->count();

        if ($passedItems === 0) {
            session()->flash('error', __('No items passed inspection. Please reject the GRN instead.'));

            return null;
        }

        if ($passedItems === $totalItems) {
            session()->flash('error', __('All items passed. Please use full accept instead.'));

            return null;
        }

        // Update GRN status to partial
        // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
        $this->grn->update([
            'status' => 'partial',
            'inspection_notes' => $this->inspectorNotes,
            'inspected_at' => now(),
            'inspected_by' => actual_user_id(),
        ]);

        // Update individual item results
        foreach ($this->inspectionData as $itemId => $data) {
            $item = $this->grn->items()->find($itemId);
            if ($item) {
                $item->update([
                    'inspection_pass' => $data['pass'] ?? false,
                    'defect_category' => $data['defect_category'] ?? null,
                    'defect_description' => $data['defect_description'] ?? null,
                    'inspection_notes' => $data['notes'] ?? null,
                ]);
            }
        }

        session()->flash('success', __('GRN marked as partial acceptance.'));

        $this->redirectRoute('app.purchases.grn.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.purchases.grn.inspection');
    }
}
