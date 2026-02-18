<?php

declare(strict_types=1);

namespace App\Http\Controllers\Branch\HRM;

use App\Http\Controllers\Branch\Concerns\RequiresBranchContext;
use App\Http\Controllers\Controller;
use App\Models\HREmployee;
use App\Rules\BranchScopedExists;
use App\Services\Contracts\HRMServiceInterface as HRM;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EmployeeController extends Controller
{
    use RequiresBranchContext;

    public function __construct(protected HRM $hrm) {}

    public function index()
    {
        return $this->ok($this->hrm->employees());
    }

    public function show(HREmployee $employee)
    {
        // Defense-in-depth: Verify employee belongs to current branch
        $branchId = $this->requireBranchId(request());
        abort_if($employee->branch_id !== $branchId, 404, 'Employee not found in this branch');

        return $this->ok($employee);
    }

    public function assign(Request $request)
    {
        // V57-CRITICAL-03 FIX: Use BranchScopedExists to prevent cross-branch employee references
        $data = $this->validate($request, [
            'employee_id' => ['required', new BranchScopedExists('hr_employees')],
            'branch_id' => ['sometimes', 'integer'],
        ]);
        $branchId = (int) ($data['branch_id'] ?? $request->attributes->get('branch_id'));
        if (Schema::hasTable('branch_employee')) {
            DB::table('branch_employee')->updateOrInsert(
                // FIX: pivot column is employee_id (not hr_employee_id)
                ['employee_id' => $data['employee_id'], 'branch_id' => $branchId],
                ['created_at' => now(), 'updated_at' => now()]
            );
        } else {
            HREmployee::whereKey($data['employee_id'])->update(['branch_id' => $branchId]);
        }

        return $this->ok(['employee_id' => $data['employee_id'], 'branch_id' => $branchId], __('Assigned'));
    }

    public function unassign(Request $request, HREmployee $employee)
    {
        $branchId = (int) $request->attributes->get('branch_id');
        if (Schema::hasTable('branch_employee')) {
            // FIX: pivot column is employee_id (not hr_employee_id)
            DB::table('branch_employee')->where('employee_id', $employee->id)->where('branch_id', $branchId)->delete();
        } else {
            $employee->branch_id = null;
            $employee->save();
        }

        return $this->ok(['employee_id' => $employee->id, 'branch_id' => $branchId], __('Unassigned'));
    }
}
