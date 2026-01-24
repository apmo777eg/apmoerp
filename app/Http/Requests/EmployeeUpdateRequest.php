<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('hrm.employees.edit') || $this->user()->can('hr.manage-employees');
    }

    public function rules(): array
    {
        $employeeId = $this->route('employee') ? $this->route('employee')->id : 'NULL';

        return [
            'code' => ['sometimes', 'required', 'string', 'max:50', 'unique:hr_employees,code,'.$employeeId],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', 'unique:hr_employees,email,'.$employeeId],
            'phone' => ['sometimes', 'required', 'string', 'max:20'],
            'national_id' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['sometimes', 'required', 'in:male,female'],
            'address' => ['nullable', 'string'],
            'hire_date' => ['sometimes', 'required', 'date'],
            'position' => ['sometimes', 'required', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
            'salary' => ['sometimes', 'required', 'numeric', 'min:0'],
            'salary_type' => ['sometimes', 'required', 'in:monthly,daily,hourly'],
            'employment_type' => ['sometimes', 'required', 'in:full_time,part_time,contract,temporary'],
            'status' => ['sometimes', 'required', 'in:active,inactive,terminated,on_leave'],
            'termination_date' => ['nullable', 'date'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
            'emergency_contact_relation' => ['nullable', 'string', 'max:100'],
            // Contract fields
            'contract_start_date' => ['nullable', 'date'],
            'contract_end_date' => ['nullable', 'date'],
            // Work permit fields
            'work_permit_number' => ['nullable', 'string', 'max:100'],
            'work_permit_expiry' => ['nullable', 'date'],
        ];
    }
}
