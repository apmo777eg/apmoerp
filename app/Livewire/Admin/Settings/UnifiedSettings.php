<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Settings;

use App\Models\Media;
use App\Models\SystemSetting;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use App\Livewire\BaseComponent as Component;
#[Layout('layouts.app')]
class UnifiedSettings extends Component
{
    protected ?SettingsService $settings = null;
    public string $activeTab = 'general';

    /**
     * UI preference: show "Advanced" tabs in the navigation.
     *
     * - When false, the Settings page focuses on essential tabs only.
     * - When true, power-user / technical tabs appear (advanced, backup, integrations, ...).
     */
    public bool $showAdvancedTabs = false;

    public array $tabs = [
    'general' => 'General Settings',
    'branding' => 'Branding',
    'communications' => 'Email & WhatsApp',
    'inventory' => 'Inventory',
    'pos' => 'POS',
    'accounting' => 'Accounting',
    'sales' => 'Sales & Invoicing',
    'purchases' => 'Purchases',
    'warehouse' => 'Warehouse',
    'manufacturing' => 'Manufacturing',
    'hrm' => 'HRM & Payroll',
    'rental' => 'Rental',
    'fixed_assets' => 'Fixed Assets',
    'integrations' => 'Integrations & API',
    'notifications' => 'Notifications',
    'branch' => 'Branch Settings',
    'currencies' => 'Currencies',
    'rates' => 'Exchange Rates',
    'translations' => 'Translations',
    'security' => 'Security',
    'backup' => 'Backup',
    'advanced' => 'Advanced',
];

    public array $tabIcons = [
    'general' => 'M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4',
    'branding' => 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01',
    'communications' => 'M8 10h.01M12 10h.01M16 10h.01M21 16a2 2 0 01-2 2H7l-4 4V6a2 2 0 012-2h14a2 2 0 012 2v10z',
    'inventory' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
    'pos' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
    'accounting' => 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z',
    'sales' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    'purchases' => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z',
    'warehouse' => 'M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z',
    'manufacturing' => 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z',
    'hrm' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
    'rental' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
    'fixed_assets' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
    'integrations' => 'M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    'notifications' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
    'branch' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
    'currencies' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    'rates' => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4',
    'translations' => 'M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129',
    'security' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z',
    'backup' => 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12',
    'advanced' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
];

    public array $tabDescriptions = [
    'general' => 'Company information, timezone, and regional settings',
    'branding' => 'Logo, colors, and company appearance',
    'communications' => 'Email (SMTP) and WhatsApp integration settings',
    'inventory' => 'Stock management and costing method',
    'pos' => 'Point of Sale terminal settings',
    'accounting' => 'Chart of accounts and financial settings',
    'sales' => 'Invoice numbering and sales defaults',
    'purchases' => 'Purchase order settings',
    'warehouse' => 'Warehouse locations and stock alerts',
    'manufacturing' => 'Production and BOM settings',
    'hrm' => 'Employee, payroll, and attendance settings',
    'rental' => 'Rental units and contracts settings',
    'fixed_assets' => 'Asset depreciation settings',
    'integrations' => 'API keys and third-party connections',
    'notifications' => 'Email and alert preferences',
    'branch' => 'Branch-specific settings',
    'currencies' => 'Manage currencies used across the system',
    'rates' => 'Manage currency exchange rates',
    'translations' => 'Manage Arabic and English translations',
    'security' => 'Password policies and session settings',
    'backup' => 'Database backup settings',
    'advanced' => 'Developer and system settings',
];

    /**
     * Which tabs should be considered "advanced" (hidden in Essential mode).
     *
     * NOTE: This is a UI/UX classification only. Authorization is still enforced
     * by permission checks inside save/restore actions.
     */
    protected array $advancedTabKeys = [
        'manufacturing',
        'hrm',
        'rental',
        'fixed_assets',
        'integrations',
        'backup',
        'advanced',
        // "Link" tabs (often just shortcuts)
        'currencies',
        'rates',
        'translations',
    ];

    /**
     * Extra keywords used by the Settings Search.
     * These are intentionally bilingual to make search helpful in both Arabic/English UI.
     */
    protected array $tabSearchKeywords = [
        'general' => 'company name email phone timezone date currency شركة اسم بريد ايميل هاتف تليفون منطقة زمنية تاريخ عملة',
        'branding' => 'logo favicon colors appearance شعار لوجو ايقونة ألوان مظهر',
        'communications' => 'email smtp mail whatsapp واتساب بريد ايميل token توكن api',
        'inventory' => 'inventory stock costing threshold مخزون جرد تكلفة تنبيه حد',
        'pos' => 'pos point of sale receipt discount كاشير نقاط بيع إيصال خصم',
        'accounting' => 'accounting chart of accounts finance محاسبة حسابات مالية',
        'sales' => 'sales invoice prefix payment terms مبيعات فاتورة رقم دفعات',
        'purchases' => 'purchases suppliers purchase orders مشتريات موردين أوامر شراء',
        'warehouse' => 'warehouse locations stock alerts مخازن مواقع تنبيهات مخزون',
        'manufacturing' => 'manufacturing production bom تصنيع انتاج bill of materials',
        'hrm' => 'hrm payroll attendance employees موارد بشرية رواتب حضور موظفين',
        'rental' => 'rental contracts units إيجار عقود وحدات',
        'fixed_assets' => 'fixed assets depreciation أصول ثابتة اهلاك',
        'integrations' => 'integrations api webhooks keys تكامل api webhooks مفاتيح',
        'notifications' => 'notifications alerts email اشعارات تنبيهات بريد',
        'branch' => 'branch multi-branch الفروع متعدد الفروع',
        'security' => 'security 2fa session password أمان تحقق ثنائي جلسة كلمة مرور',
        'backup' => 'backup restore نسخ احتياطي استعادة',
        'advanced' => 'advanced developer system cache ttl متقدم مطور نظام كاش',
        'currencies' => 'currencies iso currency codes عملات أكواد',
        'rates' => 'exchange rates currency rate أسعار صرف',
        'translations' => 'translations languages Arabic English ترجمة لغات عربي انجليزي',
    ];

