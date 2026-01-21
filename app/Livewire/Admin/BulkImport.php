<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Services\ImportService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class BulkImport extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;

    public ?string $entityType = null;

    public ?int $selectedModuleId = null;

    public $importFile = null;

    public string $importSource = 'file'; // 'file' or 'google_sheet'

    public string $googleSheetUrl = '';

    public bool $hasHeaders = true;

    public bool $updateExisting = false;

    public bool $skipDuplicates = true;

    public int $currentStep = 1;

    public array $previewData = [];

    public array $columnMapping = [];

    public array $importResult = [];

    public bool $importing = false;

    public bool $dryRun = false;

    protected ImportService $importService;

    public function boot(ImportService $importService): void
    {
        $this->importService = $importService;
    }

    public function mount(): void
    {
        // V57-HIGH-01 FIX: Add authorization for bulk import
        $this->authorize('import.manage');
        
        $this->entityType = request()->query('type', 'products');
        $moduleId = request()->query('module');
        if ($moduleId) {
            $this->selectedModuleId = (int) $moduleId;
        }
    }

    public function getEntitiesProperty(): array
    {
        return $this->importService->getImportableEntities($this->selectedModuleId);
    }

    public function getModulesProperty(): array
    {
        return $this->importService->getModulesWithProducts();
    }

    public function updatedEntityType(): void
    {
        $this->reset(['importFile', 'previewData', 'columnMapping', 'importResult', 'googleSheetUrl']);
        $this->currentStep = 1;
    }

    public function updatedSelectedModuleId(): void
    {
        // Reset preview when module changes
        $this->reset(['importFile', 'previewData', 'columnMapping', 'importResult', 'googleSheetUrl']);
    }

    public function updatedImportSource(): void
    {
        $this->reset(['importFile', 'previewData', 'columnMapping', 'importResult', 'googleSheetUrl']);
    }

    public function updatedImportFile(): void
    {
        if ($this->importFile) {
            $this->validate([
                'importFile' => 'required|file|mimes:csv,xlsx,xls|max:10240',
            ]);
            $this->loadPreview();
        }
    }

    /**
     * Fetch CSV data from Google Sheets
     *
     * @return string|null The CSV content or null on failure
     */
    protected function fetchGoogleSheetCsv(): ?string
    {
        if (empty($this->googleSheetUrl)) {
            session()->flash('error', __('Please enter a Google Sheets URL'));

            return null;
        }

        $sheetId = $this->extractGoogleSheetId($this->googleSheetUrl);
        if (! $sheetId) {
            session()->flash('error', __('Invalid Google Sheets URL. Please use a sharing link like: https://docs.google.com/spreadsheets/d/SHEET_ID/edit'));

            return null;
        }

        try {
            $csvUrl = "https://docs.google.com/spreadsheets/d/{$sheetId}/export?format=csv";
            $response = Http::timeout(30)->get($csvUrl);

            if (! $response->successful()) {
                session()->flash('error', __('Could not access Google Sheet. Make sure the sheet is shared publicly or with "Anyone with the link".'));

                return null;
            }

            return $response->body();
        } catch (\Exception $e) {
            session()->flash('error', __('Error loading Google Sheet: ').$e->getMessage());

            return null;
        }
    }

    public function loadGoogleSheet(): void
    {
        $csvContent = $this->fetchGoogleSheetCsv();
        if (! $csvContent) {
            return;
        }

        try {
            // Save CSV content to temp file
            $tempPath = 'imports/temp_'.uniqid().'.csv';
            Storage::disk('local')->put($tempPath, $csvContent);

            // Load preview from CSV
            $this->loadPreviewFromPath(Storage::disk('local')->path($tempPath));

            // Clean up
            Storage::disk('local')->delete($tempPath);
        } catch (\Exception $e) {
            session()->flash('error', __('Error loading Google Sheet: ').$e->getMessage());
        }
    }

    protected function extractGoogleSheetId(string $url): ?string
    {
        // Match patterns like:
        // https://docs.google.com/spreadsheets/d/SHEET_ID/edit
        // https://docs.google.com/spreadsheets/d/SHEET_ID/edit?usp=sharing
        if (preg_match('/\/spreadsheets\/d\/([a-zA-Z0-9-_]+)/', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }

    protected function loadPreview(): void
    {
        if (! $this->importFile || ! $this->entityType) {
            return;
        }

        // Validate entity type against allowed values
        $allowedEntities = array_keys($this->importService->getImportableEntities());
        if (! in_array($this->entityType, $allowedEntities, true)) {
            session()->flash('error', __('Invalid entity type selected'));

            return;
        }

        try {
            // Additional file validation
            $mimeType = $this->importFile->getMimeType();
            $allowedMimes = [
                'text/csv',
                'application/csv',
                'text/plain',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ];

            if (! in_array($mimeType, $allowedMimes, true)) {
                session()->flash('error', __('Invalid file type. Please upload CSV, XLS or XLSX file.'));

                return;
            }

            $this->loadPreviewFromPath($this->importFile->getRealPath());
        } catch (\Exception $e) {
            session()->flash('error', __('Error reading file: ').$e->getMessage());
        }
    }

    protected function loadPreviewFromPath(string $path): void
    {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        if (empty($rows)) {
            session()->flash('error', __('The file is empty'));

            return;
        }

        $headers = $this->hasHeaders ? array_map('trim', $rows[0]) : [];
        $this->previewData = array_slice($rows, $this->hasHeaders ? 1 : 0, 10);

        // Auto-map columns
        $entityConfig = $this->entities[$this->entityType] ?? [];
        $availableColumns = array_merge(
            $entityConfig['required_columns'] ?? [],
            $entityConfig['optional_columns'] ?? []
        );

        $this->columnMapping = [];
        foreach ($headers as $index => $header) {
            $lowerHeader = strtolower(trim($header));
            foreach ($availableColumns as $col) {
                if (strtolower($col) === $lowerHeader) {
                    $this->columnMapping[$index] = $col;
                    break;
                }
            }
        }

        session()->flash('success', __('File loaded successfully. :count rows found.', ['count' => count($rows) - ($this->hasHeaders ? 1 : 0)]));
    }

    public function nextStep(): void
    {
        if ($this->currentStep === 1) {
            if ($this->importSource === 'file' && ! $this->importFile) {
                session()->flash('error', __('Please upload a file first'));

                return;
            }
            if ($this->importSource === 'google_sheet' && empty($this->previewData)) {
                session()->flash('error', __('Please load a Google Sheet first'));

                return;
            }
            if (! $this->entityType) {
                session()->flash('error', __('Please select an entity type'));

                return;
            }
        }

        if ($this->currentStep < 3) {
            $this->currentStep++;
        }
    }

    public function previousStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    public function downloadTemplate(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        // Validate entity type
        $allowedEntities = array_keys($this->importService->getImportableEntities($this->selectedModuleId));
        if (! in_array($this->entityType, $allowedEntities, true)) {
            session()->flash('error', __('Invalid entity type selected'));

            return back();
        }

        $path = $this->importService->generateTemplate($this->entityType, $this->selectedModuleId);

        if (! $path) {
            session()->flash('error', __('Failed to generate template'));

            return back();
        }

        // Sanitize filename - only allow alphanumeric and underscore
        $safeEntityType = preg_replace('/[^a-zA-Z0-9_]/', '_', $this->entityType);
        $moduleSuffix = $this->selectedModuleId ? "_module{$this->selectedModuleId}" : '';

        return response()->download(
            Storage::disk('local')->path($path),
            "import_template_{$safeEntityType}{$moduleSuffix}.xlsx"
        )->deleteFileAfterSend(true);
    }

    public function runImport(): void
    {
        if (! $this->entityType) {
            session()->flash('error', __('Please select an entity type'));

            return;
        }

        // Validate entity type against allowed values
        $allowedEntities = array_keys($this->importService->getImportableEntities($this->selectedModuleId));
        if (! in_array($this->entityType, $allowedEntities, true)) {
            session()->flash('error', __('Invalid entity type selected'));

            return;
        }

        $this->importing = true;
        $fullPath = null;
        $tempPath = null;

        try {
            if ($this->importSource === 'file' && $this->importFile) {
                $tempPath = $this->importFile->store('imports', 'local');
                $fullPath = Storage::disk('local')->path($tempPath);
            } elseif ($this->importSource === 'google_sheet' && ! empty($this->googleSheetUrl)) {
                // Use the refactored method to fetch Google Sheet data
                $csvContent = $this->fetchGoogleSheetCsv();
                if (! $csvContent) {
                    $this->importing = false;

                    return;
                }

                $tempPath = 'imports/temp_'.uniqid().'.csv';
                Storage::disk('local')->put($tempPath, $csvContent);
                $fullPath = Storage::disk('local')->path($tempPath);
            } else {
                session()->flash('error', __('Please upload a file or provide a Google Sheet URL'));

                return;
            }

            $this->importResult = $this->importService->import(
                $this->entityType,
                $fullPath,
                [
                    'update_existing' => $this->updateExisting,
                    'skip_duplicates' => $this->skipDuplicates,
                    'branch_id' => auth()->user()->branch_id,
                    'module_id' => $this->selectedModuleId,
                ]
            );

            // Clean up
            if ($tempPath) {
                Storage::disk('local')->delete($tempPath);
            }

            if ($this->importResult['success']) {
                session()->flash('success', $this->importResult['message']);
            } else {
                session()->flash('error', $this->importResult['message']);
            }
        } catch (\Exception $e) {
            session()->flash('error', __('Import failed: ').$e->getMessage());
            $this->importResult = [
                'success' => false,
                'message' => $e->getMessage(),
                'imported' => 0,
                'failed' => 0,
                'errors' => [],
            ];
        } finally {
            $this->importing = false;
        }
    }

    public function render()
    {
        return view('livewire.admin.bulk-import')
            ->layout('layouts.app')
            ->title(__('Bulk Import'));
    }
}
