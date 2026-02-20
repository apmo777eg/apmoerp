<?php

declare(strict_types=1);

namespace App\Livewire\Hrm\SelfService;

use App\Models\Payroll;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use App\Livewire\BaseComponent as Component;
use Livewire\WithPagination;

/**
 * My Payslips - Employee Self Service
 * Allows employees to view their payslips (using Payroll model)
 */
class MyPayslips extends Component
{
    use WithPagination;

    public ?string $year = null;

    public ?string $month = null;

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user || ! $user->can('employee.self.payslip-view')) {
            abort(403);
        }

        $this->year = now()->format('Y');
    }

    public function updatingYear(): void
    {
        $this->resetPage();
    }

    public function updatingMonth(): void
    {
        $this->resetPage();
    }

    /**
     * Get year-to-date earnings summary
     */
    public function getYtdSummary(): array
    {
        $user = Auth::user();

        if (! $user || ! $user->employee_id) {
            return [
                'gross_earnings' => 0,
                'total_deductions' => 0,
                'net_salary' => 0,
            ];
        }

        $payrolls = Payroll::where('employee_id', $user->employee_id)
            ->where('year', $this->year)
            ->where('status', 'paid')
            ->get();

        return [
            'gross_earnings' => $payrolls->sum('gross_salary'),
            'total_deductions' => $payrolls->sum('total_deductions'),
            'net_salary' => $payrolls->sum('net_salary'),
        ];
    }

    /**
     * Download a payslip PDF
     */
    public function downloadPayslip(int $payrollId): void
    {
        $user = Auth::user();

        $payroll = Payroll::where('id', $payrollId)
            ->where('employee_id', $user->employee_id)
            ->first();

        if (! $payroll) {
            session()->flash('error', __('Payslip not found.'));

            return;
        }

        // Dispatch download event to JavaScript
        $this->dispatch('download-payslip', ['id' => $payrollId]);
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $user = Auth::user();

        if (! $user || ! $user->can('employee.self.payslip-view')) {
            abort(403);
        }

        $employeeId = $user->employee_id ?? null;

        $records = collect();

        if ($employeeId) {
            $records = Payroll::where('employee_id', $employeeId)
                ->when($this->year, fn ($q) => $q->where('year', $this->year))
                ->when($this->month, fn ($q) => $q->where('month', $this->month))
                ->orderByDesc('year')
                ->orderByDesc('month')
                ->paginate(12);
        }

        return view('livewire.hrm.self-service.my-payslips', [
            'records' => $records,
            'ytdSummary' => $this->getYtdSummary(),
            'years' => range(now()->year, now()->year - 5),
            'months' => collect(range(1, 12))->mapWithKeys(fn ($m) => [$m => now()->month($m)->format('F')]),
        ]);
    }
}