    /**
     * Livewire only persists public properties between requests.
     * We keep the SettingsService as a protected property, so it must be (re)injected
     * on every request (initial render + subsequent updates).
 */
    public function boot(SettingsService $settings): void
{
    $this->settings = $settings;
}


    /**
     * Safety net: if for any reason the service wasn't injected (tests, edge cases),
     * lazily resolve it from the container.
 */
    protected function settingsService(): SettingsService
{
    return $this->settings ??= app(SettingsService::class);
}
// General settings
    public string $company_name = '';

    public string $company_email = '';

    public string $company_phone = '';

    public string $timezone = 'UTC';

    public string $date_format = 'Y-m-d';

    public string $default_currency = 'EGP';

    // Branding settings
    public ?int $branding_logo_id = null;

    public ?int $branding_favicon_id = null;

    public string $branding_logo = '';  // Legacy URL support

    public string $branding_favicon = '';  // Legacy URL support

    public string $branding_primary_color = '#10b981';

    public string $branding_secondary_color = '#3b82f6';

    public string $branding_tagline = '';

    // Inventory settings
    public string $inventory_costing_method = 'FIFO';

    public int $stock_alert_threshold = 10;

    public bool $use_per_product_threshold = true;

    // POS settings
    public bool $pos_allow_negative_stock = false;

    public int $pos_max_discount_percent = 20;

    public bool $pos_auto_print_receipt = true;

    public string $pos_rounding_rule = 'none';

    // Accounting settings
    public string $accounting_coa_template = 'standard';

    // HRM settings
    public int $hrm_working_days_per_week = 5;

    public float $hrm_working_hours_per_day = 8.0;

    public int $hrm_late_arrival_threshold = 15;

    public string $hrm_transport_allowance_type = 'percentage';

    public float $hrm_transport_allowance_value = 10.0;

    public string $hrm_housing_allowance_type = 'percentage';

    public float $hrm_housing_allowance_value = 0.0;

    public float $hrm_meal_allowance = 0.0;

    public float $hrm_health_insurance_deduction = 0.0;

    // Rental settings
    public int $rental_grace_period_days = 5;

    public string $rental_penalty_type = 'percentage';

    public float $rental_penalty_value = 5.0;

    // Sales settings
    public int $sales_payment_terms_days = 30;

    public string $sales_invoice_prefix = 'INV-';

    public int $sales_invoice_starting_number = 1000;

    // Branch settings
    public bool $multi_branch = false;

    public bool $require_branch_selection = true;

    // Security settings
    public bool $require_2fa = false;

    public int $session_timeout = 120;

    public bool $enable_audit_log = true;

    // Advanced settings
    public bool $enable_api = true;

    public bool $enable_webhooks = false;

    public int $cache_ttl = 3600;

    // Backup settings
    public bool $auto_backup = false;

    public string $backup_frequency = 'daily';

    public int $backup_retention_days = 30;

    public string $backup_storage = 'local';

    // Notifications
    public bool $notifications_low_stock = true;

    public bool $notifications_payment_due = true;

    public bool $notifications_new_order = true;


// Communications: Email (SMTP)
public string $smtp_host = '';
public int $smtp_port = 587;
public string $smtp_username = '';
public string $smtp_password = ''; // Never prefilled for security
public string $smtp_encryption = 'tls'; // tls|ssl|none
public string $smtp_from_address = '';
public string $smtp_from_name = '';
public bool $smtp_password_configured = false;

// Communications: WhatsApp
public bool $whatsapp_enabled = false;
public string $whatsapp_provider = 'link'; // link|cloud
public string $whatsapp_default_country_code = '20';
public string $whatsapp_business_number = '';
public string $whatsapp_cloud_phone_number_id = '';
public string $whatsapp_cloud_access_token = ''; // Never prefilled for security
public string $whatsapp_cloud_api_version = 'v19.0';
public bool $whatsapp_cloud_token_configured = false;

