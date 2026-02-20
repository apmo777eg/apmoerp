<?php

namespace App\Livewire\Admin\Modules;

use App\Models\Module;
use App\Models\RentalPeriod;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Livewire\BaseComponent as Component;
class RentalPeriods extends Component
{
    use AuthorizesRequests;

    public Module $module;

    public function mount(Module $module): void
    {
        $this->authorize('modules.manage');
        $this->module = $module;

        if (! $module->is_rental) {
            session()->flash('warning', __('This module is not configured for rentals'));
        }
    }

    public function delete(int $periodId): void
    {
        $this->authorize('modules.manage');
        RentalPeriod::findOrFail($periodId)->delete();
        session()->flash('success', __('Rental period deleted successfully'));
    }

    public function toggleActive(int $periodId): void
    {
        $this->authorize('modules.manage');
        $period = RentalPeriod::findOrFail($periodId);
        $period->update(['is_active' => ! $period->is_active]);
    }

    public function setDefault(int $periodId): void
    {
        $this->authorize('modules.manage');

        RentalPeriod::where('module_id', $this->module->id)->update(['is_default' => false]);
        RentalPeriod::findOrFail($periodId)->update(['is_default' => true]);

        session()->flash('success', __('Default period updated'));
    }

    public function render()
    {
        $periods = RentalPeriod::where('module_id', $this->module->id)
            ->orderBy('sort_order')
            ->orderBy('duration_value')
            ->get();

        return view('livewire.admin.modules.rental-periods', [
            'periods' => $periods,
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
        ])->layout('layouts.app');
    }
}
