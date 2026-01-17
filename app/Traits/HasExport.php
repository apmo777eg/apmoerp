<?php

declare(strict_types=1);

namespace App\Traits;

use App\Services\ExportService;

trait HasExport
{
    public bool $showExportModal = false;

    public array $exportColumns = [];

    public array $selectedExportColumns = [];

    public string $exportFormat = 'xlsx';

    public bool $exportIncludeHeaders = true;

    public string $exportDateFormat = 'Y-m-d';

    public $exportMaxRows = 1000;

    public bool $exportRespectFilters = true;

    public bool $exportIncludeTotals = false;

    public bool $exportUseBackgroundJob = false;

    public function initializeExport(string $entityType): void
    {
        $exportService = app(ExportService::class);
        $this->exportColumns = $exportService->getAvailableColumns($entityType);
        $this->selectedExportColumns = array_keys($this->exportColumns);
    }

    public function openExportModal(): void
    {
        $this->showExportModal = true;
    }

    public function closeExportModal(): void
    {
        $this->showExportModal = false;
    }

    public function toggleAllExportColumns(): void
    {
        if (count($this->selectedExportColumns) === count($this->exportColumns)) {
            $this->selectedExportColumns = [];
        } else {
            $this->selectedExportColumns = array_keys($this->exportColumns);
        }
    }

    protected function performExport(string $entityType, $data, string $title = 'Export')
    {
        if (empty($this->selectedExportColumns)) {
            session()->flash('error', __('Please select at least one column'));

            return null;
        }

        try {
            $exportService = app(ExportService::class);

            // Apply max rows limit if not 'all'
            $collection = collect($data);
            if ($this->exportMaxRows !== 'all' && is_numeric($this->exportMaxRows)) {
                $collection = $collection->take((int) $this->exportMaxRows);
            }

            $exportData = $collection->map(function ($item) {
                if (is_object($item) && method_exists($item, 'toArray')) {
                    $array = $item->toArray();
                } elseif (is_object($item)) {
                    $array = get_object_vars($item);
                } else {
                    $array = $item;
                }

                return collect($this->selectedExportColumns)
                    ->mapWithKeys(fn ($col) => [$col => data_get($array, $col)])
                    ->toArray();
            });

            $filename = $entityType.'_export_'.date('Y-m-d_His');

            $filepath = $exportService->export(
                $exportData,
                $this->selectedExportColumns,
                $this->exportFormat,
                [
                    'available_columns' => $this->exportColumns,
                    'title' => $title,
                    'filename' => $filename,
                    'date_format' => $this->exportDateFormat,
                    'include_headers' => $this->exportIncludeHeaders,
                ]
            );

            if (! $filepath || ! file_exists($filepath)) {
                throw new \RuntimeException("Export file was not created successfully at expected path: {$filepath}");
            }

            $this->closeExportModal();

            $downloadName = $filename.'.'.$this->exportFormat;

            // Store export info in session for download
            // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
            session()->put('export_file', [
                'path' => $filepath,
                'name' => $downloadName,
                'time' => now()->timestamp,
                'user_id' => actual_user_id(),
            ]);

            // Ensure the export session data is immediately available to the download request
            session()->save();

            // Log export session data for debugging
            // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
            logger()->info('Export prepared', [
                'entity_type' => $entityType,
                'format' => $this->exportFormat,
                'file_path' => $filepath,
                'download_name' => $downloadName,
                'user_id' => actual_user_id(),
            ]);

            // Use JavaScript to trigger download via a dedicated route
            $this->dispatch('trigger-download', url: route('download.export'));

            session()->flash('success', __('Export prepared. Download starting...'));
        } catch (\Exception $e) {
            logger()->error('Export failed', [
                'entity_type' => $entityType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            session()->flash('error', __('Export failed: ').$e->getMessage());
            $this->closeExportModal();

            return null;
        }
    }
}
