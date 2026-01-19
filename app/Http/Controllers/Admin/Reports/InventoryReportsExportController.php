<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\StockService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryReportsExportController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();

        if (! $user || ! $user->can('reports.inventory.export')) {
            abort(403);
        }

        $validated = $request->validate([
            'branch_id' => ['nullable', 'integer'],
            'only_low' => ['nullable', 'boolean'],
            'format' => ['nullable', 'in:web,excel,pdf'],
            'columns' => ['nullable', 'array'],
            'columns.*' => ['string'],
        ]);

        $format = $validated['format'] ?? 'web';
        $branchId = ! empty($validated['branch_id']) ? (int) $validated['branch_id'] : null;

        $query = Product::query();

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $products = $query->orderBy('name')->limit(5000)->get();

        // STILL-V14-CRITICAL-01 FIX: Get stock from stock_movements via StockService (source of truth)
        // instead of products.stock_quantity (cached value that may be warehouse-specific)
        $productIds = $products->pluck('id')->toArray();

        // Use branch-filtered stock calculation when a branch is specified
        if ($branchId) {
            $stockData = StockService::getBulkCurrentStockForBranch($productIds, $branchId);
        } else {
            $stockData = StockService::getBulkCurrentStock($productIds);
        }

        // Available columns with labels
        $availableColumns = [
            'id' => 'ID',
            'sku' => 'SKU',
            'name' => 'Name',
            'stock_quantity' => 'Stock',
            'reorder_point' => 'Reorder Level',
        ];

        // Use selected columns or all columns
        $requestedColumns = $validated['columns'] ?? array_keys($availableColumns);
        $columns = array_intersect_key($availableColumns, array_flip($requestedColumns));

        // Preserve order of requested columns
        if (! empty($validated['columns'])) {
            $orderedColumns = [];
            foreach ($validated['columns'] as $col) {
                if (isset($availableColumns[$col])) {
                    $orderedColumns[$col] = $availableColumns[$col];
                }
            }
            $columns = $orderedColumns;
        }

        $rows = $products->map(function (Product $product) use ($validated, $columns, $stockData) {
            // STILL-V14-CRITICAL-01 FIX: Use stock from stock_movements (source of truth)
            $stock = decimal_float($stockData[$product->id] ?? 0);
            $reorder = $product->reorder_point ?? 0;

            if (! empty($validated['only_low']) && $reorder > 0 && $stock > $reorder) {
                return null;
            }

            $row = [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'stock_quantity' => $stock,
                'reorder_point' => $reorder,
            ];

            // Return only selected columns
            return array_intersect_key($row, $columns);
        })->filter()->values()->toArray();

        if ($format === 'excel') {
            $filename = 'inventory_report_'.now()->format('Ymd_His').'.xlsx';

            $spreadsheet = new Spreadsheet;
            $sheet = $spreadsheet->getActiveSheet();

            // Set headers
            $col = 1;
            foreach ($columns as $header) {
                $sheet->setCellValueByColumnAndRow($col, 1, $header);
                $col++;
            }

            // Set data rows
            $rowNum = 2;
            foreach ($rows as $row) {
                $col = 1;
                foreach (array_keys($columns) as $key) {
                    $value = $row[$key] ?? '';
                    $sheet->setCellValueByColumnAndRow($col, $rowNum, is_scalar($value) ? $value : json_encode($value));
                    $col++;
                }
                $rowNum++;
            }

            // Auto-size columns
            foreach (range(1, count($columns)) as $col) {
                $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
            }

            // Style header row
            $headerStyle = $sheet->getStyle('1:1');
            $headerStyle->getFont()->setBold(true);
            $headerStyle->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFE0E0E0');

            $response = new StreamedResponse(function () use ($spreadsheet): void {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
            });

            $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');
            $response->headers->set('Cache-Control', 'max-age=0');

            return $response;
        }

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('admin.reports.inventory-export-pdf', [
                'columns' => $columns,
                'rows' => $rows,
            ]);

            return $pdf->download('inventory_report_'.now()->format('Ymd_His').'.pdf');
        }

        return view('admin.reports.inventory-export-web', [
            'columns' => $columns,
            'rows' => $rows,
        ]);
    }
}
