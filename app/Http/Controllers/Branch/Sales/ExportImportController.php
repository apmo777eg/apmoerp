<?php

namespace App\Http\Controllers\Branch\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Traits\ExportsCsv;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportImportController extends Controller
{
    use ExportsCsv;

    public function exportSales(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'branch_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'string'],
            'format' => ['nullable', 'in:xlsx,csv'],
        ]);

        $query = Sale::query()->with(['branch', 'customer']);

        if (! empty($validated['date_from'])) {
            $query->whereDate('sale_date', '>=', $validated['date_from']);
        }

        if (! empty($validated['date_to'])) {
            $query->whereDate('sale_date', '<=', $validated['date_to']);
        }

        if (! empty($validated['branch_id'])) {
            $query->where('branch_id', $validated['branch_id']);
        }

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $format = $validated['format'] ?? 'xlsx';

        if ($format === 'xlsx') {
            return $this->exportToXlsx(
                $query,
                ['ID', 'Reference', 'Date', 'Customer', 'Branch', 'Subtotal', 'Tax', 'Discount', 'Total', 'Paid', 'Due', 'Status'],
                fn ($row) => [
                    $row->id,
                    $row->reference_number,
                    optional($row->sale_date)->format('Y-m-d'),
                    $row->customer?->name ?? '',
                    $row->branch?->name ?? '',
                    $row->subtotal ?? 0,
                    $row->tax_amount ?? 0,
                    $row->discount_amount ?? 0,
                    $row->total_amount,
                    $row->paid_amount ?? 0,
                    $row->remaining_amount ?? 0,
                    $row->status,
                ],
                'sales_invoices'
            );
        }

        return $this->exportToCsv(
            $query,
            ['ID', 'Reference', 'Date', 'Customer', 'Branch', 'Subtotal', 'Tax', 'Discount', 'Total', 'Paid', 'Due', 'Status'],
            fn ($row) => [
                $row->id,
                $row->reference_number,
                optional($row->sale_date)->format('Y-m-d'),
                $row->customer?->name ?? '',
                $row->branch?->name ?? '',
                $row->subtotal ?? 0,
                $row->tax_amount ?? 0,
                $row->discount_amount ?? 0,
                $row->total_amount,
                $row->paid_amount ?? 0,
                $row->remaining_amount ?? 0,
                $row->status,
            ],
            'sales_invoices'
        );
    }

    public function importSales(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv',
            'update_existing' => 'nullable|boolean',
        ]);

        $updateExisting = $request->input('update_existing', false);
        $file = $request->file('file');

        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
        } catch (\Exception $e) {
            return back()->with('error', __('Failed to read file: :error', ['error' => $e->getMessage()]));
        }

        if (count($rows) < 2) {
            return back()->with('error', __('File is empty or has no data rows'));
        }

        $headers = array_map('strtolower', array_map('trim', $rows[0]));
        unset($rows[0]);

        $imported = 0;
        $failed = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($rows as $rowNum => $row) {
                $rowData = [];
                foreach ($headers as $index => $header) {
                    $rowData[$header] = isset($row[$index]) ? trim((string) $row[$index]) : null;
                }

                // Skip empty rows
                if (empty(array_filter($rowData))) {
                    continue;
                }

                // Validate row data
                $validator = Validator::make($rowData, [
                    'reference' => 'nullable|string|max:50',
                    'date' => 'required|date',
                    'customer' => 'nullable|string|max:255',
                    'total' => 'required|numeric|min:0',
                    'status' => 'required|in:draft,posted,paid,cancelled',
                ]);

                if ($validator->fails()) {
                    $errors[] = [
                        'row' => $rowNum + 1,
                        'errors' => $validator->errors()->all(),
                    ];
                    $failed++;

                    continue;
                }

                try {
                    // Find or create sale
                    $saleData = [
                        'reference_number' => $rowData['reference'] ?? 'IMP-'.date('Ymd').'-'.(string) Str::uuid(),
                        'sale_date' => $rowData['date'],
                        'total_amount' => (float) $rowData['total'],
                        'subtotal' => (float) ($rowData['subtotal'] ?? $rowData['total']),
                        'tax_amount' => (float) ($rowData['tax'] ?? 0),
                        'discount_amount' => (float) ($rowData['discount'] ?? 0),
                        'paid_amount' => (float) ($rowData['paid'] ?? 0),
                        'status' => $rowData['status'],
                        'branch_id' => auth()->user()->branch_id,
                    ];

                    if ($updateExisting && ! empty($rowData['reference'])) {
                        Sale::updateOrCreate(
                            ['reference_number' => $rowData['reference']],
                            $saleData
                        );
                    } else {
                        Sale::create($saleData);
                    }

                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = [
                        'row' => $rowNum + 1,
                        'errors' => [$e->getMessage()],
                    ];
                    $failed++;
                }
            }

            DB::commit();

            $message = __(':imported sales imported successfully', ['imported' => $imported]);
            if ($failed > 0) {
                $message .= '. '.__(':failed rows failed', ['failed' => $failed]);
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', __('Import failed: :error', ['error' => $e->getMessage()]));
        }
    }
}
