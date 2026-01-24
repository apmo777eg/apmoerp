<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Branch;
use App\Models\Payroll;
use Illuminate\Support\Facades\View;

class PayslipService
{
    /**
     * Generate payslip HTML content
     */
    public function generatePayslipHtml(Payroll $payroll): string
    {
        $employee = $payroll->employee;
        $branch = $employee->branch;

        $data = [
            'payroll' => $payroll,
            'employee' => $employee,
            'branch' => $branch,
            'generatedAt' => now()->format('Y-m-d H:i:s'),
        ];

        return View::make('payslips.template', $data)->render();
    }

    /**
     * Get payslip breakdown
     */
    public function getPayslipBreakdown(Payroll $payroll): array
    {
        return [
            'salary' => [
                'label' => __('Basic Salary'),
                'amount' => $payroll->basic,
                'type' => 'earning',
            ],
            'allowances' => [
                'label' => __('Allowances'),
                'amount' => $payroll->allowances,
                'type' => 'earning',
            ],
            'gross_salary' => [
                'label' => __('Gross Salary'),
                'amount' => $payroll->basic + $payroll->allowances,
                'type' => 'subtotal',
            ],
            'deductions' => [
                'label' => __('Deductions'),
                'amount' => $payroll->deductions,
                'type' => 'deduction',
            ],
            'net_salary' => [
                'label' => __('Net Salary'),
                'amount' => $payroll->net,
                'type' => 'total',
            ],
        ];
    }

    /**
     * Calculate allowances based on company rules from settings
     */
    protected function calculateAllowances(float $basicSalary): array
    {
        $allowances = [];
        $total = '0';

        // Transportation allowance (configurable percentage or fixed)
        $transportType = setting('hrm.transport_allowance_type', 'percentage');
        // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
        $transportValue = decimal_float(setting('hrm.transport_allowance_value', 10));
        if ($transportType === 'percentage') {
            $transportAmount = bcmul((string) $basicSalary, bcdiv((string) $transportValue, '100', 4), 2);
        } else {
            // V30-MED-08 FIX: Use bcround() instead of bcdiv truncation
            $transportAmount = bcround((string) $transportValue, 2);
        }
        if (bccomp($transportAmount, '0', 2) > 0) {
            $allowances['transport'] = decimal_float($transportAmount);
            $total = bcadd($total, $transportAmount, 2);
        }

        // Housing allowance (configurable)
        $housingType = setting('hrm.housing_allowance_type', 'percentage');
        $housingValue = decimal_float(setting('hrm.housing_allowance_value', 0));
        if ($housingType === 'percentage') {
            $housingAmount = bcmul((string) $basicSalary, bcdiv((string) $housingValue, '100', 4), 2);
        } else {
            // V30-MED-08 FIX: Use bcround() instead of bcdiv truncation
            $housingAmount = bcround((string) $housingValue, 2);
        }
        if (bccomp($housingAmount, '0', 2) > 0) {
            $allowances['housing'] = decimal_float($housingAmount);
            $total = bcadd($total, $housingAmount, 2);
        }

        // Meal allowance (fixed)
        $mealAllowance = decimal_float(setting('hrm.meal_allowance', 0));
        if ($mealAllowance > 0) {
            // V30-MED-08 FIX: Use bcround() instead of bcdiv truncation
            $mealAllowanceStr = bcround((string) $mealAllowance, 2);
            $allowances['meal'] = decimal_float($mealAllowanceStr);
            $total = bcadd($total, $mealAllowanceStr, 2);
        }

        return [
            'breakdown' => $allowances,
            'total' => decimal_float($total),
        ];
    }

