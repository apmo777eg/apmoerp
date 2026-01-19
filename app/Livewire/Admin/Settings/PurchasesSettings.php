<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Settings;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class PurchasesSettings extends Component
{
    // Purchase settings
    public string $purchase_invoice_prefix = 'PO-';

    public int $purchase_invoice_starting_number = 1000;

    public int $purchase_payment_terms_days = 30;

    public bool $auto_receive_on_purchase = false;

    public bool $require_purchase_approval = true;

    public float $purchase_approval_threshold = 10000;

    public bool $enable_purchase_requisitions = true;

    public bool $enable_grn = true;

    public int $grn_validity_days = 7;

    public bool $enable_3way_matching = false;

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
        $settings = Cache::remember('purchases_settings', 3600, function () {
            return SystemSetting::where('group', 'purchases')->pluck('value', 'key')->toArray();
        });

        $this->purchase_invoice_prefix = $settings['purchases.invoice_prefix'] ?? 'PO-';
        $this->purchase_invoice_starting_number = (int) ($settings['purchases.invoice_starting_number'] ?? 1000);
        $this->purchase_payment_terms_days = (int) ($settings['purchases.payment_terms_days'] ?? 30);
        $this->auto_receive_on_purchase = (bool) ($settings['purchases.auto_receive_on_purchase'] ?? false);
        $this->require_purchase_approval = (bool) ($settings['purchases.require_purchase_approval'] ?? true);
        $this->purchase_approval_threshold = decimal_float($settings['purchases.approval_threshold'] ?? 10000);
        $this->enable_purchase_requisitions = (bool) ($settings['purchases.enable_purchase_requisitions'] ?? true);
        $this->enable_grn = (bool) ($settings['purchases.enable_grn'] ?? true);
        $this->grn_validity_days = (int) ($settings['purchases.grn_validity_days'] ?? 7);
        $this->enable_3way_matching = (bool) ($settings['purchases.enable_3way_matching'] ?? false);
    }

    protected function setSetting(string $key, $value): void
    {
        SystemSetting::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'group' => 'purchases',
                'is_public' => false,
            ]
        );
    }

    public function save(): mixed
    {
        $this->validate([
            'purchase_invoice_prefix' => 'required|string|max:10',
            'purchase_invoice_starting_number' => 'required|integer|min:1',
            'purchase_payment_terms_days' => 'required|integer|min:0|max:365',
            'purchase_approval_threshold' => 'required|numeric|min:0',
            'grn_validity_days' => 'required|integer|min:1|max:90',
        ]);

        $this->setSetting('purchases.invoice_prefix', $this->purchase_invoice_prefix);
        $this->setSetting('purchases.invoice_starting_number', $this->purchase_invoice_starting_number);
        $this->setSetting('purchases.payment_terms_days', $this->purchase_payment_terms_days);
        $this->setSetting('purchases.auto_receive_on_purchase', $this->auto_receive_on_purchase);
        $this->setSetting('purchases.require_purchase_approval', $this->require_purchase_approval);
        $this->setSetting('purchases.approval_threshold', $this->purchase_approval_threshold);
        $this->setSetting('purchases.enable_purchase_requisitions', $this->enable_purchase_requisitions);
        $this->setSetting('purchases.enable_grn', $this->enable_grn);
        $this->setSetting('purchases.grn_validity_days', $this->grn_validity_days);
        $this->setSetting('purchases.enable_3way_matching', $this->enable_3way_matching);

        Cache::forget('purchases_settings');
        Cache::forget('system_settings_all');

        session()->flash('success', __('Purchase settings saved successfully'));

        $this->redirectRoute('admin.settings.purchases', navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.settings.purchases-settings');
    }
}
