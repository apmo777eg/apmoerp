<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\HrmCentral;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Services\Contracts\HRMServiceInterface as HRM;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    public function __construct(protected HRM $hrm) {}

    public function index(Request $request)
    {
        $per = min(max($request->integer('per_page', 20), 1), 100);
        $q = Payroll::query()->orderByDesc('id');
        if ($request->filled('period')) {
            $period = (string) $request->input('period');

            try {
                $dt = Carbon::createFromFormat('Y-m', $period);
                $q->where('year', (int) $dt->year)->where('month', (int) $dt->month);
            } catch (\Throwable) {
                return $this->fail(__('Invalid period format. Expected Y-m.'), 422);
            }
        }

        return $this->ok($q->paginate($per));
    }

    /**
     * NEW-V15-CRITICAL-02 FIX: Show a specific payroll record
     */
    public function show(Payroll $payroll)
    {
        return $this->ok($payroll->load(['employee']));
    }

    public function run(Request $request)
    {
        $this->validate($request, ['period' => ['required', 'date_format:Y-m']]);
        $count = $this->hrm->runPayroll($request->input('period'));

        return $this->ok(['generated' => $count], __('Payroll run queued/created'));
    }

    public function approve(Payroll $payroll)
    {
        $payroll->status = 'approved';
        $payroll->save();

        return $this->ok($payroll, __('Approved'));
    }

    /**
     * NEW-V15-CRITICAL-02 FIX: Mark payroll as paid
     */
    public function pay(Payroll $payroll)
    {
        if ($payroll->status !== 'approved') {
            return $this->fail(__('Payroll must be approved before payment'), 422);
        }

        $payroll->status = 'paid';
        $payroll->payment_date = now();
        $payroll->save();

        return $this->ok($payroll, __('Payroll marked as paid'));
    }
}
