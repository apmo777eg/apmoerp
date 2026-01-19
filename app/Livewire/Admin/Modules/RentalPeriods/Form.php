<?php

namespace App\Livewire\Admin\Modules\RentalPeriods;

use App\Models\Module;
use App\Models\RentalPeriod;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Form extends Component
{
    use AuthorizesRequests;

    public Module $module;

    public ?int $periodId = null;

    public string $period_key = '';

    public string $period_name = '';

    public string $period_name_ar = '';

    public string $period_type = 'monthly';

    public int $duration_value = 1;

    public string $duration_unit = 'months';

    public float $price_multiplier = 1;

    public bool $is_default = false;

    public bool $is_active = true;

    public int $sort_order = 0;

    protected $rules = [
        'period_key' => 'required|string|max:50|regex:/^[a-z_]+$/',
        'period_name' => 'required|string|max:100',
        'period_name_ar' => 'nullable|string|max:100',
        'period_type' => 'required|in:hourly,daily,weekly,monthly,quarterly,yearly,custom',
        'duration_value' => 'required|integer|min:1',
        'duration_unit' => 'required|in:hours,days,weeks,months,years',
        'price_multiplier' => 'required|numeric|min:0',
    ];

    public function mount(Module $module, ?int $period = null): void
    {
        $this->authorize('modules.manage');
        $this->module = $module;

        if ($period) {
            $this->periodId = $period;
            $this->loadPeriod();
        } else {
            $maxOrder = RentalPeriod::where('module_id', $this->module->id)->max('sort_order') ?? 0;
            $this->sort_order = $maxOrder + 1;
        }
    }

    protected function loadPeriod(): void
    {
        $period = RentalPeriod::findOrFail($this->periodId);

        $this->period_key = $period->period_key;
        $this->period_name = $period->period_name;
        $this->period_name_ar = $period->period_name_ar ?? '';
        $this->period_type = $period->period_type;
        $this->duration_value = $period->duration_value;
        $this->duration_unit = $period->duration_unit;
        $this->price_multiplier = decimal_float($period->price_multiplier);
        $this->is_default = $period->is_default;
        $this->is_active = $period->is_active;
        $this->sort_order = $period->sort_order;
    }

    public function save(): void
    {
        $this->authorize('modules.manage');
        $this->validate();

        $data = [
            'module_id' => $this->module->id,
            'period_key' => $this->period_key,
            'period_name' => $this->period_name,
            'period_name_ar' => $this->period_name_ar ?: null,
            'period_type' => $this->period_type,
            'duration_value' => $this->duration_value,
            'duration_unit' => $this->duration_unit,
            'price_multiplier' => $this->price_multiplier,
            'is_default' => $this->is_default,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];

        if ($this->is_default) {
            RentalPeriod::where('module_id', $this->module->id)
                ->where('id', '!=', $this->periodId)
                ->update(['is_default' => false]);
        }

        if ($this->periodId) {
            RentalPeriod::findOrFail($this->periodId)->update($data);
            session()->flash('success', __('Rental period updated successfully'));
        } else {
            RentalPeriod::create($data);
            session()->flash('success', __('Rental period created successfully'));
        }

        $this->redirectRoute('admin.modules.rental-periods', ['module' => $this->module->id], navigate: true);
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.admin.modules.rental-periods.form', [
            'periodTypes' => [
                'hourly' => __('Hourly'),
                'daily' => __('Daily'),
                'weekly' => __('Weekly'),
                'monthly' => __('Monthly'),
                'quarterly' => __('Quarterly'),
                'yearly' => __('Yearly'),
                'custom' => __('Custom'),
            ],
            'durationUnits' => [
                'hours' => __('Hours'),
                'days' => __('Days'),
                'weeks' => __('Weeks'),
                'months' => __('Months'),
                'years' => __('Years'),
            ],
        ]);
    }
}
