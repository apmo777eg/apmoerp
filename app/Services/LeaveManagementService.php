<?php

namespace App\Services;

use App\Models\LeaveType;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveRequestApproval;
use App\Models\LeaveAdjustment;
use App\Models\LeaveHoliday;
use App\Models\LeaveAccrualRule;
use App\Models\LeaveEncashment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

/**
 * Service class for comprehensive leave management operations.
 * 
 * Handles the complete leave lifecycle including:
 * - Leave type configuration
 * - Employee leave balances
 * - Leave request approval workflows
 * - Accrual automation
 * - Leave encashment
 * - Holiday management
 */
class LeaveManagementService
{
    /**
     * Initialize leave balance for an employee
     *
     * @param int $employeeId Employee ID
     * @param int $leaveTypeId Leave type ID
     * @param int $year Year for balance
     * @param float $quota Initial quota
     * @return LeaveBalance Created leave balance
     */
    public function initializeBalance(int $employeeId, int $leaveTypeId, int $year, float $quota): LeaveBalance
    {
        return LeaveBalance::create([
            'employee_id' => $employeeId,
            'leave_type_id' => $leaveTypeId,
            'year' => $year,
            'opening_balance' => $quota,
            'accrued' => 0,
            'used' => 0,
            'adjusted' => 0,
            'carried_forward' => 0,
            'encashed' => 0,
            'available_balance' => $quota,
            'expires_at' => null,
        ]);
    }
    
    /**
     * Get or create leave balance for employee
     *
     * @param int $employeeId Employee ID
     * @param int $leaveTypeId Leave type ID
     * @param int|null $year Year (defaults to current year)
     * @return LeaveBalance Leave balance
     */
    public function getOrCreateBalance(int $employeeId, int $leaveTypeId, ?int $year = null): LeaveBalance
    {
        $year = $year ?? Carbon::now()->year;
        
        return LeaveBalance::firstOrCreate([
            'employee_id' => $employeeId,
            'leave_type_id' => $leaveTypeId,
            'year' => $year,
        ], [
            'opening_balance' => 0,
            'accrued' => 0,
            'used' => 0,
            'adjusted' => 0,
            'carried_forward' => 0,
            'encashed' => 0,
            'available_balance' => 0,
        ]);
    }
    
    /**
     * Adjust leave balance manually
     *
     * @param int $employeeId Employee ID
     * @param int $leaveTypeId Leave type ID
     * @param float $days Days to adjust
     * @param string $type Adjustment type
     * @param string $reason Reason for adjustment
     * @return LeaveAdjustment Created adjustment
     */
    public function adjustBalance(int $employeeId, int $leaveTypeId, float $days, string $type, string $reason): LeaveAdjustment
    {
        return DB::transaction(function () use ($employeeId, $leaveTypeId, $days, $type, $reason) {
            $balance = $this->getOrCreateBalance($employeeId, $leaveTypeId);
            
            // Create adjustment record
            $adjustment = LeaveAdjustment::create([
                'employee_id' => $employeeId,
                'leave_type_id' => $leaveTypeId,
                'adjustment_type' => $type,
                'days' => $days,
                'reason' => $reason,
                'adjusted_by' => Auth::id(),
            ]);
            
            // Update balance
            if (in_array($type, [LeaveAdjustment::TYPE_ADDITION, LeaveAdjustment::TYPE_CARRY_FORWARD])) {
                $balance->adjusted += $days;
                $balance->available_balance += $days;
            } elseif (in_array($type, [LeaveAdjustment::TYPE_DEDUCTION, LeaveAdjustment::TYPE_CORRECTION])) {
                $balance->adjusted -= $days;
                $balance->available_balance -= $days;
            }
            
            $balance->save();
            
            return $adjustment;
        });
    }
    
