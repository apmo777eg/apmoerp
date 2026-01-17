<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\HrmCentral;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $per = min(max($request->integer('per_page', 20), 1), 100);
        $q = LeaveRequest::query()->orderByDesc('created_at');
        if ($request->filled('employee_id')) {
            $q->where('employee_id', $request->integer('employee_id'));
        }
        if ($request->filled('status')) {
            $q->where('status', $request->input('status'));
        }

        return $this->ok($q->paginate($per));
    }

    /**
     * NEW-V15-CRITICAL-02 FIX: Approve a leave request
     */
    public function approve(LeaveRequest $leave)
    {
        $leave->status = 'approved';
        $leave->approved_at = now();
        // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
        $leave->approved_by = actual_user_id();
        $leave->save();

        return $this->ok($leave, __('Leave request approved'));
    }

    /**
     * NEW-V15-CRITICAL-02 FIX: Reject a leave request
     */
    public function reject(Request $request, LeaveRequest $leave)
    {
        $validated = $this->validate($request, [
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $leave->status = 'rejected';
        $leave->rejection_reason = $validated['reason'] ?? null;
        // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
        $leave->rejected_by = actual_user_id();
        $leave->save();

        return $this->ok($leave, __('Leave request rejected'));
    }

    public function updateStatus(Request $request, LeaveRequest $leave)
    {
        $data = $this->validate($request, ['status' => ['required', 'in:pending,approved,rejected']]);
        $leave->status = $data['status'];
        $leave->save();

        return $this->ok($leave, __('Status updated'));
    }
}
