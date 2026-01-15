<?php

declare(strict_types=1);

namespace App\Http\Controllers\Branch\HRM;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Services\Contracts\HRMServiceInterface as HRM;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    public function __construct(protected HRM $hrm) {}

    public function index(Request $request)
    {
        $per = min(max($request->integer('per_page', 20), 1), 100);
        $q = Payroll::query()->orderByDesc('id');
        
        // CRIT-05 FIX: Use year/month columns instead of 'period'
        if ($request->filled('period')) {
            $periodDate = \Carbon\Carbon::createFromFormat('Y-m', $request->input('period'));
            if ($periodDate) {
                $q->where('year', $periodDate->year)
                  ->where('month', $periodDate->month);
            }
        }

        return $this->ok($q->paginate($per));
    }

    public function run(Request $request)
    {
        $this->validate($request, ['period' => ['required', 'date_format:Y-m']]);

        return $this->ok(['generated' => $this->hrm->runPayroll($request->period)]);
    }

    public function approve(Payroll $payroll)
    {
        $payroll->status = 'approved';
        $payroll->save();

        return $this->ok($payroll, __('Approved'));
    }

    public function pay(Payroll $payroll)
    {
        if ($payroll->status !== 'approved') {
            return $this->error(__('Payroll must be approved before payment'), 422);
        }

        $payroll->status = 'paid';
        // CRIT-05 FIX: Use payment_date instead of paid_at
        $payroll->payment_date = now();
        $payroll->save();

        return $this->ok($payroll, __('Paid'));
    }
}