    /**
     * Process leave accrual for an employee
     *
     * @param int $employeeId Employee ID
     * @param int $leaveTypeId Leave type ID
     * @param Carbon $date Date to process accrual for
     * @return float Accrued amount
     */
    public function processAccrual(int $employeeId, int $leaveTypeId, Carbon $date): float
    {
        return DB::transaction(function () use ($employeeId, $leaveTypeId, $date) {
            $accrualRule = LeaveAccrualRule::where('leave_type_id', $leaveTypeId)
                ->where('is_active', true)
                ->first();
            
            if (!$accrualRule) {
                return 0;
            }
            
            // Get or create balance
            $balance = $this->getOrCreateBalance($employeeId, $leaveTypeId, $date->year);
            
            // Calculate accrual amount based on frequency
            $accrualAmount = $this->calculateAccrualAmount($accrualRule, $date, $employeeId);
            
            if ($accrualAmount > 0) {
                $balance->accrued += $accrualAmount;
                $balance->available_balance += $accrualAmount;
                $balance->save();
            }
            
            return $accrualAmount;
        });
    }
    
    /**
     * Calculate accrual amount based on rule and proration
     *
     * @param LeaveAccrualRule $rule Accrual rule
     * @param Carbon $date Accrual date
     * @param int $employeeId Employee ID
     * @return float Accrual amount
     */
    protected function calculateAccrualAmount(LeaveAccrualRule $rule, Carbon $date, int $employeeId): float
    {
        $accrualAmount = $rule->accrual_amount;
        
        // Apply proration if enabled
        if ($rule->prorate_on_joining || $rule->prorate_on_leaving) {
            // This would integrate with your employee service to get joining/leaving dates
            // For now, return the base amount
            // Example:
            // $employee = Employee::find($employeeId);
            // if ($rule->prorate_on_joining && $employee->joining_date->isSameMonth($date)) {
            //     $daysInMonth = $date->daysInMonth;
            //     $workingDays = $daysInMonth - $employee->joining_date->day + 1;
            //     $accrualAmount = ($accrualAmount / $daysInMonth) * $workingDays;
            // }
        }
        
        // Apply maximum accrual limit
        if ($rule->max_accrual_limit) {
            $balance = $this->getOrCreateBalance($employeeId, $rule->leave_type_id);
            $potentialBalance = $balance->available_balance + $accrualAmount;
            
            if ($potentialBalance > $rule->max_accrual_limit) {
                $accrualAmount = max(0, $rule->max_accrual_limit - $balance->available_balance);
            }
        }
        
        return $accrualAmount;
    }
    
