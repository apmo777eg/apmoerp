<?php

namespace App\Livewire\Purchases\Quotations;

use App\Models\SupplierQuotation;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use AuthorizesRequests, WithPagination;

    #[Url]
    public $search = '';

    #[Url]
    public $statusFilter = '';

    public $sortField = 'created_at';

    public $sortDirection = 'desc';

    public function mount(): void
    {
        $this->authorize('purchases.view');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function accept($id)
    {
        $this->authorize('purchases.manage');

        $quotation = SupplierQuotation::findOrFail($id);

        if ($quotation->isExpired()) {
            session()->flash('error', __('Cannot accept expired quotation'));

            return;
        }

        // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
        $quotation->update([
            'status' => 'accepted',
            'accepted_at' => now(),
            'accepted_by' => actual_user_id(),
        ]);

        session()->flash('success', __('Quotation accepted successfully'));
    }

    public function reject($id, $reason = null)
    {
        $this->authorize('purchases.manage');

        $quotation = SupplierQuotation::findOrFail($id);

        // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
        $quotation->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejected_by' => actual_user_id(),
            'rejection_reason' => $reason,
        ]);

        session()->flash('success', __('Quotation rejected successfully'));
    }

    public function delete($id)
    {
        $this->authorize('purchases.manage');

        SupplierQuotation::findOrFail($id)->delete();

        session()->flash('success', __('Quotation deleted successfully'));
    }

    public function render()
    {
        $query = SupplierQuotation::with(['supplier', 'requisition', 'createdBy'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('quotation_number', 'like', '%'.$this->search.'%')
                        ->orWhereHas('supplier', function ($q) {
                            $q->where('name', 'like', '%'.$this->search.'%');
                        })
                        ->orWhereHas('requisition', function ($q) {
                            $q->where('requisition_number', 'like', '%'.$this->search.'%');
                        });
                });
            })
            ->when($this->statusFilter, function ($query) {
                if ($this->statusFilter === 'expired') {
                    $query->where('valid_until', '<', now())
                        ->where('status', 'pending');
                } else {
                    $query->where('status', $this->statusFilter);
                }
            })
            ->orderBy($this->sortField, $this->sortDirection);

        $quotations = $query->paginate(15);

        // Statistics
        $stats = [
            'total' => SupplierQuotation::count(),
            'pending' => SupplierQuotation::where('status', 'pending')->count(),
            'accepted' => SupplierQuotation::where('status', 'accepted')->count(),
            'rejected' => SupplierQuotation::where('status', 'rejected')->count(),
            'expired' => SupplierQuotation::where('status', 'pending')
                ->where('valid_until', '<', now())
                ->count(),
        ];

        return view('livewire.purchases.quotations.index', [
            'quotations' => $quotations,
            'stats' => $stats,
        ]);
    }
}
