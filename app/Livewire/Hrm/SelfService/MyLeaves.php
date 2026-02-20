<?php

declare(strict_types=1);

namespace App\Livewire\Hrm\SelfService;

use App\Models\LeaveRequest;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use App\Livewire\BaseComponent as Component;
use Livewire\WithPagination;

/**
 * My Leaves - Employee Self Service
 * Allows employees to view and request leaves
 */
class MyLeaves extends Component
{
    use WithPagination;

    public ?string $status = null;

    public ?string $year = null;

    // For creating new leave request
    public bool $showRequestModal = false;

    public ?string $leaveType = null;

    public ?string $startDate = null;

    public ?string $endDate = null;

    public ?string $reason = null;

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user || ! $user->can('employee.self.leave-request')) {
            abort(403);
        }

        $this->year = now()->format('Y');
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingYear(): void
    {
        $this->resetPage();
    }

    /**
     * Open the leave request modal
     */
    public function openRequestModal(): void
    {
        $this->reset(['leaveType', 'startDate', 'endDate', 'reason']);
        $this->showRequestModal = true;
    }

    /**
     * Close the leave request modal
     */
    public function closeRequestModal(): void
    {
        $this->showRequestModal = false;
    }

    /**
     * Submit a new leave request
     */
    public function submitRequest(): void
    {
        $user = Auth::user();

        if (! $user || ! $user->can('employee.self.leave-request')) {
            abort(403);
        }

        $this->validate([
            'leaveType' => 'required|string',
            'startDate' => 'required|date',
            'endDate' => 'required|date|after_or_equal:startDate',
            'reason' => 'nullable|string|max:500',
        ]);

        $startDate = \Carbon\Carbon::parse($this->startDate);
        $endDate = \Carbon\Carbon::parse($this->endDate);
        $daysCount = $startDate->diffInDays($endDate) + 1;

        LeaveRequest::create([
            'employee_id' => $user->employee_id,
            'leave_type' => $this->leaveType,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'days_count' => $daysCount,
            'reason' => $this->reason,
            'status' => 'pending',
        ]);

        $this->closeRequestModal();
        $this->dispatch('notify', type: 'success', message: __('Leave request submitted successfully.'));
    }

    /**
     * Cancel a pending leave request
     */
    public function cancelRequest(int $requestId): void
    {
        $user = Auth::user();

        $request = LeaveRequest::where('id', $requestId)
            ->where('employee_id', $user->employee_id)
            ->where('status', 'pending')
            ->first();

        if ($request) {
            $request->update(['status' => 'cancelled']);
            $this->dispatch('notify', type: 'success', message: __('Leave request cancelled.'));
        }
    }

    /**
     * Get leave balance for the current year
     */
    public function getLeaveBalance(): array
    {
        $user = Auth::user();

        if (! $user || ! $user->employee_id) {
            return [
                'annual' => ['total' => 0, 'used' => 0, 'remaining' => 0],
                'sick' => ['total' => 0, 'used' => 0, 'remaining' => 0],
            ];
        }

        // This is a simplified calculation - actual implementation may vary
        $approvedLeaves = LeaveRequest::where('employee_id', $user->employee_id)
            ->where('status', 'approved')
            ->whereYear('start_date', $this->year)
            ->get();

        $annualUsed = $approvedLeaves->where('leave_type', 'annual')->sum('days_count');
        $sickUsed = $approvedLeaves->where('leave_type', 'sick')->sum('days_count');

        return [
            'annual' => ['total' => 21, 'used' => $annualUsed, 'remaining' => max(0, 21 - $annualUsed)],
            'sick' => ['total' => 10, 'used' => $sickUsed, 'remaining' => max(0, 10 - $sickUsed)],
        ];
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $user = Auth::user();

        if (! $user || ! $user->can('employee.self.leave-request')) {
            abort(403);
        }

        $employeeId = $user->employee_id ?? null;

        $records = collect();

        if ($employeeId) {
            $records = LeaveRequest::where('employee_id', $employeeId)
                ->when($this->status, fn ($q) => $q->where('status', $this->status))
                ->when($this->year, fn ($q) => $q->whereYear('start_date', $this->year))
                ->orderByDesc('created_at')
                ->paginate(15);
        }

        return view('livewire.hrm.self-service.my-leaves', [
            'records' => $records,
            'leaveBalance' => $this->getLeaveBalance(),
            'leaveTypes' => ['annual', 'sick', 'unpaid', 'maternity', 'emergency'],
        ]);
    }
}
