<?php

declare(strict_types=1);

namespace App\Livewire\Helpdesk\SLAPolicies;

use App\Http\Requests\Traits\HasMultilingualValidation;
use App\Models\TicketSLAPolicy;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use App\Livewire\BaseComponent as Component;
class Form extends Component
{
    use AuthorizesRequests;
    use HasMultilingualValidation;

    public ?int $policyId = null;

    public string $name = '';

    public string $description = '';

    public int $response_time_minutes = 60;

    public int $resolution_time_minutes = 480;

    public bool $business_hours_only = false;

    public string $business_hours_start = '09:00';

    public string $business_hours_end = '17:00';

    public array $working_days = [1, 2, 3, 4, 5];

    public bool $is_active = true;

    protected array $daysOfWeek = [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
    ];

    public function mount(?int $policy = null): void
    {
        $this->authorize('helpdesk.manage');

        if ($policy) {
            $this->policyId = $policy;
            $this->loadPolicy();
        }
    }

    protected function loadPolicy(): void
    {
        $policy = TicketSLAPolicy::findOrFail($this->policyId);

        $this->name = $policy->name;
        $this->description = $policy->description ?? '';
        $this->response_time_minutes = $policy->response_time_minutes;
        $this->resolution_time_minutes = $policy->resolution_time_minutes;
        $this->business_hours_only = $policy->business_hours_only;
        $this->business_hours_start = $policy->business_hours_start ?? '09:00';
        $this->business_hours_end = $policy->business_hours_end ?? '17:00';
        $this->working_days = $policy->working_days ?? [1, 2, 3, 4, 5];
        $this->is_active = $policy->is_active;
    }

    protected function rules(): array
    {
        $rules = [
            'name' => $this->multilingualString(required: true, max: 255),
            'description' => $this->unicodeText(required: false),
            'response_time_minutes' => 'required|integer|min:1',
            'resolution_time_minutes' => 'required|integer|min:1',
            'business_hours_only' => 'boolean',
        ];

        if ($this->business_hours_only) {
            $rules['business_hours_start'] = ['required', 'regex:/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/'];
            $rules['business_hours_end'] = ['required', 'regex:/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/'];
            $rules['working_days'] = 'required|array|min:1';
            $rules['working_days.*'] = 'integer|min:0|max:6';
        }

        return $rules;
    }

    public function save(): mixed
    {
        $this->authorize('helpdesk.manage');

        $this->validate();

        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'response_time_minutes' => $this->response_time_minutes,
            'resolution_time_minutes' => $this->resolution_time_minutes,
            'business_hours_only' => $this->business_hours_only,
            'business_hours_start' => $this->business_hours_only ? $this->business_hours_start : null,
            'business_hours_end' => $this->business_hours_only ? $this->business_hours_end : null,
            'working_days' => $this->business_hours_only ? $this->working_days : null,
            'is_active' => $this->is_active,
        ];

        if ($this->policyId) {
            $policy = TicketSLAPolicy::findOrFail($this->policyId);
            // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
            $data['updated_by'] = actual_user_id();
            $policy->update($data);
            session()->flash('success', __('SLA Policy updated successfully'));
        } else {
            // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
            $data['created_by'] = actual_user_id();
            TicketSLAPolicy::create($data);
            session()->flash('success', __('SLA Policy created successfully'));
        }

        $this->redirectRoute('app.helpdesk.sla-policies.index', navigate: true);
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.helpdesk.sla-policies.form', [
            'daysOfWeek' => $this->daysOfWeek,
        ]);
    }
}
