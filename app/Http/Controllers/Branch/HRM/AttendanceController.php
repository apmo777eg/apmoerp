<?php

declare(strict_types=1);

namespace App\Http\Controllers\Branch\HRM;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\HREmployee;
use App\Services\Contracts\HRMServiceInterface as HRM;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct(protected HRM $hrm) {}

    public function index(Branch $branch, Request $request)
    {
        $per = min(max($request->integer('per_page', 20), 1), 100);

        // V46-CRIT-01 FIX: Use canonical column names from Attendance model
        // Model uses: attendance_date, clock_in, clock_out (not date, check_in, check_out)
        $query = Attendance::query()
            ->where('branch_id', $branch->getKey())
            ->orderByDesc('attendance_date')
            ->orderByDesc('clock_in');

        // Apply filters
        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->integer('employee_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        return $this->ok($query->paginate($per));
    }

    public function log(Branch $branch, Request $request)
    {
        $data = $this->validate($request, [
            'employee_id' => ['required', 'exists:hr_employees,id'],
            'type' => ['required', 'in:in,out'],
            'at' => ['sometimes', 'date'],
        ]);

        // Ensure employee belongs to branch
        HREmployee::where('branch_id', $branch->getKey())->findOrFail($data['employee_id']);

        return $this->ok($this->hrm->logAttendance($data['employee_id'], $data['type'], $request->input('at', now()->toDateTimeString())));
    }

    public function approve(Branch $branch, Attendance $record)
    {
        abort_if($record->branch_id !== $branch->getKey(), 404);

        return $this->ok($this->hrm->approveAttendance($record->id));
    }

    public function store(Branch $branch, Request $request)
    {
        $data = $this->validate($request, [
            'employee_id' => ['required', 'exists:hr_employees,id'],
            'date' => ['required', 'date'],
            'check_in' => ['required', 'date_format:H:i:s'],
            'check_out' => ['nullable', 'date_format:H:i:s'],
            'status' => ['nullable', 'string', 'in:present,absent,late,on_leave,pending,inactive'],
        ]);

        // Ensure employee belongs to branch
        HREmployee::where('branch_id', $branch->getKey())->findOrFail($data['employee_id']);

        // V46-CRIT-01 FIX: Use canonical column names from Attendance model
        // Model uses: attendance_date, clock_in, clock_out (not date, check_in, check_out)
        $attendance = Attendance::updateOrCreate(
            [
                'employee_id' => $data['employee_id'],
                'attendance_date' => $data['date'],
            ],
            [
                'branch_id' => $branch->getKey(),
                'clock_in' => $data['check_in'],
                'clock_out' => $data['check_out'] ?? null,
                'status' => $data['status'] ?? 'pending',
            ]
        );

        return $this->ok($attendance->fresh());
    }

    public function update(Branch $branch, Attendance $record, Request $request)
    {
        abort_if($record->branch_id !== $branch->getKey(), 404);

        $data = $this->validate($request, [
            'check_in' => ['nullable', 'date_format:H:i:s'],
            'check_out' => ['nullable', 'date_format:H:i:s'],
            'status' => ['nullable', 'string', 'in:present,absent,late,on_leave,pending,inactive'],
        ]);

        $record->fill($data);
        $record->save();

        return $this->ok($record->fresh());
    }

    public function deactivate(Branch $branch, Attendance $record)
    {
        abort_if($record->branch_id !== $branch->getKey(), 404);

        $record->status = 'inactive';
        $record->save();

        return $this->ok($record->fresh(), __('Deactivated'));
    }
}