    /**
     * Calculate deductions based on company rules and tax config
     */
    protected function calculateDeductions(float $grossSalary): array
    {
        $deductions = [];
        $total = '0';

        // Social Insurance deduction (use bcmath)
        $siConfig = config('hrm.social_insurance', []);
        // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
        $siRate = decimal_float($siConfig['rate'] ?? 0.14);
        $siMaxSalary = decimal_float($siConfig['max_salary'] ?? 12600);
        $siBaseSalary = bccomp((string) $grossSalary, (string) $siMaxSalary, 2) > 0 ? $siMaxSalary : $grossSalary;
        $socialInsurance = bcmul((string) $siBaseSalary, (string) $siRate, 2);
        if (bccomp($socialInsurance, '0', 2) > 0) {
            $deductions['social_insurance'] = decimal_float($socialInsurance);
            $total = bcadd($total, $socialInsurance, 2);
        }

        // Income Tax (progressive brackets)
        $annualGross = $grossSalary * 12;
        $taxBrackets = config('hrm.tax_brackets', []);
        $annualTax = 0.0;
        $previousLimit = 0;

        foreach ($taxBrackets as $bracket) {
            $limit = decimal_float($bracket['limit'] ?? PHP_FLOAT_MAX);
            $rate = decimal_float($bracket['rate'] ?? 0);

            if ($annualGross <= $previousLimit) {
                break;
            }

            $taxableInBracket = min($annualGross, $limit) - $previousLimit;
            $annualTax += max(0, $taxableInBracket) * $rate;
            $previousLimit = $limit;
        }

        $monthlyTax = $annualTax / 12;
        if ($monthlyTax > 0) {
            $deductions['income_tax'] = round($monthlyTax, 2);
            $total += $monthlyTax;
        }

        // Additional fixed deductions from settings
        $healthInsurance = decimal_float(setting('hrm.health_insurance_deduction', 0));
        if ($healthInsurance > 0) {
            // V30-MED-08 FIX: Use bcround() instead of bcdiv truncation
            $healthInsuranceStr = bcround((string) $healthInsurance, 2);
            $deductions['health_insurance'] = decimal_float($healthInsuranceStr);
            $total = bcadd($total, $healthInsuranceStr, 2);
        }

        return [
            'breakdown' => $deductions,
            'total' => decimal_float($total),
        ];
    }

    /**
     * Calculate payroll for employee with pro-rata support for mid-month salary changes.
     *
     * BUG FIX: Handles promotions/salary changes that occur mid-month.
     * Previously, the system would use the current salary for the entire month,
     * leading to overpayment when an employee gets promoted late in the month.
     *
     * @param  int  $employeeId  Employee ID
     * @param  string  $period  Period in Y-m format
     * @param  array|null  $salaryChanges  Optional array of salary changes in format:
     *                                     [['effective_date' => 'Y-m-d', 'new_salary' => float], ...]
     * @return array Payroll calculation result
     */
    public function calculatePayroll(int $employeeId, string $period, ?array $salaryChanges = null): array
    {
        $employee = \App\Models\HREmployee::findOrFail($employeeId);

        // Parse period to get the first and last day of the month
        $periodParts = explode('-', $period);
        $year = (int) ($periodParts[0] ?? date('Y'));
        $month = (int) ($periodParts[1] ?? date('m'));
        $periodStart = \Carbon\Carbon::create($year, $month, 1)->startOfDay();
        $periodEnd = $periodStart->copy()->endOfMonth();
        $daysInMonth = $periodStart->daysInMonth;

        // BUG FIX: Calculate pro-rata salary if there are mid-month changes
        $basic = $this->calculateProRataBasicSalary(
            $employee,
            $periodStart,
            $periodEnd,
            $daysInMonth,
            $salaryChanges
        );

        // Calculate allowances based on configurable company rules
        $allowanceResult = $this->calculateAllowances($basic);
        $allowances = $allowanceResult['total'];

        // Gross salary
        $gross = $basic + $allowances;

        // Calculate deductions based on configurable rules and tax brackets
        $deductionResult = $this->calculateDeductions($gross);
        $deductions = $deductionResult['total'];

        // Net salary (use bcmath)
        $net = bcsub((string) $gross, (string) $deductions, 2);

        return [
            'employee_id' => $employeeId,
            'period' => $period,
            // V30-MED-08 FIX: Use bcround() instead of bcdiv truncation
            // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
            'basic' => decimal_float(bcround((string) $basic, 2)),
            'allowances' => decimal_float(bcround((string) $allowances, 2)),
            'allowance_breakdown' => $allowanceResult['breakdown'],
            'deductions' => decimal_float(bcround((string) $deductions, 2)),
            'deduction_breakdown' => $deductionResult['breakdown'],
            'gross' => decimal_float(bcround((string) $gross, 2)),
            'net' => decimal_float($net),
            'status' => 'draft',
        ];
    }

