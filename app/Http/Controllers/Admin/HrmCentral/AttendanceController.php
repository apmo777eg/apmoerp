<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\HrmCentral;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $per = min(max($request->integer('per_page', 20), 1), 100);
        // V46-CRIT-01 FIX: Use canonical column name 'attendance_date' instead of 'logged_at'
        $q = Attendance::query()->orderByDesc('attendance_date');
        if ($request->filled('employee_id')) {
            $q->where('employee_id', $request->integer('employee_id'));
        }
        if ($request->filled('status')) {
            $q->where('status', $request->input('status'));
        }

        return $this->ok($q->paginate($per));
    }

    /**
     * NEW-V15-CRITICAL-02 FIX: Store a new attendance record
     * V46-CRIT-01 FIX: Use canonical column names from Attendance model
     * and correct table name 'hr_employees' instead of 'employees'
     */
    public function store(Request $request)
    {
        $validated = $this->validate($request, [
            'employee_id' => ['required', 'integer', 'exists:hr_employees,id'],
            'attendance_date' => ['required', 'date'],
            'clock_in' => ['nullable', 'date_format:H:i:s'],
            'clock_out' => ['nullable', 'date_format:H:i:s'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $attendance = Attendance::create([
            'employee_id' => $validated['employee_id'],
            'attendance_date' => $validated['attendance_date'],
            'clock_in' => $validated['clock_in'] ?? null,
            'clock_out' => $validated['clock_out'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => 'pending',
            // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
            'created_by' => actual_user_id(),
        ]);

        return $this->ok($attendance, __('Attendance record created'), 201);
    }

    /**
     * NEW-V15-CRITICAL-02 FIX: Update an attendance record
     * V46-CRIT-01 FIX: Use canonical column names from Attendance model
     */
    public function update(Request $request, Attendance $attendance)
    {
        $validated = $this->validate($request, [
            'attendance_date' => ['sometimes', 'date'],
            'clock_in' => ['nullable', 'date_format:H:i:s'],
            'clock_out' => ['nullable', 'date_format:H:i:s'],
            'notes' => ['nullable', 'string', 'max:500'],
            'status' => ['sometimes', 'string', 'in:pending,approved,rejected,present,absent,late,on_leave'],
        ]);

        $attendance->update($validated);

        return $this->ok($attendance, __('Attendance record updated'));
    }

    /**
     * NEW-V15-CRITICAL-02 FIX: Deactivate an attendance record
     */
    public function deactivate(Attendance $attendance)
    {
        $attendance->status = 'deactivated';
        $attendance->save();

        return $this->ok($attendance, __('Attendance record deactivated'));
    }

    public function approve(Attendance $record)
    {
        $record->status = 'approved';
        $record->approved_at = now();
        $record->save();

        return $this->ok($record, __('Approved'));
    }
}
