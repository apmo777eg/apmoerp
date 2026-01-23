<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Attendance;
use App\Models\HREmployee;
use App\Models\Payroll;
use App\Services\Contracts\HRMServiceInterface;
use App\Traits\HandlesServiceErrors;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Factory as ValidatorFactory;

class HRMService implements HRMServiceInterface
{
    use HandlesServiceErrors;

    public function __construct(protected ValidatorFactory $validator) {}

    /** @return \Illuminate\Database\Eloquent\Collection<int, HREmployee> */
    public function employees(bool $activeOnly = true)
    {
        return HREmployee::query()
            ->when($activeOnly, fn ($q) => $q->where('is_active', true))
            ->orderBy('id', 'desc')
            ->get();
    }

    public function logAttendance(int $employeeId, string $type, string $at): Attendance
    {
        return $this->handleServiceOperation(
            callback: function () use ($employeeId, $type, $at) {
                $this->validator->make(['type' => $type], ['type' => 'required|in:in,out'])->validate();
                $ts = Carbon::parse($at);
                $date = $ts->toDateString();
                $employee = HREmployee::findOrFail($employeeId);
                $branchId = $employee->branch_id;

                $attendance = Attendance::firstOrNew([
                    'employee_id' => $employeeId,
                    'date' => $date,
                ], [
                    'branch_id' => $branchId,
                    'status' => 'pending',
                ]);

                if ($type === 'in') {
                    $attendance->check_in = $ts;
                } else {
                    $attendance->check_out = $ts;
                }

                $attendance->save();

                return $attendance;
            },
            operation: 'logAttendance',
            context: ['employee_id' => $employeeId, 'type' => $type, 'at' => $at]
        );
    }

    public function approveAttendance(int $attendanceId): Attendance
    {
        return $this->handleServiceOperation(
            callback: function () use ($attendanceId) {
                $att = Attendance::findOrFail($attendanceId);
                $att->status = 'approved';
                // V33-CRIT-02 FIX: Use actual_user_id() for correct audit attribution during impersonation
                $att->approved_by = actual_user_id();
                $att->approved_at = now();
                $att->save();

                return $att;
            },
            operation: 'approveAttendance',
            context: ['attendance_id' => $attendanceId]
        );
    }

    public function runPayroll(string $period): int
    {
        return $this->handleServiceOperation(
            callback: function () use ($period) {
                // CRIT-05 FIX: Parse period (Y-m) into year and month
                $periodDate = Carbon::createFromFormat('Y-m', $period);
                if (! $periodDate) {
                    throw new \InvalidArgumentException("Invalid period format. Expected Y-m, got: {$period}");
                }
                $year = (int) $periodDate->year;
                $month = (int) $periodDate->month;

                $emps = HREmployee::query()->where('is_active', true)->get();
                $count = 0;
                DB::transaction(function () use ($emps, $year, $month, &$count) {
                    foreach ($emps as $emp) {
                        // CRIT-05 FIX: Check existence using year/month columns instead of 'period'
                        $exists = Payroll::query()
                            ->where('employee_id', $emp->getKey())
                            ->where('year', $year)
                            ->where('month', $month)
                            ->exists();
                        if ($exists) {
                            continue;
                        }

                        // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
                        $basic = decimal_float($emp->salary);

                        $extra = $emp->extra_attributes ?? [];
                        $housingAllowance = decimal_float($extra['housing_allowance'] ?? 0);
                        $transportAllowance = decimal_float($extra['transport_allowance'] ?? 0);
                        $otherAllowance = decimal_float($extra['other_allowance'] ?? 0);
                        $totalAllowances = $housingAllowance + $transportAllowance + $otherAllowance;

                        $grossSalary = $basic + $totalAllowances;
                        $socialInsurance = $this->calculateSocialInsurance($grossSalary);
                        $tax = $this->calculateTax($grossSalary - $socialInsurance);
                        $absenceDeduction = $this->calculateAbsenceDeduction($emp, "{$year}-{$month}");
                        $loanDeduction = decimal_float($extra['loan_deduction'] ?? 0);
                        $totalDeductions = $socialInsurance + $tax + $absenceDeduction + $loanDeduction;

                        $net = $grossSalary - $totalDeductions;

                        // CRIT-05 FIX: Use correct Payroll model column names
                        Payroll::create([
                            'branch_id' => $emp->branch_id,
                            'employee_id' => $emp->getKey(),
                            'year' => $year,
                            'month' => $month,
                            'basic_salary' => $basic,
                            'housing_allowance' => $housingAllowance,
                            'transport_allowance' => $transportAllowance,
                            'other_allowances' => $otherAllowance,
                            'gross_salary' => $grossSalary,
                            'tax_deduction' => $tax,
                            'insurance_deduction' => $socialInsurance,
                            'loan_deduction' => $loanDeduction,
                            'absence_deduction' => $absenceDeduction,
                            'total_deductions' => $totalDeductions,
                            'net_salary' => max(0, $net),
                            'status' => 'draft',
                        ]);
                        $count++;
                    }
                });

                return $count;
            },
            operation: 'runPayroll',
            context: ['period' => $period]
        );
    }

