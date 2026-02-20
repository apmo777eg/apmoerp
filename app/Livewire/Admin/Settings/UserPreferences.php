<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Settings;

use App\Models\UserPreference;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use App\Livewire\BaseComponent as Component;
class UserPreferences extends Component
{
    public string $theme = 'light';

    public int $session_timeout = 30;

    public bool $auto_logout = true;

    public ?string $default_printer = null;

    public array $dashboard_widgets = [];

    public array $pos_shortcuts = [];

    public array $notification_settings = [];

    public function mount(): void
    {
        $preferences = UserPreference::getForUser(Auth::id());

        $this->theme = $preferences->theme;
        $this->session_timeout = $preferences->session_timeout;
        $this->auto_logout = $preferences->auto_logout;
        $this->default_printer = $preferences->default_printer;
        $this->dashboard_widgets = $preferences->dashboard_widgets ?? [];
        $this->pos_shortcuts = $preferences->pos_shortcuts ?? [];
        $this->notification_settings = $preferences->notification_settings ?? [];
    }

    public function save(): void
    {
        $this->validate([
            'theme' => 'required|in:light,dark,system',
            'session_timeout' => 'required|integer|min:5|max:480',
            'auto_logout' => 'boolean',
        ]);

        $preferences = UserPreference::getForUser(Auth::id());
        $preferences->update([
            'theme' => $this->theme,
            'session_timeout' => $this->session_timeout,
            'auto_logout' => $this->auto_logout,
            'default_printer' => $this->default_printer,
            'dashboard_widgets' => $this->dashboard_widgets,
            'pos_shortcuts' => $this->pos_shortcuts,
            'notification_settings' => $this->notification_settings,
        ]);

        $this->dispatch('preferences-saved');
        $this->dispatch('theme-changed', theme: $this->theme);

        session()->flash('success', __('Preferences saved successfully'));
    }

    public function toggleWidget(string $widget): void
    {
        $this->dashboard_widgets[$widget] = ! ($this->dashboard_widgets[$widget] ?? false);
    }

    public function updateShortcut(string $key, string $action): void
    {
        $this->pos_shortcuts[$key] = $action;
    }

    public function toggleNotification(string $type): void
    {
        $this->notification_settings[$type] = ! ($this->notification_settings[$type] ?? false);
    }

    public function resetToDefaults(): void
    {
        $defaults = UserPreference::getDefaults();

        $this->theme = $defaults['theme'];
        $this->session_timeout = $defaults['session_timeout'];
        $this->auto_logout = $defaults['auto_logout'];
        $this->dashboard_widgets = $defaults['dashboard_widgets'];
        $this->pos_shortcuts = $defaults['pos_shortcuts'];
        $this->notification_settings = $defaults['notification_settings'];

        $this->save();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.admin.settings.user-preferences', [
            'availableWidgets' => [
                'sales_today' => __('Today\'s Sales'),
                'revenue_chart' => __('Revenue Chart'),
                'top_products' => __('Top Products'),
                'low_stock' => __('Low Stock Alerts'),
                'recent_orders' => __('Recent Orders'),
                'customer_stats' => __('Customer Statistics'),
                'pending_payments' => __('Pending Payments'),
                'monthly_comparison' => __('Monthly Comparison'),
            ],
            'availableActions' => [
                'new_sale' => __('New Sale'),
                'search_product' => __('Search Product'),
                'search_customer' => __('Search Customer'),
                'apply_discount' => __('Apply Discount'),
                'hold_sale' => __('Hold Sale'),
                'recall_held' => __('Recall Held'),
                'payment_cash' => __('Cash Payment'),
                'payment_card' => __('Card Payment'),
                'print_receipt' => __('Print Receipt'),
                'void_item' => __('Void Item'),
                'void_sale' => __('Void Sale'),
                'close_session' => __('Close Session'),
            ],
        ]);
    }
}
