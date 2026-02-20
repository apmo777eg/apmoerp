<?php

declare(strict_types=1);

namespace App\Livewire\Hrm\Payroll;

use App\Models\HREmployee;
use App\Models\Payroll;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use App\Livewire\BaseComponent as Component;
class Run extends Component
{
    public ?string $period = null; // e.g. "2024-01"

    public ?int $branchId = null;

    /**
     * Whether to include inactive employees.
     */
    public bool $includeInactive = false;

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user || ! $user->can('hrm.payroll.run')) {
            abort(403);
        }

        $this->branchId = $user->branch_id;
        if (! $this->period) {
            $this->period = now()->format('Y-m');
        }
    }

    protected function rules(): array
    {
        return [
            'period' => ['required', 'date_format:Y-m'],
            'includeInactive' => ['boolean'],
        ];
    }

    public function runPayroll(): mixed
    {
        $user = Auth::user();

        if (! $user || ! $user->can('hrm.payroll.run')) {
            abort(403);
        }

        $this->validate();

        if (! $this->branchId) {
            session()->flash('error', __('Branch is not set for current user.'));

            return null;
        }

        DB::transaction(function () {
            $employeesQuery = HREmployee::query()
                ->where('branch_id', $this->branchId);

            if (! $this->includeInactive) {
                $employeesQuery->where('is_active', true);
            }

            $employees = $employeesQuery->get();

            $dt = Carbon::createFromFormat('Y-m', (string) $this->period);
            $year = (int) $dt->year;
            $month = (int) $dt->month;

            foreach ($employees as $employee) {
                // If a payroll for this employee & period already exists, skip it.
                $exists = Payroll::query()
                    ->where('employee_id', $employee->id)
                    ->where('year', $year)
                    ->where('month', $month)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $basic = decimal_float($employee->salary ?? 0);

                // Allowances from canonical employee columns
                $housing = decimal_float($employee->housing_allowance ?? 0);
                $transport = decimal_float($employee->transport_allowance ?? 0);
                $meal = decimal_float($employee->meal_allowance ?? 0);
                $other = decimal_float($employee->other_allowances ?? 0);

                $gross = $basic + $housing + $transport + $meal + $other;

                // Deductions can be extended later (tax/insurance/loans).
                $totalDeductions = 0.0;
                $net = max(0, $gross - $totalDeductions);

                Payroll::create([
                    'branch_id' => $this->branchId,
                    'employee_id' => $employee->id,
                    'year' => $year,
                    'month' => $month,
                    'salary' => $basic,
                    'housing_allowance' => $housing,
                    'transport_allowance' => $transport,
                    'meal_allowance' => $meal,
                    'other_allowances' => $other,
                    'gross_salary' => $gross,
                    'total_deductions' => $totalDeductions,
                    'net_salary' => $net,
                    'status' => 'draft',
                    'extra_attributes' => [],
                ]);
            }
        });


        session()->flash('status', __('Payroll generated for :period', ['period' => $this->period]));

        $this->redirectRoute('app.hrm.payroll.index', navigate: true);
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.hrm.payroll.run');
    }
}
