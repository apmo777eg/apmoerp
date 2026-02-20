<?php

declare(strict_types=1);

namespace App\Livewire\Purchases\Quotations;

use App\Models\SupplierQuotation;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use App\Livewire\BaseComponent as Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    protected array $allowedSortFields = [
        'created_at',
        'reference_number',
        'quotation_date',
        'valid_until',
        'status',
        'total_amount',
    ];

    public function mount(): void
    {
        $this->authorize('purchases.view');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if (! in_array($field, $this->allowedSortFields, true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function accept(int $id): void
    {
        $this->authorize('purchases.manage');

        $quotation = SupplierQuotation::findOrFail($id);

        if ($quotation->isExpired()) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => __('Cannot accept expired quotation'),
            ]);

            return;
        }

        $quotation->accept(function_exists('actual_user_id') ? actual_user_id() : auth()->id());

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => __('Quotation accepted successfully'),
        ]);
    }

    public function reject(int $id, string $reason = ''): void
    {
        $this->authorize('purchases.manage');

        $quotation = SupplierQuotation::findOrFail($id);
        $quotation->reject($reason ?: __('Rejected'), function_exists('actual_user_id') ? actual_user_id() : auth()->id());

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => __('Quotation rejected'),
        ]);
    }

    public function render()
    {
        $query = SupplierQuotation::query()
            ->with(['supplier', 'requisition']);

        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                    ->orWhereHas('supplier', function ($s) use ($search) {
                        $s->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('requisition', function ($r) use ($search) {
                        $r->where('code', 'like', "%{$search}%")
                          ->orWhere('subject', 'like', "%{$search}%");
                    });
            });
        }

        if ($this->status) {
            if ($this->status === 'expired') {
                $query->expired();
            } else {
                $query->where('status', $this->status);
            }
        }

        if (! in_array($this->sortField, $this->allowedSortFields, true)) {
            $this->sortField = 'created_at';
        }

        $quotations = $query
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);

        $statistics = [
            'total' => SupplierQuotation::count(),
            'pending' => SupplierQuotation::where('status', 'pending')->count(),
            'accepted' => SupplierQuotation::where('status', 'accepted')->count(),
            'rejected' => SupplierQuotation::where('status', 'rejected')->count(),
            'expired' => SupplierQuotation::expired()->count(),
        ];

        return view('livewire.purchases.quotations.index', [
            'quotations' => $quotations,
            'statistics' => $statistics,
        ]);
    }
}