    /**
     * Calculate pro-rata basic salary for mid-month salary changes.
     *
     * Example: If employee had salary 5000 for days 1-24 and got promoted to 10000 on day 25,
     * the calculation would be: (5000 * 24/30) + (10000 * 6/30) = 4000 + 2000 = 6000
     *
     * @param  \App\Models\HREmployee  $employee  The employee
     * @param  \Carbon\Carbon  $periodStart  Start of the payroll period
     * @param  \Carbon\Carbon  $periodEnd  End of the payroll period
     * @param  int  $daysInMonth  Total days in the month
     * @param  array|null  $salaryChanges  Explicit salary changes, or null to try auto-detection
     * @return float The pro-rata basic salary
     */
    protected function calculateProRataBasicSalary(
        \App\Models\HREmployee $employee,
        \Carbon\Carbon $periodStart,
        \Carbon\Carbon $periodEnd,
        int $daysInMonth,
        ?array $salaryChanges = null
    ): float {
        // Get current salary using the model accessor for consistency
        // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
        $currentSalary = decimal_float($employee->salary);

        // If no salary changes provided, try to get from activity log
        if ($salaryChanges === null) {
            $salaryChanges = $this->getSalaryChangesFromActivityLog($employee, $periodStart, $periodEnd);
        }

        // If no changes in the period, return full current salary
        if (empty($salaryChanges)) {
            return $currentSalary;
        }

        // Sort changes by date
        usort($salaryChanges, function ($a, $b) {
            return strcmp($a['effective_date'], $b['effective_date']);
        });

        // Calculate pro-rata salary using bcmath string operations for precision
        $proRataSalary = '0';
        $previousDate = $periodStart;
        $previousSalary = null;

        // Determine the salary at the start of the period
        // This would be the salary before the first change, or the old_salary if tracked
        $salaryAtPeriodStart = $currentSalary;
        if (! empty($salaryChanges[0]['old_salary'])) {
            // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
            $salaryAtPeriodStart = decimal_float($salaryChanges[0]['old_salary']);
        }

        // If first change is after period start, calculate days at initial salary
        $firstChangeDate = \Carbon\Carbon::parse($salaryChanges[0]['effective_date']);
        if ($firstChangeDate->gt($periodStart)) {
            $daysAtInitialSalary = $periodStart->diffInDays($firstChangeDate);
            $dailyRate = bcdiv((string) $salaryAtPeriodStart, (string) $daysInMonth, 6);
            $portion = bcmul($dailyRate, (string) $daysAtInitialSalary, 4);
            $proRataSalary = bcadd($proRataSalary, $portion, 4);
            $previousDate = $firstChangeDate;
        }

        // Process each salary change
        foreach ($salaryChanges as $index => $change) {
            $changeDate = \Carbon\Carbon::parse($change['effective_date']);
            // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
            $newSalary = decimal_float($change['new_salary']);

            // Skip changes outside the period
            if ($changeDate->gt($periodEnd)) {
                continue;
            }

            // Determine the end date for this salary rate
            $nextChangeDate = isset($salaryChanges[$index + 1])
                ? \Carbon\Carbon::parse($salaryChanges[$index + 1]['effective_date'])
                : $periodEnd->copy()->addDay();

            // Cap at period end
            $endForThisSalary = $nextChangeDate->gt($periodEnd)
                ? $periodEnd->copy()->addDay()
                : $nextChangeDate;

            $daysAtThisSalary = $changeDate->diffInDays($endForThisSalary);

            if ($daysAtThisSalary > 0) {
                $dailyRate = bcdiv((string) $newSalary, (string) $daysInMonth, 6);
                $portion = bcmul($dailyRate, (string) $daysAtThisSalary, 4);
                $proRataSalary = bcadd($proRataSalary, $portion, 4);
            }
        }

        // V30-MED-08 FIX: Use bcround() instead of bcdiv truncation
        // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
        return decimal_float(bcround($proRataSalary, 2));
    }

