<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\Livewire\BaseComponent as Component;
class ExportColumnSelector extends Component
{
    public array $availableColumns = [];

    public array $selectedColumns = [];

    public bool $showModal = false;

    public string $exportUrl = '';

    public string $entityType = '';

    public function mount(array $availableColumns, string $exportUrl, string $entityType = '')
    {
        $this->availableColumns = $availableColumns;
        $this->selectedColumns = array_keys($availableColumns);
        $this->exportUrl = $exportUrl;
        $this->entityType = $entityType;
    }

    public function toggleColumn(string $column)
    {
        if (in_array($column, $this->selectedColumns)) {
            $this->selectedColumns = array_values(array_diff($this->selectedColumns, [$column]));
        } else {
            $this->selectedColumns[] = $column;
        }
    }

    public function selectAll()
    {
        $this->selectedColumns = array_keys($this->availableColumns);
    }

    public function deselectAll()
    {
        $this->selectedColumns = [];
    }

    public function export()
    {
        if (empty($this->selectedColumns)) {
            session()->flash('error', __('Please select at least one column to export'));

            return;
        }

        // Build export URL with selected columns
        $url = $this->exportUrl;
        $separator = str_contains($url, '?') ? '&' : '?';
        $url .= $separator.'columns='.urlencode(implode(',', $this->selectedColumns));
        $url .= '&format=xlsx';

        // Close modal before redirect
        $this->showModal = false;

        // Redirect to export
        $this->redirect($url, navigate: true);
    }

    public function openModal()
    {
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
    }

    public function render()
    {
        return view('livewire.components.export-column-selector');
    }
}
