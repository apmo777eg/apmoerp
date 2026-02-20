<?php

declare(strict_types=1);

namespace App\Livewire\Shared;

use Livewire\Attributes\Url;
use App\Livewire\BaseComponent as Component;
use Livewire\WithPagination;

class DynamicTable extends Component
{
    use WithPagination;

    public array $columns = [];

    public array $rows = [];

    public array $filters = [];

    public array $filterValues = [];

    #[Url(except: '')]
    public ?string $search = null;

    #[Url(except: '')]
    public string $sortField = '';

    #[Url(except: 'asc')]
    public string $sortDirection = 'asc';

    public int $perPage = 10;

    public array $perPageOptions = [10, 25, 50, 100];

    public bool $showSearch = true;

    public bool $showFilters = true;

    public bool $showPagination = true;

    public bool $showPerPage = true;

    public string $emptyMessage = '';

    public string $tableClass = '';

    public array $actions = [];

    public bool $selectable = false;

    public array $selected = [];

    public array $allowedActions = [];

    public function mount(
        array $columns = [],
        array $rows = [],
        array $filters = [],
        int $perPage = 10,
        bool $showSearch = true,
        bool $showFilters = true,
        bool $showPagination = true,
        string $emptyMessage = '',
        array $actions = [],
        bool $selectable = false,
        array $allowedActions = []
    ): void {
        $this->columns = $columns;
        $this->rows = $rows;
        $this->filters = $filters;
        $this->perPage = $perPage;
        $this->showSearch = $showSearch;
        $this->showFilters = $showFilters;
        $this->showPagination = $showPagination;
        $this->emptyMessage = $emptyMessage ?: __('No records found.');
        $this->actions = $actions;
        $this->selectable = $selectable;
        if ($allowedActions) {
            $this->allowedActions = $allowedActions;
        } else {
            $this->allowedActions = array_filter(
                array_map(fn ($action) => $action['name'] ?? null, $actions)
            );
        }

        foreach ($this->filters as $filter) {
            $name = $filter['name'] ?? '';
            if ($name) {
                $this->filterValues[$name] = $filter['default'] ?? '';
            }
        }
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->dispatch('sort-changed', field: $field, direction: $this->sortDirection);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->dispatch('search-updated', query: $this->search);
    }

    public function updatedFilterValues(): void
    {
        $this->resetPage();
        $this->dispatch('filters-updated', filters: $this->filterValues);
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
        $this->dispatch('per-page-changed', perPage: $this->perPage);
    }

    public function clearFilters(): void
    {
        $this->search = '';
        foreach ($this->filters as $filter) {
            $name = $filter['name'] ?? '';
            if ($name) {
                $this->filterValues[$name] = '';
            }
        }
        $this->resetPage();
        $this->dispatch('filters-cleared');
    }

    public function toggleSelectAll(): void
    {
        if (count($this->selected) === count($this->rows)) {
            $this->selected = [];
        } else {
            $this->selected = array_map(fn ($row) => $row['id'] ?? null, $this->rows);
        }
        $this->dispatch('selection-changed', selected: $this->selected);
    }

    public function executeAction(string $action, $id): void
    {
        $this->authorizeAction($action);
        $this->dispatch('action-executed', action: $action, id: $id);
    }

    public function executeBulkAction(string $action): void
    {
        $this->authorizeAction($action);
        $this->dispatch('bulk-action-executed', action: $action, ids: $this->selected);
    }

    public function getFilteredRows(): array
    {
        $rows = $this->rows;

        if ($this->search) {
            $search = strtolower($this->search);
            $rows = array_filter($rows, function ($row) use ($search) {
                foreach ($this->columns as $column) {
                    $name = $column['name'] ?? '';
                    if ($name && isset($row[$name])) {
                        if (str_contains(strtolower((string) $row[$name]), $search)) {
                            return true;
                        }
                    }
                }

                return false;
            });
        }

        foreach ($this->filterValues as $filterName => $filterValue) {
            if ($filterValue !== '' && $filterValue !== null) {
                $rows = array_filter($rows, function ($row) use ($filterName, $filterValue) {
                    return isset($row[$filterName]) && $row[$filterName] == $filterValue;
                });
            }
        }

        if ($this->sortField) {
            usort($rows, function ($a, $b) {
                $valA = $a[$this->sortField] ?? '';
                $valB = $b[$this->sortField] ?? '';
                $result = $valA <=> $valB;

                return $this->sortDirection === 'asc' ? $result : -$result;
            });
        }

        return array_values($rows);
    }

    public function getPaginatedRows(): array
    {
        $filtered = $this->getFilteredRows();
        $offset = ($this->getPage() - 1) * $this->perPage;

        return array_slice($filtered, $offset, $this->perPage);
    }

    public function getTotalPages(): int
    {
        return (int) ceil(count($this->getFilteredRows()) / $this->perPage);
    }

    public function getPage(): int
    {
        return max(1, $this->paginators['page'] ?? 1);
    }

    public function render()
    {
        return view('livewire.shared.dynamic-table', [
            'displayRows' => $this->getPaginatedRows(),
            'totalRows' => count($this->getFilteredRows()),
            'totalPages' => $this->getTotalPages(),
            'currentPage' => $this->getPage(),
        ]);
    }

    protected function authorizeAction(string $action): void
    {
        $actionAllowed = empty($this->allowedActions) || in_array($action, $this->allowedActions, true);
        if (! $actionAllowed) {
            abort(403, __('Action is not allowed.'));
        }

        $definition = collect($this->actions)->firstWhere('name', $action);
        $ability = $definition['ability'] ?? null;

        if ($ability && ! auth()->user()?->can($ability)) {
            abort(403);
        }
    }
}
