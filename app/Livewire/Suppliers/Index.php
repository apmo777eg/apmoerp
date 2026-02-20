<?php

declare(strict_types=1);

namespace App\Livewire\Suppliers;

use App\Models\Supplier;
use App\Traits\HasExport;
use App\Traits\HasSortableColumns;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Url;
use App\Livewire\BaseComponent as Component;
use Livewire\WithPagination;

class Index extends Component
{
    use AuthorizesRequests;
    use HasExport;
    use HasSortableColumns;
    use WithPagination;

    #[Url]
    public string $search = '';

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    /**
     * Define allowed sort columns to prevent SQL injection.
     */
    protected function allowedSortColumns(): array
    {
        return ['id', 'name', 'email', 'phone', 'created_at', 'updated_at'];
    }

    public function mount(): void
    {
        $this->authorize('suppliers.view');
        $this->initializeExport('suppliers');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        $this->authorize('suppliers.manage');
        Supplier::findOrFail($id)->delete();
        session()->flash('success', __('Supplier deleted successfully'));
    }

    public function render()
    {
        $suppliers = Supplier::query()
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")
                ->orWhere('phone', 'like', "%{$this->search}%"))
            ->orderBy($this->getSortField(), $this->getSortDirection())
            ->paginate(15);

        return view('livewire.suppliers.index', [
            'suppliers' => $suppliers,
        ])->layout('layouts.app', ['title' => __('Suppliers')]);
    }

    public function export()
    {
        $this->authorize('suppliers.manage');

        $data = Supplier::query()
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")
                ->orWhere('phone', 'like', "%{$this->search}%"))
            ->orderBy($this->getSortField(), $this->getSortDirection())
            ->get();

        return $this->performExport('suppliers', $data, __('Suppliers Export'));
    }
}