    public function mount(): void
    {

        $user = Auth::user();
        if (! $user || ! $user->can('settings.view')) {
            abort(403);
        }

        // UI mode controls: keep Settings user-friendly by default
        $simpleMode = (bool) config('erp_ui.simple_mode', true);
        $hideAdvanced = (bool) config('erp_ui.hide_advanced_settings', true);
        $hideCodeEditors = (bool) config('erp_ui.hide_code_editors', true);
        $hideLinkTabs = (bool) config('erp_ui.hide_settings_link_tabs', true);

        // Default behavior:
        // - Simple mode OR hide_advanced_settings => show essentials only
        // - Otherwise, show all settings tabs
        $this->showAdvancedTabs = ! ($simpleMode || $hideAdvanced);

        $tabsToHide = [];

        if ($simpleMode && $hideLinkTabs) {
            // These are quick-link tabs (they point to dedicated pages). Keep the Settings UI clean.
            $tabsToHide = array_merge($tabsToHide, ['currencies', 'rates', 'translations']);
        }

        if ($hideCodeEditors) {
            // Anything that behaves like a template/code editor can be treated as advanced
            $tabsToHide = array_merge($tabsToHide, ['developer']);
        }

        foreach (array_unique($tabsToHide) as $tabKey) {
            unset($this->tabs[$tabKey], $this->tabIcons[$tabKey], $this->tabDescriptions[$tabKey]);
        }


        // Get tab from query string (supports both ?tab= and hash via JS)
        $this->activeTab = request()->query('tab', 'general');

        // Validate the tab exists
        if (! array_key_exists($this->activeTab, $this->tabs)) {
            $this->activeTab = 'general';
        }

        // If a user navigates directly to an Advanced tab (URL param/hash),
        // automatically reveal advanced tabs so navigation doesn't feel broken.
        if ($this->isAdvancedTab($this->activeTab)) {
            $this->showAdvancedTabs = true;
        }

        $this->loadSettings();
    }

