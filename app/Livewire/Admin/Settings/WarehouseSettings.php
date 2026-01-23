<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Settings;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class WarehouseSettings extends Component
{
    // Warehouse settings
    public bool $enable_multi_location = false;

    public bool $enable_batch_tracking = true;

    public bool $enable_serial_tracking = true;

    public bool $auto_allocate_stock = true;

    public string $stock_allocation_method = 'FIFO';

    public bool $enable_negative_stock = false;

    public int $stock_count_frequency_days = 30;

    public bool $require_approval_for_adjustments = true;

    public bool $enable_barcode_scanning = true;

    public string $default_warehouse_location = 'main';

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->can('settings.view')) {
            abort(403);
        }

        $this->loadSettings();
    }

    protected function loadSettings(): void
    {
        $settings = Cache::remember('warehouse_settings', 3600, function () {
            return SystemSetting::where('setting_group', 'warehouse')->pluck('value', 'setting_key')->toArray();
        });

        $this->enable_multi_location = (bool) ($settings['warehouse.enable_multi_location'] ?? false);
        $this->enable_batch_tracking = (bool) ($settings['warehouse.enable_batch_tracking'] ?? true);
        $this->enable_serial_tracking = (bool) ($settings['warehouse.enable_serial_tracking'] ?? true);
        $this->auto_allocate_stock = (bool) ($settings['warehouse.auto_allocate_stock'] ?? true);
        $this->stock_allocation_method = $settings['warehouse.stock_allocation_method'] ?? 'FIFO';
        $this->enable_negative_stock = (bool) ($settings['warehouse.enable_negative_stock'] ?? false);
        $this->stock_count_frequency_days = (int) ($settings['warehouse.stock_count_frequency_days'] ?? 30);
        $this->require_approval_for_adjustments = (bool) ($settings['warehouse.require_approval_for_adjustments'] ?? true);
        $this->enable_barcode_scanning = (bool) ($settings['warehouse.enable_barcode_scanning'] ?? true);
        $this->default_warehouse_location = $settings['warehouse.default_warehouse_location'] ?? 'main';
    }

    protected function setSetting(string $key, $value): void
    {
        SystemSetting::updateOrCreate(
            ['setting_key' => $key],
            [
                'value' => $value,
                'setting_group' => 'warehouse',
                'is_public' => false,
            ]
        );
    }

    public function save(): mixed
    {
        $this->validate([
            'stock_allocation_method' => 'required|in:FIFO,LIFO,FEFO',
            'stock_count_frequency_days' => 'required|integer|min:1|max:365',
            'default_warehouse_location' => 'required|string|max:100',
        ]);

        $this->setSetting('warehouse.enable_multi_location', $this->enable_multi_location);
        $this->setSetting('warehouse.enable_batch_tracking', $this->enable_batch_tracking);
        $this->setSetting('warehouse.enable_serial_tracking', $this->enable_serial_tracking);
        $this->setSetting('warehouse.auto_allocate_stock', $this->auto_allocate_stock);
        $this->setSetting('warehouse.stock_allocation_method', $this->stock_allocation_method);
        $this->setSetting('warehouse.enable_negative_stock', $this->enable_negative_stock);
        $this->setSetting('warehouse.stock_count_frequency_days', $this->stock_count_frequency_days);
        $this->setSetting('warehouse.require_approval_for_adjustments', $this->require_approval_for_adjustments);
        $this->setSetting('warehouse.enable_barcode_scanning', $this->enable_barcode_scanning);
        $this->setSetting('warehouse.default_warehouse_location', $this->default_warehouse_location);

        Cache::forget('warehouse_settings');
        Cache::forget('system_settings_all');

        session()->flash('success', __('Warehouse settings saved successfully'));

        $this->redirectRoute('admin.settings.warehouse', navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.settings.warehouse-settings');
    }
}
