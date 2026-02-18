<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ExportLayout;
use App\Traits\HandlesServiceErrors;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportService
{
    use HandlesServiceErrors;

    public function getAvailableColumns(string $entityType): array
    {
        return match ($entityType) {
            'products' => [
                'id' => 'ID',
                'code' => __('Code'),
                'name' => __('Name'),
                'sku' => __('SKU'),
                'barcode' => __('Barcode'),
                'type' => __('Type'),
                'standard_cost' => __('Cost Price'),
                'default_price' => __('Sell Price'),
                'min_stock' => __('Min Stock'),
                'status' => __('Status'),
                'module_name' => __('Module'),
                'branch_name' => __('Branch'),
                'created_at' => __('Created At'),
            ],
            'sales' => [
                'id' => 'ID',
                'reference_number' => __('Reference'),
                'sale_date' => __('Date'),
                'customer_name' => __('Customer'),
                'total_amount' => __('Total'),
                'amount_paid' => __('Paid'),
                'amount_due' => __('Due'),
                'status' => __('Status'),
                'payment_status' => __('Payment Status'),
                'branch_name' => __('Branch'),
                'created_at' => __('Created At'),
            ],
            'purchases' => [
                'id' => 'ID',
                'reference_number' => __('Reference'),
                'purchase_date' => __('Date'),
                'supplier_name' => __('Supplier'),
                'total_amount' => __('Total'),
                'amount_paid' => __('Paid'),
                'amount_due' => __('Due'),
                'status' => __('Status'),
                'payment_status' => __('Payment Status'),
                'branch_name' => __('Branch'),
                'created_at' => __('Created At'),
            ],
            'customers' => [
                'id' => 'ID',
                'name' => __('Name'),
                'email' => __('Email'),
                'phone' => __('Phone'),
                'address' => __('Address'),
                'balance' => __('Balance'),
                // FIX: customers table uses `type` (individual/company)
                'type' => __('Type'),
                'created_at' => __('Created At'),
            ],
            'suppliers' => [
                'id' => 'ID',
                'name' => __('Name'),
                'company_name' => __('Company'),
                'email' => __('Email'),
                'phone' => __('Phone'),
                'contact_person' => __('Contact Person'),
                'address' => __('Address'),
                'city' => __('City'),
                'country' => __('Country'),
                'balance' => __('Balance'),
                'is_active' => __('Status'),
                'tax_number' => __('Tax Number'),
                'created_at' => __('Created At'),
            ],
            'expenses' => [
                'id' => 'ID',
                // APMOERP68-FIX: Add reference_number to expenses export columns
                'reference_number' => __('Reference'),
                'expense_date' => __('Date'),
                'category_name' => __('Category'),
                'description' => __('Description'),
                'amount' => __('Amount'),
                'branch_name' => __('Branch'),
                'created_at' => __('Created At'),
            ],
            'incomes' => [
                'id' => 'ID',
                // APMOERP68-FIX: Add reference_number to incomes export columns
                'reference_number' => __('Reference'),
                'income_date' => __('Date'),
                'category_name' => __('Category'),
                'description' => __('Description'),
                'amount' => __('Amount'),
                'branch_name' => __('Branch'),
                'created_at' => __('Created At'),
            ],
            default => [],
        };
    }

    public function getUserLayouts(int $userId, ?string $entityType = null): Collection
    {
        return $this->handleServiceOperation(
            callback: function () use ($userId, $entityType) {
                $query = ExportLayout::forUser($userId);

                if ($entityType) {
                    $query->forEntity($entityType);
                }

                return $query->orderBy('layout_name')->get();
            },
            operation: 'getUserLayouts',
            context: ['user_id' => $userId, 'entity_type' => $entityType]
        );
    }

    public function getDefaultLayout(int $userId, string $entityType): ?ExportLayout
    {
        return $this->handleServiceOperation(
            callback: fn () => ExportLayout::forUser($userId)
                ->forEntity($entityType)
                ->default()
                ->first(),
            operation: 'getDefaultLayout',
            context: ['user_id' => $userId, 'entity_type' => $entityType],
            defaultValue: null
        );
    }

    public function saveLayout(int $userId, string $entityType, array $data): ExportLayout
    {
        return $this->handleServiceOperation(
            callback: function () use ($userId, $entityType, $data) {
                if ($data['is_default'] ?? false) {
                    ExportLayout::where('user_id', $userId)
                        ->where('entity_type', $entityType)
                        ->update(['is_default' => false]);
                }

                return ExportLayout::updateOrCreate(
                    [
                        'user_id' => $userId,
                        'entity_type' => $entityType,
                        'layout_name' => $data['layout_name'],
                    ],
                    [
                        'selected_columns' => $data['selected_columns'],
                        'column_order' => $data['column_order'] ?? null,
                        'column_labels' => $data['column_labels'] ?? null,
                        'export_format' => $data['export_format'] ?? 'xlsx',
                        'include_headers' => $data['include_headers'] ?? true,
                        'date_format' => $data['date_format'] ?? 'Y-m-d',
                        'number_format' => $data['number_format'] ?? null,
                        'is_default' => $data['is_default'] ?? false,
                        'is_shared' => $data['is_shared'] ?? false,
                    ]
                );
            },
            operation: 'saveLayout',
            context: ['user_id' => $userId, 'entity_type' => $entityType]
        );
    }

    public function deleteLayout(int $layoutId, int $userId): bool
    {
        return $this->handleServiceOperation(
            callback: fn () => ExportLayout::where('id', $layoutId)
                ->where('user_id', $userId)
                ->delete() > 0,
            operation: 'deleteLayout',
            context: ['layout_id' => $layoutId, 'user_id' => $userId],
            defaultValue: false
        );
    }

    public function export(Collection $data, array $columns, string $format = 'xlsx', array $options = []): string
    {
        return $this->handleServiceOperation(
            callback: function () use ($data, $columns, $format, $options) {
                // Validate inputs
                if (empty($columns)) {
                    throw new \InvalidArgumentException('At least one column must be specified for export');
                }

                // Empty data exports are allowed to enable users to export template files
                // with headers only. This is useful for:
                // 1. Creating templates for bulk import
                // 2. Exporting filtered results that may have no matching records
                // 3. Demonstrating export format to users

                $availableColumns = $options['available_columns'] ?? [];
                $dateFormat = $options['date_format'] ?? 'Y-m-d';
                $includeHeaders = $options['include_headers'] ?? true;
                $filename = $options['filename'] ?? 'export_'.date('Y-m-d_His');

                // Sanitize filename
                $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $filename);

                $rows = $this->prepareDataRows($data, $columns, $availableColumns, $dateFormat);

                return match ($format) {
                    'xlsx' => $this->exportToExcel($rows, $columns, $availableColumns, $includeHeaders, $filename),
                    'csv' => $this->exportToCsv($rows, $columns, $availableColumns, $includeHeaders, $filename),
                    'pdf' => $this->exportToPdf($rows, $columns, $availableColumns, $includeHeaders, $filename, $options),
                    default => $this->exportToExcel($rows, $columns, $availableColumns, $includeHeaders, $filename),
                };
            },
            operation: 'export',
            context: ['format' => $format, 'columns_count' => count($columns), 'data_count' => $data->count()]
        );
    }

    protected function prepareDataRows(Collection $data, array $columns, array $availableColumns, string $dateFormat): array
    {
        return $data->map(function ($item) use ($columns, $dateFormat) {
            $row = [];
            // NOTE: (array)$model can produce surprising keys (protected properties, etc.)
            // We prefer Model::toArray() when available.
            if (is_object($item) && method_exists($item, 'toArray')) {
                $itemArray = $item->toArray();
            } elseif (is_object($item)) {
                $itemArray = get_object_vars($item);
            } else {
                $itemArray = is_array($item) ? $item : (array) $item;
            }

            foreach ($columns as $column) {
                $value = data_get($itemArray, $column, '');

                // Handle different data types safely
                try {
                    if ($value instanceof \DateTime || $value instanceof \Carbon\Carbon) {
                        $value = $value->format($dateFormat);
                    } elseif (is_array($value)) {
                        $value = json_encode($value, JSON_THROW_ON_ERROR);
                    } elseif (is_object($value)) {
                        $value = method_exists($value, '__toString') ? (string) $value : json_encode($value, JSON_THROW_ON_ERROR);
                    } elseif (is_bool($value)) {
                        $value = $value ? 'Yes' : 'No';
                    } elseif (is_null($value)) {
                        $value = '';
                    } else {
                        // Ensure string conversion for scalar types
                        $value = (string) $value;
                    }
                } catch (\Throwable $e) {
                    // Fallback to empty string if conversion fails
                    $value = '';
                }

                $row[$column] = $value;
            }

            return $row;
        })->toArray();
    }

    protected function exportToExcel(array $rows, array $columns, array $availableColumns, bool $includeHeaders, string $filename): string
    {
        try {
            $spreadsheet = new Spreadsheet;
            $sheet = $spreadsheet->getActiveSheet();

            $rowIndex = 1;

            if ($includeHeaders) {
                $colIndex = 1;
                foreach ($columns as $column) {
                    $label = $availableColumns[$column] ?? $column;
                    $cellCoordinate = Coordinate::stringFromColumnIndex($colIndex).$rowIndex;
                    $sheet->setCellValue($cellCoordinate, $label);
                    $colIndex++;
                }

                // Style header row
                $lastColumn = Coordinate::stringFromColumnIndex(count($columns));
                $headerRange = 'A1:'.$lastColumn.'1';
                $sheet->getStyle($headerRange)->getFont()->setBold(true);
                $sheet->getStyle($headerRange)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFE0E0E0');

                $rowIndex++;
            }

            foreach ($rows as $row) {
                $colIndex = 1;
                foreach ($columns as $column) {
                    $value = $row[$column] ?? '';
                    $cellCoordinate = Coordinate::stringFromColumnIndex($colIndex).$rowIndex;
                    $sheet->setCellValue($cellCoordinate, $value);
                    $colIndex++;
                }
                $rowIndex++;
            }

            // Auto-size columns for better readability
            foreach (range(1, count($columns)) as $col) {
                $colLetter = Coordinate::stringFromColumnIndex($col);
                $sheet->getColumnDimension($colLetter)->setAutoSize(true);
            }

            $filepath = $this->getExportPath($filename, 'xlsx');

            $writer = new Xlsx($spreadsheet);
            $writer->save($filepath);

            return $filepath;
        } catch (\Throwable $e) {
            throw new \RuntimeException('Failed to generate Excel export: '.$e->getMessage(), 0, $e);
        }
    }

    protected function exportToCsv(array $rows, array $columns, array $availableColumns, bool $includeHeaders, string $filename): string
    {
        try {
            $filepath = $this->getExportPath($filename, 'csv');

            $handle = fopen($filepath, 'w');

            if (! $handle) {
                throw new \RuntimeException('Failed to create CSV file');
            }

            // Add UTF-8 BOM for proper Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            if ($includeHeaders) {
                $headers = array_map(fn ($col) => $availableColumns[$col] ?? $col, $columns);
                fputcsv($handle, $headers);
            }

            foreach ($rows as $row) {
                $rowData = array_map(fn ($col) => $row[$col] ?? '', $columns);
                fputcsv($handle, $rowData);
            }

            fclose($handle);

            return $filepath;
        } catch (\Throwable $e) {
            throw new \RuntimeException('Failed to generate CSV export: '.$e->getMessage(), 0, $e);
        }
    }

    protected function exportToPdf(array $rows, array $columns, array $availableColumns, bool $includeHeaders, string $filename, array $options): string
    {
        $locale = app()->getLocale();
        $isRtl = $locale === 'ar';
        $textAlign = $isRtl ? 'right' : 'left';

        $html = '<!DOCTYPE html>';
        $html .= '<html dir="'.($isRtl ? 'rtl' : 'ltr').'" lang="'.$locale.'"><head><meta charset="UTF-8">';
        // Note: DejaVu Sans is bundled with Dompdf and supports Unicode including Arabic
        $html .= '<style>
            * { font-family: "DejaVu Sans", sans-serif; }
            body { 
                font-size: 10px;
                direction: '.($isRtl ? 'rtl' : 'ltr').';
                text-align: '.$textAlign.';
            }
            table { 
                width: 100%; 
                border-collapse: collapse; 
                margin-top: 10px; 
                direction: '.($isRtl ? 'rtl' : 'ltr').';
            }
            th, td { 
                border: 1px solid #ddd; 
                padding: 6px 8px; 
                text-align: '.$textAlign.'; 
                vertical-align: middle;
            }
            th { 
                background-color: #10b981; 
                color: white; 
                font-weight: bold; 
            }
            tr:nth-child(even) { background-color: #f9f9f9; }
            h1 { 
                color: #10b981; 
                font-size: 18px;
                text-align: '.$textAlign.';
            }
            .export-info {
                font-size: 9px;
                color: #666;
                margin-bottom: 10px;
                text-align: '.$textAlign.';
            }
        </style></head><body>';

        if (! empty($options['title'])) {
            $html .= '<h1>'.htmlspecialchars($options['title']).'</h1>';
        }

        // Add export metadata
        $html .= '<div class="export-info">';
        $html .= htmlspecialchars(__('Exported on')).': '.now()->format('Y-m-d H:i:s');
        $html .= ' | '.htmlspecialchars(__('Total records')).': '.count($rows);
        $html .= '</div>';

        $html .= '<table><thead><tr>';

        if ($includeHeaders) {
            foreach ($columns as $column) {
                $label = $availableColumns[$column] ?? $column;
                $html .= '<th>'.htmlspecialchars($label).'</th>';
            }
        }

        $html .= '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($columns as $column) {
                $value = $row[$column] ?? '';
                // Format dates nicely
                if (preg_match('/^\d{4}-\d{2}-\d{2}/', (string) $value)) {
                    try {
                        $value = \Carbon\Carbon::parse($value)->format($options['date_format'] ?? 'Y-m-d');
                    } catch (\Exception $e) {
                        // Keep original value if parsing fails
                    }
                }
                $html .= '<td>'.htmlspecialchars((string) $value).'</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table></body></html>';

        if (class_exists(\Dompdf\Dompdf::class)) {
            $filepath = $this->getExportPath($filename, 'pdf');
            $dompdf = new \Dompdf\Dompdf([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'defaultFont' => 'DejaVu Sans',
            ]);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();
            file_put_contents($filepath, $dompdf->output());
        } else {
            // Fallback to HTML when Dompdf is not available
            $filepath = $this->getExportPath($filename, 'html');
            file_put_contents($filepath, $html);
        }

        return $filepath;
    }

    /**
     * Resolve the disk path for storing export files, ensuring the directory exists.
     */
    protected function getExportPath(string $filename, string $extension): string
    {
        // IMPORTANT:
        // The export pipeline expects an *absolute local filesystem path*.
        // Using a remote filesystem disk (e.g., s3) will throw "Invalid export path"
        // because those disks do not support path().
        $diskName = config('exports.disk', 'local');
        $disk = Storage::disk($diskName);

        if (! method_exists($disk, 'path')) {
            $diskName = 'local';
            $disk = Storage::disk('local');
        }

        $baseDir = trim(config('exports.path', 'exports'), '/');
        if (! $disk->exists($baseDir)) {
            $disk->makeDirectory($baseDir);
        }

        return $disk->path("{$baseDir}/{$filename}.{$extension}");
    }

    public function downloadAndCleanup(string $filepath): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $filename = basename($filepath);

        return response()->download($filepath, $filename)->deleteFileAfterSend(true);
    }

    public function exportWithLayout(Collection $data, ExportLayout $layout, array $options = []): string
    {
        return $this->handleServiceOperation(
            callback: function () use ($data, $layout, $options) {
                $columns = $layout->getOrderedColumns();
                $availableColumns = $this->getAvailableColumns($layout->entity_type);

                if (! empty($layout->column_labels)) {
                    $availableColumns = array_merge($availableColumns, $layout->column_labels);
                }

                return $this->export($data, $columns, $layout->export_format, array_merge($options, [
                    'available_columns' => $availableColumns,
                    'date_format' => $layout->date_format,
                    'include_headers' => $layout->include_headers,
                ]));
            },
            operation: 'exportWithLayout',
            context: ['layout_id' => $layout->id, 'entity_type' => $layout->entity_type]
        );
    }
}