    /**
     * Helper to properly cast boolean values from settings
     * Handles cases where value might be string "0", "false", or actual boolean
     */
    protected function castToBool(mixed $value, bool $default = false): bool
    {
        if ($value === null) {
            return $default;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $lower = strtolower($value);
            if (in_array($lower, ['0', 'false', 'no', 'off', ''], true)) {
                return false;
            }
            if (in_array($lower, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }
        }

        return (bool) $value;
    }

    /**
     * Determine whether a tab is considered "advanced" for UX purposes.
     */
    protected function isAdvancedTab(string $tabKey): bool
    {
        return in_array($tabKey, $this->advancedTabKeys, true);
    }

    /**
     * Build grouped tabs for the Settings sidebar navigation.
     *
     * @return array<int, array{key:string,label:string,tabs:array<int,string>}>
     */
    protected function buildTabGroups(): array
    {
        $groups = [
            [
                'key' => 'system',
                'label' => __('System'),
                'tabs' => ['general', 'branding', 'communications', 'notifications', 'security', 'backup', 'advanced'],
            ],
            [
                'key' => 'sales',
                'label' => __('Sales'),
                'tabs' => ['sales', 'pos', 'accounting'],
            ],
            [
                'key' => 'purchases',
                'label' => __('Purchases'),
                'tabs' => ['purchases'],
            ],
            [
                'key' => 'inventory',
                'label' => __('Inventory'),
                'tabs' => ['inventory', 'warehouse', 'manufacturing', 'fixed_assets', 'rental'],
            ],
            [
                'key' => 'organization',
                'label' => __('Organization'),
                'tabs' => ['branch', 'hrm'],
            ],
            [
                'key' => 'integrations',
                'label' => __('Integrations'),
                'tabs' => ['integrations', 'currencies', 'rates', 'translations'],
            ],
        ];

        // Filter out tabs not available in the current UI mode.
        foreach ($groups as &$group) {
            $group['tabs'] = array_values(array_filter($group['tabs'], fn (string $tab): bool => array_key_exists($tab, $this->tabs)));
        }
        unset($group);

        // Any tabs not explicitly listed should still appear under "Other".
        $listed = [];
        foreach ($groups as $group) {
            foreach ($group['tabs'] as $tab) {
                $listed[$tab] = true;
            }
        }

        $otherTabs = array_values(array_filter(array_keys($this->tabs), fn (string $tab): bool => ! isset($listed[$tab])));
        if (! empty($otherTabs)) {
            $groups[] = [
                'key' => 'other',
                'label' => __('Other'),
                'tabs' => $otherTabs,
            ];
        }

        // Remove empty groups to keep the navigation clean.
        $groups = array_values(array_filter($groups, fn (array $g): bool => ! empty($g['tabs'])));

        return $groups;
    }

    protected function loadSettings(): void
    {
        // Use SettingsService for proper type handling instead of raw pluck
        // This ensures boolean values are properly resolved
        $allSettings = $this->settingsService()->all();

        // Load general settings - use canonical key names from config/settings.php
        $this->company_name = $allSettings['general.company_name']
            ?? $allSettings['company.name']  // Legacy support
            ?? config('app.name', 'HugouERP');
        $this->company_email = $allSettings['general.company_email']
            ?? $allSettings['company.email']  // Legacy support
            ?? '';
        $this->company_phone = $allSettings['general.company_phone']
            ?? $allSettings['company.phone']  // Legacy support
            ?? '';
        $this->timezone = $allSettings['system.timezone']
            ?? $allSettings['branding.timezone']
            ?? $allSettings['app.timezone']  // Legacy support
            ?? config('app.timezone', 'UTC');
        $this->date_format = $allSettings['system.date_format']
            ?? $allSettings['branding.date_format']
            ?? $allSettings['app.date_format']  // Legacy support
            ?? 'Y-m-d';
        $this->default_currency = $allSettings['general.default_currency'] ?? 'EGP';

        // Load branding settings
        $this->branding_logo_id = isset($allSettings['branding.logo_id']) ? (int) $allSettings['branding.logo_id'] : null;
        $this->branding_favicon_id = isset($allSettings['branding.favicon_id']) ? (int) $allSettings['branding.favicon_id'] : null;
        $this->branding_logo = $allSettings['branding.logo'] ?? '';
        $this->branding_favicon = $allSettings['branding.favicon'] ?? '';
        $this->branding_primary_color = $allSettings['branding.primary_color'] ?? '#10b981';
        $this->branding_secondary_color = $allSettings['branding.secondary_color'] ?? '#3b82f6';
        $this->branding_tagline = $allSettings['branding.tagline'] ?? '';

        // Load inventory settings - use canonical key names from config/settings.php
        $this->inventory_costing_method = $allSettings['inventory.default_costing_method']
            ?? $allSettings['inventory.costing_method']  // Legacy support
            ?? 'FIFO';
        $this->stock_alert_threshold = (int) ($allSettings['inventory.stock_alert_threshold'] ?? 10);
        $this->use_per_product_threshold = $this->castToBool($allSettings['inventory.use_per_product_threshold'] ?? null, true);

        // Load POS settings - Also update inventory.allow_negative_stock for services
        $this->pos_allow_negative_stock = $this->castToBool($allSettings['pos.allow_negative_stock'] ?? null, false);
        $this->pos_max_discount_percent = (int) ($allSettings['pos.max_discount_percent'] ?? 20);
        $this->pos_auto_print_receipt = $this->castToBool($allSettings['pos.auto_print_receipt'] ?? null, true);
        $this->pos_rounding_rule = $allSettings['pos.rounding_rule'] ?? 'none';

        // Load accounting settings
        $this->accounting_coa_template = $allSettings['accounting.default_coa_template']
            ?? $allSettings['accounting.coa_template']  // Legacy support
            ?? 'standard';

        // Load HRM settings
        $this->hrm_working_days_per_week = (int) ($allSettings['hrm.working_days_per_week'] ?? 5);
        $this->hrm_working_hours_per_day = decimal_float($allSettings['hrm.working_hours_per_day'] ?? 8.0);
        $this->hrm_late_arrival_threshold = (int) ($allSettings['hrm.late_arrival_threshold'] ?? 15);
        $this->hrm_transport_allowance_type = $allSettings['hrm.transport_allowance_type'] ?? 'percentage';
        $this->hrm_transport_allowance_value = decimal_float($allSettings['hrm.transport_allowance_value'] ?? 10.0);
        $this->hrm_housing_allowance_type = $allSettings['hrm.housing_allowance_type'] ?? 'percentage';
        $this->hrm_housing_allowance_value = decimal_float($allSettings['hrm.housing_allowance_value'] ?? 0.0);
        $this->hrm_meal_allowance = decimal_float($allSettings['hrm.meal_allowance'] ?? 0.0);
        $this->hrm_health_insurance_deduction = decimal_float($allSettings['hrm.health_insurance_deduction'] ?? 0.0);

        // Load rental settings
        $this->rental_grace_period_days = (int) ($allSettings['rental.grace_period_days'] ?? 5);
        $this->rental_penalty_type = $allSettings['rental.penalty_type'] ?? 'percentage';
        $this->rental_penalty_value = decimal_float($allSettings['rental.penalty_value'] ?? 5.0);

        // Load sales settings
        $this->sales_payment_terms_days = (int) ($allSettings['sales.default_payment_terms']
            ?? $allSettings['sales.payment_terms_days']  // Legacy support
            ?? 30);
        $this->sales_invoice_prefix = $allSettings['sales.invoice_prefix'] ?? 'INV-';
        $this->sales_invoice_starting_number = (int) ($allSettings['sales.invoice_starting_number'] ?? 1000);

        // Load branch settings
        $this->multi_branch = $this->castToBool($allSettings['system.multi_branch'] ?? null, false);
        $this->require_branch_selection = $this->castToBool($allSettings['system.require_branch_selection'] ?? null, true);

        // Load security settings (supporting legacy key for backward compatibility)
        $this->require_2fa = $this->castToBool(
            $allSettings['security.2fa_required'] ?? $allSettings['security.require_2fa'] ?? null,
            false
        );
        $this->session_timeout = (int) ($allSettings['security.session_timeout'] ?? 120);
        $this->enable_audit_log = $this->castToBool($allSettings['security.enable_audit_log'] ?? null, true);

        // Load advanced settings
        $this->enable_api = $this->castToBool($allSettings['advanced.enable_api'] ?? null, true);
        $this->enable_webhooks = $this->castToBool($allSettings['advanced.enable_webhooks'] ?? null, false);
        $this->cache_ttl = (int) ($allSettings['advanced.cache_ttl'] ?? 3600);

        // Load backup settings
        $this->auto_backup = $this->castToBool($allSettings['backup.auto_backup'] ?? null, false);
        $this->backup_frequency = $allSettings['backup.frequency'] ?? 'daily';
        $this->backup_retention_days = (int) ($allSettings['backup.retention_days'] ?? 30);
        $this->backup_storage = $allSettings['backup.storage'] ?? 'local';

        // Load notification settings
        $this->notifications_low_stock = $this->castToBool($allSettings['notifications.low_stock_enabled']
            ?? $allSettings['notifications.low_stock']  // Legacy support
            ?? null, true);
        $this->notifications_payment_due = $this->castToBool($allSettings['notifications.payment_due_enabled']
            ?? $allSettings['notifications.payment_due']  // Legacy support
            ?? null, true);
        $this->notifications_new_order = $this->castToBool($allSettings['notifications.new_order_enabled']
            ?? $allSettings['notifications.new_order']  // Legacy support
            ?? null, true);


// Load communications settings (Email + WhatsApp)
$this->smtp_host = $allSettings['mail.smtp_host'] ?? (string) config('mail.mailers.smtp.host', '');
$this->smtp_port = (int) ($allSettings['mail.smtp_port'] ?? config('mail.mailers.smtp.port', 587));
$this->smtp_username = $allSettings['mail.smtp_username'] ?? (string) config('mail.mailers.smtp.username', '');
$enc = $allSettings['mail.smtp_encryption'] ?? config('mail.mailers.smtp.encryption');
$this->smtp_encryption = $enc ? (string) $enc : 'none';
$this->smtp_from_address = $allSettings['mail.from_address'] ?? (string) config('mail.from.address', '');
$this->smtp_from_name = $allSettings['mail.from_name'] ?? (string) config('mail.from.name', '');
$this->smtp_password_configured = ! empty($allSettings['mail.smtp_password'] ?? null);

$this->whatsapp_enabled = $this->castToBool($allSettings['whatsapp.enabled'] ?? null, false);
$this->whatsapp_provider = $allSettings['whatsapp.provider'] ?? 'link';
$this->whatsapp_default_country_code = $allSettings['whatsapp.default_country_code'] ?? '20';
$this->whatsapp_business_number = $allSettings['whatsapp.business_number'] ?? '';
$this->whatsapp_cloud_phone_number_id = $allSettings['whatsapp.cloud.phone_number_id'] ?? '';
$this->whatsapp_cloud_api_version = $allSettings['whatsapp.cloud.api_version'] ?? 'v19.0';
$this->whatsapp_cloud_token_configured = ! empty($allSettings['whatsapp.cloud.access_token'] ?? null);
    }

    protected function getSetting(string $key, $default = null)
    {
        return $this->settingsService()->get($key, $default);
    }

    protected function setSetting(string $key, $value, string $group = 'general', string $type = 'string'): void
    {
        $this->settingsService()->set($key, $value, [
            'group' => $group,
            'type' => $type,
            'is_public' => false,
        ]);
    }

    /**
     * Clear all settings caches for consistency
     */
    protected function clearSettingsCaches(): void
    {
        Cache::forget('system_settings');
        Cache::forget('system_settings_all');
        $this->settingsService()->clearCache();
    }

    /**
     * Standardized save wrapper for settings tabs.
     *
     * - Success: toast success
     * - Validation errors: toast error + keep field-level error messages
     * - Unexpected errors: toast error + report()
     */
    protected function persistSettings(array $rules, callable $persist, string $successMessage): void
    {
        $this->resetErrorBag();

        $user = Auth::user();
        if (! $user || ! ($user->can('settings.update') || $user->can('settings.manage'))) {
            abort(403);
        }

        try {
            if (! empty($rules)) {
                $this->validate($rules);
            }

            $persist();
            $this->clearSettingsCaches();

            $this->dispatch('notify', type: 'success', message: $successMessage);
        } catch (ValidationException $e) {
            $first = collect($e->validator->errors()->all())->first()
                ?? __('Please correct the highlighted fields.');
            $this->dispatch('notify', type: 'error', message: $first);
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('notify', type: 'error', message: __('Failed to save settings. Please try again.'));
        }
    }

    public function switchTab(string $tab): void
    {
        if (array_key_exists($tab, $this->tabs)) {
            $this->activeTab = $tab;
            // Update URL with the new tab (dispatched to JS)
            $this->dispatch('tab-changed', tab: $tab);
        }
    }

    /**
     * When a user turns OFF Advanced mode while currently on an advanced tab,
     * switch back to a safe essential tab to avoid a confusing empty navigation.
     */
    public function updatedShowAdvancedTabs(bool $value): void
    {
        if ($value === false && $this->isAdvancedTab($this->activeTab)) {
            $fallback = array_key_exists('general', $this->tabs) ? 'general' : array_key_first($this->tabs);
            $this->activeTab = $fallback ?: 'general';
            $this->dispatch('tab-changed', tab: $this->activeTab);
        }
    }

    protected function redirectToTab(?string $tab = null): mixed
    {
        $tab ??= $this->activeTab;

        return $this->redirectRoute('admin.settings', ['tab' => $tab], navigate: true);
    }

    public function saveGeneral(): void
    {
        $this->persistSettings([
            'company_name' => 'required|string|max:255',
            'company_email' => 'nullable|email|max:255',
            'company_phone' => 'nullable|string|max:50',
            'timezone' => 'required|string',
            'date_format' => 'required|string',
            // ISO 4217 (3 letters): EGP, USD, ...
            'default_currency' => 'required|string|size:3',
        ], function (): void {
            // Use canonical key names from config/settings.php
            $this->setSetting('general.company_name', $this->company_name, 'general');
            $this->setSetting('general.company_email', $this->company_email, 'general');
            $this->setSetting('general.company_phone', $this->company_phone, 'general');

            // Canonical system keys
$this->setSetting('system.timezone', $this->timezone, 'system');
$this->setSetting('system.date_format', $this->date_format, 'system');

// Legacy keys kept for backward compatibility
$this->setSetting('branding.timezone', $this->timezone, 'branding');
$this->setSetting('branding.date_format', $this->date_format, 'branding');

$this->setSetting('general.default_currency', $this->default_currency, 'general');
}, __('General settings saved successfully'));
    }

    #[On('media-selected')]
    public function handleMediaSelected(string $fieldId, int $mediaId, array $media): void
    {
        if ($fieldId === 'branding-logo') {
            $this->branding_logo_id = $mediaId;
            $this->branding_logo = $media['url'] ?? '';
        } elseif ($fieldId === 'branding-favicon') {
            $this->branding_favicon_id = $mediaId;
            $this->branding_favicon = $media['url'] ?? '';
        }
    }

    #[On('media-cleared')]
    public function handleMediaCleared(string $fieldId): void
    {
        if ($fieldId === 'branding-logo') {
            $this->branding_logo_id = null;
            $this->branding_logo = '';
        } elseif ($fieldId === 'branding-favicon') {
            $this->branding_favicon_id = null;
            $this->branding_favicon = '';
        }
    }

    public function saveBranding(): void
    {
        $this->persistSettings([
            'branding_primary_color' => 'required|string|max:7',
            'branding_secondary_color' => 'required|string|max:7',
            'branding_tagline' => 'nullable|string|max:255',
        ], function (): void {
            // Save media IDs (preferred) and also URLs for backward compatibility
            $this->setSetting('branding.logo_id', $this->branding_logo_id, 'branding');
            $this->setSetting('branding.favicon_id', $this->branding_favicon_id, 'branding');

            // Get URLs from media if IDs are set, otherwise use the legacy URL values
            $logoUrl = $this->branding_logo;
            $faviconUrl = $this->branding_favicon;

            if ($this->branding_logo_id) {
                $logoMedia = Media::find($this->branding_logo_id);
                $logoUrl = $logoMedia?->url ?? $this->branding_logo;
            }

            if ($this->branding_favicon_id) {
                $faviconMedia = Media::find($this->branding_favicon_id);
                $faviconUrl = $faviconMedia?->url ?? $this->branding_favicon;
            }

            $this->setSetting('branding.logo', $logoUrl, 'branding');
            $this->setSetting('branding.favicon', $faviconUrl, 'branding');
            $this->setSetting('branding.primary_color', $this->branding_primary_color, 'branding');
            $this->setSetting('branding.secondary_color', $this->branding_secondary_color, 'branding');
            $this->setSetting('branding.tagline', $this->branding_tagline, 'branding');
        }, __('Branding settings saved successfully'));
    }

    public function saveBranch(): void
    {
        $this->persistSettings([], function (): void {
            $this->setSetting('system.multi_branch', $this->multi_branch, 'branch', 'boolean');
            $this->setSetting('system.require_branch_selection', $this->require_branch_selection, 'branch', 'boolean');
        }, __('Branch settings saved successfully'));
    }

    public function saveSecurity(): void
    {
        $this->persistSettings([
            'session_timeout' => 'required|integer|min:5|max:1440',
        ], function (): void {
            // Normalize to the current key and remove the legacy one to avoid drift
            SystemSetting::where('setting_key', 'security.require_2fa')->delete();
            $this->setSetting('security.2fa_required', $this->require_2fa, 'security', 'boolean');
            $this->setSetting('security.session_timeout', $this->session_timeout, 'security', 'integer');
            $this->setSetting('security.enable_audit_log', $this->enable_audit_log, 'security', 'boolean');
        }, __('Security settings saved successfully'));
    }

    public function saveAdvanced(): void
    {
        $this->persistSettings([
            'cache_ttl' => 'required|integer|min:60|max:86400',
        ], function (): void {
            $this->setSetting('advanced.enable_api', $this->enable_api, 'advanced', 'boolean');
            $this->setSetting('advanced.enable_webhooks', $this->enable_webhooks, 'advanced', 'boolean');
            $this->setSetting('advanced.cache_ttl', $this->cache_ttl, 'advanced', 'integer');

            // Clear API enabled cache for middleware
            Cache::forget('api_enabled_setting');
        }, __('Advanced settings saved successfully'));
    }

    public function saveBackup(): void
    {
        $this->persistSettings([
            'backup_retention_days' => 'required|integer|min:1|max:365',
            'backup_frequency' => 'required|in:daily,weekly,monthly',
            'backup_storage' => 'required|in:local,s3,ftp',
        ], function (): void {
            $this->setSetting('backup.auto_backup', $this->auto_backup, 'backup', 'boolean');
            $this->setSetting('backup.frequency', $this->backup_frequency, 'backup');
            $this->setSetting('backup.retention_days', $this->backup_retention_days, 'backup', 'integer');
            $this->setSetting('backup.storage', $this->backup_storage, 'backup');
        }, __('Backup settings saved successfully'));
    }

    public function saveInventory(): void
    {
        $this->persistSettings([
            'inventory_costing_method' => 'required|in:FIFO,LIFO,AVG',
            'stock_alert_threshold' => 'required|integer|min:0',
        ], function (): void {
            // Use canonical key inventory.default_costing_method as per config/settings.php
            $this->setSetting('inventory.default_costing_method', $this->inventory_costing_method, 'inventory');
            $this->setSetting('inventory.stock_alert_threshold', $this->stock_alert_threshold, 'inventory', 'integer');
            $this->setSetting('inventory.use_per_product_threshold', $this->use_per_product_threshold, 'inventory', 'boolean');
        }, __('Inventory settings saved successfully'));
    }

    public function savePos(): void
    {
        $this->persistSettings([
            'pos_max_discount_percent' => 'required|integer|min:0|max:100',
            'pos_rounding_rule' => 'required|in:none,0.05,0.10,0.25,0.50,1.00',
        ], function (): void {
            // Save to both pos.allow_negative_stock and inventory.allow_negative_stock
            // to ensure consistency between POS UI setting and inventory services
            $this->setSetting('pos.allow_negative_stock', $this->pos_allow_negative_stock, 'pos', 'boolean');
            $this->setSetting('inventory.allow_negative_stock', $this->pos_allow_negative_stock, 'inventory', 'boolean');
            $this->setSetting('pos.max_discount_percent', $this->pos_max_discount_percent, 'pos', 'integer');
            $this->setSetting('pos.auto_print_receipt', $this->pos_auto_print_receipt, 'pos', 'boolean');
            $this->setSetting('pos.rounding_rule', $this->pos_rounding_rule, 'pos');
        }, __('POS settings saved successfully'));
    }

    public function saveAccounting(): void
    {
        $this->persistSettings([
            'accounting_coa_template' => 'required|in:standard,retail,service',
        ], function (): void {
            // Use canonical key accounting.default_coa_template as per config/settings.php
            $this->setSetting('accounting.default_coa_template', $this->accounting_coa_template, 'accounting');
        }, __('Accounting settings saved successfully'));
    }

    public function saveHrm(): void
    {
        $this->persistSettings([
            'hrm_working_days_per_week' => 'required|integer|min:1|max:7',
            'hrm_working_hours_per_day' => 'required|numeric|min:1|max:24',
            'hrm_late_arrival_threshold' => 'required|integer|min:0',
            'hrm_transport_allowance_type' => 'required|in:percentage,fixed',
            'hrm_transport_allowance_value' => 'required|numeric|min:0',
            'hrm_housing_allowance_type' => 'required|in:percentage,fixed',
            'hrm_housing_allowance_value' => 'required|numeric|min:0',
            'hrm_meal_allowance' => 'required|numeric|min:0',
            'hrm_health_insurance_deduction' => 'required|numeric|min:0',
        ], function (): void {
            $this->setSetting('hrm.working_days_per_week', $this->hrm_working_days_per_week, 'hrm', 'integer');
            $this->setSetting('hrm.working_hours_per_day', $this->hrm_working_hours_per_day, 'hrm', 'number');
            $this->setSetting('hrm.late_arrival_threshold', $this->hrm_late_arrival_threshold, 'hrm', 'integer');
            $this->setSetting('hrm.transport_allowance_type', $this->hrm_transport_allowance_type, 'hrm');
            $this->setSetting('hrm.transport_allowance_value', $this->hrm_transport_allowance_value, 'hrm', 'number');
            $this->setSetting('hrm.housing_allowance_type', $this->hrm_housing_allowance_type, 'hrm');
            $this->setSetting('hrm.housing_allowance_value', $this->hrm_housing_allowance_value, 'hrm', 'number');
            $this->setSetting('hrm.meal_allowance', $this->hrm_meal_allowance, 'hrm', 'number');
            $this->setSetting('hrm.health_insurance_deduction', $this->hrm_health_insurance_deduction, 'hrm', 'number');
        }, __('HRM settings saved successfully'));
    }

    public function saveRental(): void
    {
        $this->persistSettings([
            'rental_grace_period_days' => 'required|integer|min:0',
            'rental_penalty_type' => 'required|in:percentage,fixed',
            'rental_penalty_value' => 'required|numeric|min:0',
        ], function (): void {
            $this->setSetting('rental.grace_period_days', $this->rental_grace_period_days, 'rental', 'integer');
            $this->setSetting('rental.penalty_type', $this->rental_penalty_type, 'rental');
            $this->setSetting('rental.penalty_value', $this->rental_penalty_value, 'rental', 'number');
        }, __('Rental settings saved successfully'));
    }

    public function saveSales(): void
    {
        $this->persistSettings([
            'sales_payment_terms_days' => 'required|integer|min:0',
            'sales_invoice_prefix' => 'required|string|max:10',
            'sales_invoice_starting_number' => 'required|integer|min:1',
        ], function (): void {
            // Use canonical key sales.default_payment_terms as per config/settings.php
            $this->setSetting('sales.default_payment_terms', $this->sales_payment_terms_days, 'sales', 'integer');
            $this->setSetting('sales.invoice_prefix', $this->sales_invoice_prefix, 'sales');
            $this->setSetting('sales.invoice_starting_number', $this->sales_invoice_starting_number, 'sales', 'integer');
        }, __('Sales settings saved successfully'));
    }

    public function saveCommunications(): void
    {
        $rules = [
                'smtp_host' => 'nullable|string|max:255',
                'smtp_port' => 'nullable|integer|min:1|max:65535',
                'smtp_username' => 'nullable|string|max:255',
                'smtp_password' => 'nullable|string|max:255',
                'smtp_encryption' => 'required|in:none,tls,ssl',
                'smtp_from_address' => 'nullable|email|max:255',
                'smtp_from_name' => 'nullable|string|max:255',

                'whatsapp_enabled' => 'boolean',
                'whatsapp_provider' => 'required|in:link,cloud',
                'whatsapp_default_country_code' => 'nullable|string|max:8',
                'whatsapp_business_number' => 'nullable|string|max:30',
                'whatsapp_cloud_phone_number_id' => 'nullable|string|max:50',
                'whatsapp_cloud_access_token' => 'nullable|string|max:500',
                'whatsapp_cloud_api_version' => 'nullable|string|max:30',
            ];

            // If WhatsApp is enabled and provider is Cloud API, require minimum fields
            if ($this->whatsapp_enabled && $this->whatsapp_provider === 'cloud') {
                $rules['whatsapp_cloud_phone_number_id'] = 'required|string|max:50';

                // Access token is required only if not already configured (we never prefill it)
                if (! $this->whatsapp_cloud_token_configured) {
                    $rules['whatsapp_cloud_access_token'] = 'required|string|max:500';
                }
            }

            $this->persistSettings($rules, function (): void {
                // SMTP settings
                $this->setSetting('mail.smtp_host', $this->smtp_host, 'mail');
                $this->setSetting('mail.smtp_port', $this->smtp_port, 'mail', 'integer');
                $this->setSetting('mail.smtp_username', $this->smtp_username, 'mail');
                $this->setSetting('mail.smtp_encryption', $this->smtp_encryption, 'mail');
                $this->setSetting('mail.from_address', $this->smtp_from_address, 'mail');
                $this->setSetting('mail.from_name', $this->smtp_from_name, 'mail');

                // Never overwrite saved password unless the user types a new one
                if (filled($this->smtp_password)) {
                    $this->settingsService()->set('mail.smtp_password', $this->smtp_password, [
                        'group' => 'mail',
                        'type' => 'string',
                        'is_public' => false,
                        'is_encrypted' => true,
                    ]);
                    $this->smtp_password_configured = true;
                    $this->smtp_password = '';
                }

                // WhatsApp settings
                $this->setSetting('whatsapp.enabled', $this->whatsapp_enabled, 'whatsapp', 'boolean');
                $this->setSetting('whatsapp.provider', $this->whatsapp_provider, 'whatsapp');
                $this->setSetting('whatsapp.default_country_code', $this->whatsapp_default_country_code, 'whatsapp');
                $this->setSetting('whatsapp.business_number', $this->whatsapp_business_number, 'whatsapp');
                $this->setSetting('whatsapp.cloud.phone_number_id', $this->whatsapp_cloud_phone_number_id, 'whatsapp');
                $this->setSetting('whatsapp.cloud.api_version', $this->whatsapp_cloud_api_version, 'whatsapp');

                // Never overwrite saved token unless the user types a new one
                if (filled($this->whatsapp_cloud_access_token)) {
                    $this->settingsService()->set('whatsapp.cloud.access_token', $this->whatsapp_cloud_access_token, [
                        'group' => 'whatsapp',
                        'type' => 'string',
                        'is_public' => false,
                        'is_encrypted' => true,
                    ]);
                    $this->whatsapp_cloud_token_configured = true;
                    $this->whatsapp_cloud_access_token = '';
                }
            }, __('Communication settings saved successfully'));
    }
    public function saveNotifications(): void
    {
        $this->persistSettings([], function (): void {
            // Use canonical key names with _enabled suffix as per config/settings.php
            $this->setSetting('notifications.low_stock_enabled', $this->notifications_low_stock, 'notifications', 'boolean');
            $this->setSetting('notifications.payment_due_enabled', $this->notifications_payment_due, 'notifications', 'boolean');
            $this->setSetting('notifications.new_order_enabled', $this->notifications_new_order, 'notifications', 'boolean');
        }, __('Notification settings saved successfully'));
    }

    public function restoreDefaults(string $group): mixed
    {
        $user = Auth::user();
        if (! $user || ! ($user->can('settings.update') || $user->can('settings.manage'))) {
            abort(403);
        }

        // Load defaults from config
        $defaults = config("settings.{$group}", []);

        // Support both structures:
        // 1) settings.<group> => [ key => [default=>...], ... ]
        // 2) settings.<group> => ['label'=>..., 'description'=>..., 'settings'=> [ key => [...], ... ]]
        if (isset($defaults['settings']) && is_array($defaults['settings'])) {
            $defaults = $defaults['settings'];
        }

        foreach ($defaults as $key => $config) {
            $fullKey = "{$group}.{$key}";
            SystemSetting::where('setting_key', $fullKey)->delete();
        }

        $this->clearSettingsCaches();
        $this->loadSettings();

        session()->flash('success', __('Settings restored to defaults for :group', ['group' => $group]));

        return $this->redirectToTab($this->activeTab);
    }

    public function render()
    {
        $currencies = \App\Models\Currency::active()->ordered()->get(['code', 'name', 'symbol']);

        // Navigation meta for Settings Search + categorized sidebar.
        $tabsMeta = [];
        foreach ($this->tabs as $tabKey => $tabLabel) {
            $tabsMeta[$tabKey] = [
                'key' => $tabKey,
                'label' => __($tabLabel),
                'description' => isset($this->tabDescriptions[$tabKey]) ? __($this->tabDescriptions[$tabKey]) : '',
                'icon' => $this->tabIcons[$tabKey] ?? null,
                'isAdvanced' => $this->isAdvancedTab($tabKey),
                'keywords' => $this->tabSearchKeywords[$tabKey] ?? '',
            ];
        }

        $tabGroups = $this->buildTabGroups();

        return view('livewire.admin.settings.unified-settings', [
            'currencies' => $currencies,
            'tabsMeta' => $tabsMeta,
            'tabGroups' => $tabGroups,
        ]);
    }
}
