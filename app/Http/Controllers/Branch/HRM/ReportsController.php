<?php

namespace App\Http\Controllers\Branch\HRM;

use Carbon\Carbon;

use App\Http\Controllers\Controller;
use App\Rules\BranchScopedExists;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsController extends Controller
{
    public function attendance(Request $request): StreamedResponse
    {
        // V57-CRITICAL-03 FIX: Use BranchScopedExists to prevent cross-branch employee references
        $validated = $request->validate([
            'employee_id' => ['nullable', 'integer', new BranchScopedExists('hr_employees', 'id', null, true)],
            'status' => 'nullable|string|in:present,absent,late,on_leave',
            'branch_id' => 'nullable|integer',
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        $model = '\\App\\Models\\Attendance';

        if (! class_exists($model)) {
            abort(500, 'Attendance model not found');
        }

        /** @var \App\Models\Attendance $model */
        $query = $model::query()->with('employee');

        if (! empty($validated['employee_id'])) {
            $query->where('employee_id', $validated['employee_id']);
        }

        if (! empty($validated['status'])) {
            if ($validated['status'] === 'pending') {
                $query->pending();
            } else {
                $query->where('status', $validated['status']);
            }
        }

        if (! empty($validated['branch_id'])) {
            $query->where('branch_id', $validated['branch_id']);
        }

        // V46-CRIT-01 FIX: Use canonical column name 'attendance_date' instead of 'date'
        if (! empty($validated['from'])) {
            $query->whereDate('attendance_date', '>=', $validated['from']);
        }

        if (! empty($validated['to'])) {
            $query->whereDate('attendance_date', '<=', $validated['to']);
        }

        $filename = 'hrm_attendance_'.now()->format('Ymd_His').'.xlsx';

        $callback = function () use ($query) {
            $spreadsheet = new Spreadsheet;
            $sheet = $spreadsheet->getActiveSheet();

            // Set headers
            $headers = ['Employee', 'Date', 'Check in', 'Check out', 'Status', 'Approved at'];
            $col = 1;
            foreach ($headers as $header) {
                $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . 1, $header);
                $col++;
            }

            // Style header row
            $headerStyle = $sheet->getStyle('1:1');
            $headerStyle->getFont()->setBold(true);
            $headerStyle->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFE0E0E0');

            // Add data
            $rowNum = 2;
            $query->chunk(500, function ($rows) use ($sheet, &$rowNum) {
                foreach ($rows as $row) {
                    $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(1) . $rowNum, optional($row->employee)->name ?? '');
                    $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(2) . $rowNum, $row->date);
                    $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(3) . $rowNum, $row->check_in);
                    $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(4) . $rowNum, $row->check_out);
                    $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(5) . $rowNum, $row->status);
                    $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(6) . $rowNum, $row->approved_at);
                    $rowNum++;
                }
            });

            // Auto-size columns
            foreach (range(1, count($headers)) as $col) {
                $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function payroll(Request $request): StreamedResponse
    {
        // V57-CRITICAL-03 FIX: Use BranchScopedExists to prevent cross-branch employee references
        $validated = $request->validate([
            'employee_id' => ['nullable', 'integer', new BranchScopedExists('hr_employees', 'id', null, true)],
            'period' => 'nullable|date_format:Y-m',
            'status' => 'nullable|string|in:pending,draft,calculated,approved,paid,cancelled',
        ]);

        $model = '\\App\\Models\\Payroll';

        if (! class_exists($model)) {
            abort(500, 'Payroll model not found');
        }

        /** @var \App\Models\Payroll $model */
        $query = $model::query()->with('employee');

        if (! empty($validated['employee_id'])) {
            $query->where('employee_id', $validated['employee_id']);
        }

        if (! empty($validated['period'])) {
            $dt = Carbon::createFromFormat('Y-m', (string) $validated['period']);
            $query->where('year', (int) $dt->year)->where('month', (int) $dt->month);
        }

        if (! empty($validated['status'])) {
            if ($validated['status'] === 'pending') {
                $query->pending();
            } else {
                $query->where('status', $validated['status']);
            }
        }

        $filename = 'hrm_payroll_'.now()->format('Ymd_His').'.xlsx';

        $callback = function () use ($query) {
            $spreadsheet = new Spreadsheet;
            $sheet = $spreadsheet->getActiveSheet();

            // Set headers
            $headers = ['Employee', 'Period', 'Basic', 'Allowances', 'Deductions', 'Net', 'Status', 'Paid at'];
            $col = 1;
            foreach ($headers as $header) {
                $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . 1, $header);
                $col++;
            }

            // Style header row
            $headerStyle = $sheet->getStyle('1:1');
            $headerStyle->getFont()->setBold(true);
            $headerStyle->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFE0E0E0');

            // Add data
            $rowNum = 2;
            $query->chunk(500, function ($rows) use ($sheet, &$rowNum) {
                foreach ($rows as $row) {
                    $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(1) . $rowNum, optional($row->employee)->name ?? '');
                    $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(2) . $rowNum, $row->period);
                    $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(3) . $rowNum, $row->basic);
                    $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(4) . $rowNum, $row->allowances);
                    $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(5) . $rowNum, $row->deductions);
                    $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(6) . $rowNum, $row->net);
                    $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(7) . $rowNum, $row->status);
                    $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(8) . $rowNum, $row->paid_at);
                    $rowNum++;
                }
            });

            // Auto-size columns
            foreach (range(1, count($headers)) as $col) {
                $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
