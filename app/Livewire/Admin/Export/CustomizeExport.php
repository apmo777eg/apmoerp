<?php

namespace App\Livewire\Admin\Export;

use App\Models\ExportLayout;
use App\Services\ExportService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Livewire\BaseComponent as Component;
class CustomizeExport extends Component
{
    use AuthorizesRequests;

    public string $entityType = 'products';

    public string $layoutName = '';

    public array $selectedColumns = [];

    public array $columnOrder = [];

    public string $exportFormat = 'xlsx';

    public bool $includeHeaders = true;

    public string $dateFormat = 'Y-m-d';

    public bool $isDefault = false;

    public bool $isShared = false;

    public ?int $editingLayoutId = null;

    protected ExportService $exportService;

    protected $rules = [
        'layoutName' => 'required|string|max:100|min:3',
        'entityType' => 'required|in:products,sales,purchases,customers,suppliers,expenses,incomes',
        'selectedColumns' => 'required|array|min:1',
        'selectedColumns.*' => 'string|max:100',
        'exportFormat' => 'required|in:xlsx,csv,pdf',
        'dateFormat' => 'required|string|max:50',
        'includeHeaders' => 'boolean',
        'isDefault' => 'boolean',
        'isShared' => 'boolean',
    ];

    protected $messages = [
        'layoutName.required' => 'Please enter a layout name',
        'layoutName.min' => 'Layout name must be at least 3 characters',
        'layoutName.max' => 'Layout name cannot exceed 100 characters',
        'selectedColumns.required' => 'Please select at least one column',
        'selectedColumns.min' => 'Please select at least one column',
    ];

    public function boot(ExportService $exportService): void
    {
        $this->exportService = $exportService;
    }

    public function mount(): void
    {
        $this->authorize('reports.export');
        $this->loadDefaultColumns();
    }

    public function updatedEntityType(): void
    {
        $this->loadDefaultColumns();
        $this->editingLayoutId = null;
        $this->layoutName = '';
    }

    protected function loadDefaultColumns(): void
    {
        $columns = $this->exportService->getAvailableColumns($this->entityType);
        $this->selectedColumns = array_keys($columns);
        $this->columnOrder = array_keys($columns);
    }

    public function loadLayout(int $layoutId): void
    {
        $layout = ExportLayout::where('id', $layoutId)
            ->where('user_id', auth()->id())
            ->first();

        if ($layout) {
            $this->editingLayoutId = $layout->id;
            $this->layoutName = $layout->layout_name;
            $this->entityType = $layout->entity_type;
            $this->selectedColumns = $layout->selected_columns;
            $this->columnOrder = $layout->column_order ?? $layout->selected_columns;
            $this->exportFormat = $layout->export_format;
            $this->includeHeaders = $layout->include_headers;
            $this->dateFormat = $layout->date_format;
            $this->isDefault = $layout->is_default;
            $this->isShared = $layout->is_shared;
        }
    }

    public function saveLayout(): void
    {
        $this->authorize('reports.export');

        $this->validate();

        $this->exportService->saveLayout(auth()->id(), $this->entityType, [
            'layout_name' => $this->layoutName,
            'selected_columns' => $this->selectedColumns,
            'column_order' => $this->columnOrder,
            'export_format' => $this->exportFormat,
            'include_headers' => $this->includeHeaders,
            'date_format' => $this->dateFormat,
            'is_default' => $this->isDefault,
            'is_shared' => $this->isShared,
        ]);

        session()->flash('success', __('Export layout saved successfully'));
    }

    public function deleteLayout(int $layoutId): void
    {
        $this->authorize('reports.export');

        if ($this->exportService->deleteLayout($layoutId, auth()->id())) {
            session()->flash('success', __('Layout deleted successfully'));

            if ($this->editingLayoutId === $layoutId) {
                $this->editingLayoutId = null;
                $this->layoutName = '';
                $this->loadDefaultColumns();
            }
        }
    }

    public function moveColumnUp(string $column): void
    {
        $index = array_search($column, $this->columnOrder);
        if ($index !== false && $index > 0) {
            $temp = $this->columnOrder[$index - 1];
            $this->columnOrder[$index - 1] = $this->columnOrder[$index];
            $this->columnOrder[$index] = $temp;
        }
    }

    public function moveColumnDown(string $column): void
    {
        $index = array_search($column, $this->columnOrder);
        if ($index !== false && $index < count($this->columnOrder) - 1) {
            $temp = $this->columnOrder[$index + 1];
            $this->columnOrder[$index + 1] = $this->columnOrder[$index];
            $this->columnOrder[$index] = $temp;
        }
    }

    public function render()
    {
        $availableColumns = $this->exportService->getAvailableColumns($this->entityType);
        $savedLayouts = $this->exportService->getUserLayouts(auth()->id(), $this->entityType);

        return view('livewire.admin.export.customize-export', [
            'availableColumns' => $availableColumns,
            'savedLayouts' => $savedLayouts,
            'entityTypes' => [
                'products' => __('Products'),
                'sales' => __('Sales'),
                'purchases' => __('Purchases'),
                'customers' => __('Customers'),
                'suppliers' => __('Suppliers'),
                'expenses' => __('Expenses'),
                'incomes' => __('Income'),
            ],
            'dateFormats' => [
                'Y-m-d' => '2024-12-31',
                'd/m/Y' => '31/12/2024',
                'm/d/Y' => '12/31/2024',
                'd-m-Y' => '31-12-2024',
            ],
        ])->layout('layouts.app');
    }
}