    /**
     * Process bulk accruals for all employees
     *
     * @param Carbon $date Date to process accruals for
     * @return array Results of accrual processing
     */
    public function processBulkAccruals(Carbon $date): array
    {
        $results = [];
        
        // Get all active accrual rules
        $rules = LeaveAccrualRule::where('is_active', true)->with('leaveType')->get();
        
        foreach ($rules as $rule) {
            // Check if accrual should run based on frequency
            if (!$this->shouldProcessAccrual($rule, $date)) {
                continue;
            }
            
            // Get all active employees (integrate with your employee model)
            // For now, get all employees from leave_balances
            $employeeIds = LeaveBalance::where('leave_type_id', $rule->leave_type_id)
                ->distinct()
                ->pluck('employee_id');
            
            foreach ($employeeIds as $employeeId) {
                try {
                    $accrued = $this->processAccrual($employeeId, $rule->leave_type_id, $date);
                    $results[] = [
                        'employee_id' => $employeeId,
                        'leave_type_id' => $rule->leave_type_id,
                        'accrued' => $accrued,
                        'status' => 'success',
                    ];
                } catch (\Exception $e) {
                    $results[] = [
                        'employee_id' => $employeeId,
                        'leave_type_id' => $rule->leave_type_id,
                        'status' => 'failed',
                        'error' => $e->getMessage(),
                    ];
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Check if accrual should be processed based on frequency
     *
     * @param LeaveAccrualRule $rule Accrual rule
     * @param Carbon $date Date to check
     * @return bool Whether to process accrual
     */
    protected function shouldProcessAccrual(LeaveAccrualRule $rule, Carbon $date): bool
    {
        switch ($rule->accrual_frequency) {
            case LeaveAccrualRule::FREQUENCY_MONTHLY:
                // Process on first day of month
                return $date->day === 1;
                
            case LeaveAccrualRule::FREQUENCY_QUARTERLY:
                // Process on first day of quarter (Jan, Apr, Jul, Oct)
                return $date->day === 1 && in_array($date->month, [1, 4, 7, 10]);
                
            case LeaveAccrualRule::FREQUENCY_YEARLY:
                // Process on first day of year
                return $date->month === 1 && $date->day === 1;
                
            case LeaveAccrualRule::FREQUENCY_BIWEEKLY:
                // Process every two weeks (simplified - check if week number is even)
                return $date->dayOfWeek === Carbon::MONDAY && ($date->weekOfYear % 2 === 0);
                
            default:
                return false;
        }
    }
    
    /**
     * Process leave encashment request
     *
     * @param int $employeeId Employee ID
     * @param int $leaveTypeId Leave type ID
     * @param float $days Days to encash
     * @param float $ratePerDay Rate per day
     * @return LeaveEncashment Created encashment
     */
    public function createEncashment(int $employeeId, int $leaveTypeId, float $days, float $ratePerDay): LeaveEncashment
    {
        return DB::transaction(function () use ($employeeId, $leaveTypeId, $days, $ratePerDay) {
            $balance = $this->getOrCreateBalance($employeeId, $leaveTypeId);
            
            // Check if employee has sufficient balance
            if (!$balance->hasSufficientBalance($days)) {
                throw new \Exception('Insufficient leave balance for encashment');
            }
            
            // Create encashment record
            $encashment = LeaveEncashment::create([
                'employee_id' => $employeeId,
                'leave_type_id' => $leaveTypeId,
                'days_encashed' => $days,
                'rate_per_day' => $ratePerDay,
                'total_amount' => $days * $ratePerDay,
                'status' => LeaveEncashment::STATUS_PENDING,
                'requested_by' => Auth::id(),
            ]);
            
            return $encashment;
        });
    }
    
    /**
     * Approve leave encashment
     *
     * @param int $encashmentId Encashment ID
     * @return LeaveEncashment Approved encashment
     */
    public function approveEncashment(int $encashmentId): LeaveEncashment
    {
        return DB::transaction(function () use ($encashmentId) {
            $encashment = LeaveEncashment::findOrFail($encashmentId);
            
            if (!$encashment->canBeApproved()) {
                throw new \Exception('Encashment cannot be approved in current status');
            }
            
            // Update encashment status
            $encashment->update([
                'status' => LeaveEncashment::STATUS_APPROVED,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);
            
            // Deduct from leave balance
            $balance = $this->getOrCreateBalance($encashment->employee_id, $encashment->leave_type_id);
            $balance->encashed += $encashment->days_encashed;
            $balance->available_balance -= $encashment->days_encashed;
            $balance->save();
            
            return $encashment->fresh();
        });
    }
    
    /**
     * Process year-end carry forward for all employees
     *
     * @param int $fromYear Source year
     * @param int $toYear Target year
     * @return array Results of carry forward processing
     */
    public function processYearEndCarryForward(int $fromYear, int $toYear): array
    {
        $results = [];
        
        // Get all leave types with carry forward enabled
        $leaveTypes = LeaveType::where('allow_carry_forward', true)->get();
        
        foreach ($leaveTypes as $leaveType) {
            // Get all balances for the from year
            $balances = LeaveBalance::where('leave_type_id', $leaveType->id)
                ->where('year', $fromYear)
                ->get();
            
            foreach ($balances as $balance) {
                try {
                    $carriedForward = $this->carryForwardBalance($balance, $toYear, $leaveType);
                    
                    $results[] = [
                        'employee_id' => $balance->employee_id,
                        'leave_type_id' => $leaveType->id,
                        'carried_forward' => $carriedForward,
                        'status' => 'success',
                    ];
                } catch (\Exception $e) {
                    $results[] = [
                        'employee_id' => $balance->employee_id,
                        'leave_type_id' => $leaveType->id,
                        'status' => 'failed',
                        'error' => $e->getMessage(),
                    ];
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Carry forward balance to next year
     *
     * @param LeaveBalance $balance Source balance
     * @param int $toYear Target year
     * @param LeaveType $leaveType Leave type
     * @return float Amount carried forward
     */
    protected function carryForwardBalance(LeaveBalance $balance, int $toYear, LeaveType $leaveType): float
    {
        // Calculate amount to carry forward
        $availableToCarryForward = $balance->available_balance;
        
        // Apply maximum carry forward limit if set
        if ($leaveType->carry_forward_max_days) {
            $availableToCarryForward = min($availableToCarryForward, $leaveType->carry_forward_max_days);
        }
        
        if ($availableToCarryForward <= 0) {
            return 0;
        }
        
        // Get or create balance for next year
        $nextYearBalance = $this->getOrCreateBalance($balance->employee_id, $leaveType->id, $toYear);
        
        // Add to next year's balance
        $nextYearBalance->carried_forward += $availableToCarryForward;
        $nextYearBalance->opening_balance += $availableToCarryForward;
        $nextYearBalance->available_balance += $availableToCarryForward;
        
        // Set expiry date if applicable
        if ($leaveType->carry_forward_expires_in_days) {
            $nextYearBalance->expires_at = Carbon::create($toYear, 1, 1)
                ->addDays($leaveType->carry_forward_expires_in_days);
        }
        
        $nextYearBalance->save();
        
        // Create adjustment record
        $this->adjustBalance(
            $balance->employee_id,
            $leaveType->id,
            $availableToCarryForward,
            LeaveAdjustment::TYPE_CARRY_FORWARD,
            "Carried forward from year {$balance->year}"
        );
        
        return $availableToCarryForward;
    }
    
    /**
     * Calculate actual working days excluding holidays
     *
     * @param Carbon $startDate Start date
     * @param Carbon $endDate End date
     * @param int|null $branchId Branch ID for branch-specific holidays
     * @return float Actual working days
     */
    public function calculateWorkingDays(Carbon $startDate, Carbon $endDate, ?int $branchId = null): float
    {
        // Get holidays in the date range
        $holidays = LeaveHoliday::inDateRange($startDate, $endDate);
        
        if ($branchId) {
            $holidays = $holidays->forBranch($branchId);
        }
        
        $holidayDates = $holidays->pluck('date')->map(function ($date) {
            return Carbon::parse($date)->format('Y-m-d');
        })->toArray();
        
        // Calculate working days
        $workingDays = 0;
        $currentDate = $startDate->copy();
        
        while ($currentDate <= $endDate) {
            // Skip weekends (Friday and Saturday)
            if (!in_array($currentDate->dayOfWeek, [Carbon::FRIDAY, Carbon::SATURDAY])) {
                // Check if it's not a holiday
                if (!in_array($currentDate->format('Y-m-d'), $holidayDates)) {
                    $workingDays++;
                }
            }
            
            $currentDate->addDay();
        }
        
        return $workingDays;
    }
    
    /**
     * Get leave statistics for an employee
     *
     * @param int $employeeId Employee ID
     * @param int|null $year Year (defaults to current year)
     * @return array Statistics
     */
    public function getEmployeeLeaveStatistics(int $employeeId, ?int $year = null): array
    {
        $year = $year ?? Carbon::now()->year;
        
        $balances = LeaveBalance::where('employee_id', $employeeId)
            ->where('year', $year)
            ->with('leaveType')
            ->get();
        
        $statistics = [];
        
        foreach ($balances as $balance) {
            $statistics[] = [
                'leave_type' => $balance->leaveType->name,
                'leave_type_code' => $balance->leaveType->code,
                'opening_balance' => $balance->opening_balance,
                'accrued' => $balance->accrued,
                'used' => $balance->used,
                'adjusted' => $balance->adjusted,
                'carried_forward' => $balance->carried_forward,
                'encashed' => $balance->encashed,
                'available_balance' => $balance->available_balance,
                'utilization_rate' => $balance->getUtilizationRate(),
            ];
        }
        
        return $statistics;
    }

    /**
     * Create a leave request with proper holiday deduction.
     *
     * BUG FIX: Calculates actual working days excluding public holidays.
     * Per labor law, public holidays falling within a leave period should NOT
     * be deducted from the employee's annual leave balance.
     *
     * @param int $employeeId Employee ID
     * @param int $leaveTypeId Leave type ID
     * @param Carbon $startDate Leave start date
     * @param Carbon $endDate Leave end date
     * @param string|null $reason Leave reason
     * @param int|null $branchId Branch ID for branch-specific holidays
     * @return LeaveRequest Created leave request
     * @throws \Exception If insufficient balance
     */
    public function createLeaveRequest(
        int $employeeId,
        int $leaveTypeId,
        Carbon $startDate,
        Carbon $endDate,
        ?string $reason = null,
        ?int $branchId = null
    ): LeaveRequest {
        return DB::transaction(function () use ($employeeId, $leaveTypeId, $startDate, $endDate, $reason, $branchId) {
            // Calculate actual working days excluding weekends and holidays
            $actualDays = $this->calculateWorkingDays($startDate, $endDate, $branchId);
            
            // Get employee's leave balance
            $balance = $this->getOrCreateBalance($employeeId, $leaveTypeId, $startDate->year);
            
            // Check if employee has sufficient balance
            if ($balance->available_balance < $actualDays) {
                throw new \Exception(
                    __('Insufficient leave balance. Available: :available days, Required: :required days', [
                        'available' => $balance->available_balance,
                        'required' => $actualDays,
                    ])
                );
            }
            
            // Get the leave type to determine the leave_type string
            $leaveType = LeaveType::find($leaveTypeId);
            $leaveTypeCode = $leaveType?->code ?? 'annual';
            
            // Get holidays in the date range for documentation
            $holidays = LeaveHoliday::inDateRange($startDate, $endDate);
            if ($branchId) {
                $holidays = $holidays->forBranch($branchId);
            }
            $holidayCount = $holidays->count();
            $calendarDays = $startDate->diffInDays($endDate) + 1;
            
            // Create leave request with calculated actual days
            $leaveRequest = LeaveRequest::create([
                'employee_id' => $employeeId,
                'leave_type' => $leaveTypeCode,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'days_count' => $actualDays, // BUG FIX: Use actual working days, not calendar days
                'status' => LeaveRequest::STATUS_PENDING,
                'reason' => $reason,
                'extra_attributes' => [
                    'leave_type_id' => $leaveTypeId,
                    'calendar_days' => $calendarDays,
                    'holidays_excluded' => $holidayCount,
                    'weekends_excluded' => $calendarDays - $actualDays - $holidayCount,
                ],
            ]);
            
            return $leaveRequest;
        });
    }

    /**
     * Approve a leave request and deduct from balance.
     *
     * BUG FIX: Uses the pre-calculated days_count (which excludes holidays)
     * rather than recalculating from raw dates.
     *
     * @param int $leaveRequestId Leave request ID
     * @param int|null $approverId Approver user ID
     * @param string|null $note Approval note
     * @return LeaveRequest Approved request
     */
    public function approveLeaveRequest(
        int $leaveRequestId,
        ?int $approverId = null,
        ?string $note = null
    ): LeaveRequest {
        return DB::transaction(function () use ($leaveRequestId, $approverId, $note) {
            $leaveRequest = LeaveRequest::findOrFail($leaveRequestId);
            
            if (!$leaveRequest->canBeApproved()) {
                throw new \Exception(__('Leave request cannot be approved in its current status'));
            }
            
            // Get leave type ID from extra_attributes or look up
            $leaveTypeId = $leaveRequest->extra_attributes['leave_type_id'] 
                ?? LeaveType::where('code', $leaveRequest->leave_type)->value('id');
            
            if ($leaveTypeId) {
                // Get and update balance
                $balance = $this->getOrCreateBalance(
                    $leaveRequest->employee_id,
                    $leaveTypeId,
                    $leaveRequest->start_date->year
                );
                
                // Use the pre-calculated days_count which already excludes holidays
                $daysToDeduct = decimal_float($leaveRequest->days_count);
                
                // Verify balance is still sufficient
                if ($balance->available_balance < $daysToDeduct) {
                    throw new \Exception(
                        __('Insufficient leave balance. Available: :available days', [
                            'available' => $balance->available_balance,
                        ])
                    );
                }
                
                // Deduct from balance
                $balance->used += $daysToDeduct;
                $balance->available_balance -= $daysToDeduct;
                $balance->save();
            }
            
            // Approve the request
            $leaveRequest->approve($approverId, $note);
            
            return $leaveRequest->fresh();
        });
    }
}