    /**
     * Attempt to get salary changes from activity log.
     *
     * @param  \App\Models\HREmployee  $employee  The employee
     * @param  \Carbon\Carbon  $periodStart  Start of the payroll period
     * @param  \Carbon\Carbon  $periodEnd  End of the payroll period
     * @return array Array of salary changes
     */
    protected function getSalaryChangesFromActivityLog(
        \App\Models\HREmployee $employee,
        \Carbon\Carbon $periodStart,
        \Carbon\Carbon $periodEnd
    ): array {
        // Try to find salary changes from activity log (Spatie ActivityLog)
        if (! class_exists(\Spatie\Activitylog\Models\Activity::class)) {
            return [];
        }

        try {
            $activities = \Spatie\Activitylog\Models\Activity::query()
                ->where('subject_type', \App\Models\HREmployee::class)
                ->where('subject_id', $employee->id)
                ->where('event', 'updated')
                ->whereBetween('created_at', [$periodStart, $periodEnd])
                ->get();

            $changes = [];
            foreach ($activities as $activity) {
                $properties = $activity->properties->toArray();
                $attributes = $properties['attributes'] ?? [];
                $old = $properties['old'] ?? [];

                // Check if salary was changed
                if (isset($attributes['salary']) && isset($old['salary'])) {
                    $changes[] = [
                        'effective_date' => $activity->created_at->format('Y-m-d'),
                        // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
                        'old_salary' => decimal_float($old['salary']),
                        'new_salary' => decimal_float($attributes['salary']),
                    ];
                }
            }

            return $changes;
        } catch (\Exception $e) {
            // If activity log is not available or throws error, return empty
            return [];
        }
    }

    /**
     * Process payroll for all employees in a branch
     */
    public function processBranchPayroll(int $branchId, string $period): array
    {
        $employees = \App\Models\HREmployee::where('branch_id', $branchId)
            ->where('is_active', true)
            ->get();

        $processed = [];
        $errors = [];

        // Parse period to extract year and month
        $periodParts = explode('-', $period);
        $year = (int) ($periodParts[0] ?? date('Y'));
        $month = (int) ($periodParts[1] ?? date('m'));

        foreach ($employees as $employee) {
            try {
                // Check if payroll already exists for this employee in this period
                // regardless of branch to prevent duplicate payroll when employee changes department
                $existingPayroll = Payroll::where('employee_id', $employee->id)
                    ->where('year', $year)
                    ->where('month', $month)
                    ->first();

                if ($existingPayroll) {
                    $errors[] = [
                        'employee_id' => $employee->id,
                        'employee_name' => $employee->name,
                        'error' => __('Payroll already generated for this employee in this period'),
                    ];

                    continue;
                }

                $payrollData = $this->calculatePayroll($employee->id, $period);

                // Only store the fields that match the Payroll model
                $payroll = Payroll::create([
                    'employee_id' => $payrollData['employee_id'],
                    'period' => $payrollData['period'],
                    'year' => $year,
                    'month' => $month,
                    'basic' => $payrollData['basic'],
                    'allowances' => $payrollData['allowances'],
                    'deductions' => $payrollData['deductions'],
                    'net' => $payrollData['net'],
                    'status' => $payrollData['status'],
                ]);
                $processed[] = $payroll;
            } catch (\Exception $e) {
                $errors[] = [
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->name,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'processed' => $processed,
            'errors' => $errors,
            'total' => count($employees),
            'success' => count($processed),
            'failed' => count($errors),
        ];
    }
}