    protected function calculateSocialInsurance(float $grossSalary): float
    {
        $rate = config('hrm.social_insurance.rate', 0.14);
        $maxSalary = config('hrm.social_insurance.max_salary', 12600);

        $insurableSalary = min($grossSalary, $maxSalary);

        // Use bcmath for precise social insurance calculation
        $insurance = bcmul((string) $insurableSalary, (string) $rate, 4);

        // V30-MED-08 FIX: Use bcround() instead of bcdiv truncation
        // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
        return decimal_float(bcround($insurance, 2));
    }

    protected function calculateTax(float $taxableIncome): float
    {
        // Use bcmath for all tax calculations
        $annualIncome = bcmul((string) $taxableIncome, '12', 2);

        $brackets = config('hrm.tax_brackets', [
            ['limit' => 40000, 'rate' => 0],
            ['limit' => 55000, 'rate' => 0.10],
            ['limit' => 70000, 'rate' => 0.15],
            ['limit' => 200000, 'rate' => 0.20],
            ['limit' => 400000, 'rate' => 0.225],
            ['limit' => PHP_FLOAT_MAX, 'rate' => 0.25],
        ]);

        $previousLimit = '0';
        $annualTaxString = '0.00';
        foreach ($brackets as $bracket) {
            // Use bcmath comparison
            if (bccomp($annualIncome, $previousLimit, 2) <= 0) {
                break;
            }

            // Use bcmath to calculate taxable amount in bracket
            $bracketLimit = (string) $bracket['limit'];
            $taxableUpToLimit = bccomp($annualIncome, $bracketLimit, 2) < 0 ? $annualIncome : $bracketLimit;
            $taxableInBracket = bcsub($taxableUpToLimit, $previousLimit, 2);

            // Use bcmath for precise tax bracket calculation
            if (bccomp($taxableInBracket, '0', 2) > 0) {
                $bracketTax = bcmul($taxableInBracket, (string) $bracket['rate'], 4);
                $annualTaxString = bcadd($annualTaxString, $bracketTax, 4);
            }
            $previousLimit = $bracketLimit;
        }

        // Use bcmath for precise monthly tax calculation
        $monthlyTax = bcdiv($annualTaxString, '12', 4);

        // V30-MED-08 FIX: Use bcround() instead of bcdiv truncation
        // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
        return decimal_float(bcround($monthlyTax, 2));
    }

    protected function calculateAbsenceDeduction(HREmployee $emp, string $period): float
    {
        try {
            $periodDate = Carbon::createFromFormat('Y-m', $period);
            if (! $periodDate) {
                return 0;
            }

            $startDate = $periodDate->copy()->startOfMonth()->toDateString();
            $endDate = $periodDate->copy()->endOfMonth()->toDateString();

            $absenceDays = Attendance::query()
                ->where('employee_id', $emp->getKey())
                ->where('status', 'absent')
                ->whereBetween('date', [$startDate, $endDate])
                ->count();

            if ($absenceDays <= 0) {
                return 0;
            }

            // FIX: Use bcdiv for financial precision instead of float division
            $dailyRate = decimal_float(bcdiv((string) decimal_float($emp->salary), '30', 4), 4);

            // Use bcmath for precise absence deduction
            return decimal_float(bcmul((string) $dailyRate, (string) $absenceDays, 2));
        } catch (\Exception $e) {
            Log::warning('Failed to calculate absence deduction', [
                'employee_id' => $emp->getKey(),
                'period' => $period,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }
}
