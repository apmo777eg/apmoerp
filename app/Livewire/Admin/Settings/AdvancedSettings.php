<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Settings;

use App\Services\SettingsService;
use App\Services\Sms\SmsManager;
use Illuminate\Support\Facades\Auth;
use App\Livewire\BaseComponent as Component;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class AdvancedSettings extends Component
{
    use AuthorizesRequests;
    public string $activeTab = 'general';

    public array $general = [
        'app_name' => '',
        'app_logo' => '',
        'default_currency' => 'EGP',
        'default_locale' => 'ar',
        'timezone' => 'Africa/Cairo',
    ];

    public array $sms = [
        'provider' => 'none',
        '3shm' => [
            'enabled' => false,
            'appkey' => '',
            'authkey' => '',
            'sandbox' => false,
        ],
        'smsmisr' => [
            'enabled' => false,
            'username' => '',
            'password' => '',
            'sender_id' => '',
            'sandbox' => false,
        ],
    ];

    public array $security = [
        '2fa_enabled' => false,
        '2fa_required' => false,
        'recaptcha_enabled' => false,
        'recaptcha_site_key' => '',
        'recaptcha_secret_key' => '',
        'max_sessions' => 3,
        'session_lifetime' => 480,
        'password_expiry_days' => 0,
    ];

    public array $backup = [
        'enabled' => false,
        'frequency' => 'daily',
        'time' => '02:00',
        'retention_days' => 7,
        'include_uploads' => true,
    ];

    public array $notifications = [
        'low_stock_enabled' => true,
        'low_stock_threshold' => 10,
        'rental_reminder_days' => 3,
        'late_payment_enabled' => true,
        'late_penalty_percent' => 5,
    ];

    public array $firebase = [
        'enabled' => false,
        'api_key' => '',
        'auth_domain' => '',
        'project_id' => '',
        'storage_bucket' => '',
        'messaging_sender_id' => '',
        'app_id' => '',
        'vapid_key' => '',
    ];

    public array $performance = [
        'cache_ttl' => 300,
        'pagination_default' => '15',
        'lazy_load_components' => true,
        'spa_navigation_enabled' => true,
        'show_progress_bar' => true,
        'progress_bar_color' => '#22c55e',
        'max_payload_size' => 2048,
        'enable_query_logging' => false,
        'slow_query_threshold' => 100,
    ];

    public array $ui = [
        'sidebar_collapsed' => 'auto',
        'compact_tables' => false,
        'show_breadcrumbs' => true,
        'enable_keyboard_shortcuts' => true,
        'toast_position' => 'top-right',
        'toast_duration' => 5,
        'auto_save_forms' => true,
        'auto_save_interval' => 30,
    ];

    public array $export = [
        'default_format' => 'xlsx',
        'include_headers' => true,
        'max_export_rows' => 10000,
        'chunk_size' => 1000,
        'pdf_orientation' => 'portrait',
        'pdf_paper_size' => 'a4',
    ];

    protected ?SettingsService $settingsService = null;

    protected ?SmsManager $smsManager = null;

    public function boot(SettingsService $settingsService, SmsManager $smsManager): void
    {
        $this->settingsService = $settingsService;
        $this->smsManager = $smsManager;
    }

    

/**
 * Safety nets: these protected service properties aren't persisted by Livewire between requests.
 * If they aren't injected for any reason, lazily resolve them from the container.
 */
protected function settings(): SettingsService
{
    return $this->settingsService ??= app(SettingsService::class);
}

protected function sms(): SmsManager
{
    return $this->smsManager ??= app(SmsManager::class);
}

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
        $this->general = [
            'app_name' => $this->settings()->get('app.name', config('app.name')),
            'app_logo' => $this->settings()->get('app.logo', ''),
            'default_currency' => $this->settings()->get('app.currency', 'EGP'),
            'default_locale' => $this->settings()->get('app.locale', 'ar'),
            'timezone' => $this->settings()->get('app.timezone', 'Africa/Cairo'),
        ];

        $this->sms = [
            'provider' => $this->settings()->get('sms.provider', 'none'),
            '3shm' => [
                'enabled' => (bool) $this->settings()->get('sms.3shm.enabled', false),
                'appkey' => $this->settings()->getDecrypted('sms.3shm.appkey', ''),
                'authkey' => $this->settings()->getDecrypted('sms.3shm.authkey', ''),
                'sandbox' => (bool) $this->settings()->get('sms.3shm.sandbox', false),
            ],
            'smsmisr' => [
                'enabled' => (bool) $this->settings()->get('sms.smsmisr.enabled', false),
                'username' => $this->settings()->getDecrypted('sms.smsmisr.username', ''),
                'password' => $this->settings()->getDecrypted('sms.smsmisr.password', ''),
                'sender_id' => $this->settings()->get('sms.smsmisr.sender_id', ''),
                'sandbox' => (bool) $this->settings()->get('sms.smsmisr.sandbox', false),
            ],
        ];

        $securityConfig = $this->settings()->getSecurityConfig();
        $this->security = [
            '2fa_enabled' => $securityConfig['2fa_enabled'],
            '2fa_required' => $securityConfig['2fa_required'],
            'recaptcha_enabled' => $securityConfig['recaptcha_enabled'],
            'recaptcha_site_key' => $securityConfig['recaptcha_site_key'] ?? '',
            'recaptcha_secret_key' => $this->settings()->getDecrypted('security.recaptcha_secret_key', ''),
            'max_sessions' => $securityConfig['max_sessions'],
            'session_lifetime' => $securityConfig['session_lifetime'],
            'password_expiry_days' => $securityConfig['password_expiry_days'],
        ];

        $backupConfig = $this->settings()->getBackupConfig();
        $this->backup = [
            'enabled' => $backupConfig['enabled'],
            'frequency' => $backupConfig['frequency'],
            'time' => $backupConfig['time'],
            'retention_days' => $backupConfig['retention_days'],
            'include_uploads' => $backupConfig['include_uploads'],
        ];

        $this->notifications = [
            'low_stock_enabled' => (bool) $this->settings()->get('notifications.low_stock_enabled', true),
            'low_stock_threshold' => (int) $this->settings()->get('notifications.low_stock_threshold', 10),
            'rental_reminder_days' => (int) $this->settings()->get('notifications.rental_reminder_days', 3),
            'late_payment_enabled' => (bool) $this->settings()->get('notifications.late_payment_enabled', true),
            // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
            'late_penalty_percent' => decimal_float($this->settings()->get('notifications.late_penalty_percent', 5)),
        ];

        $this->firebase = [
            'enabled' => (bool) $this->settings()->get('firebase.enabled', false),
            'api_key' => $this->settings()->getDecrypted('firebase.api_key', ''),
            'auth_domain' => $this->settings()->get('firebase.auth_domain', ''),
            'project_id' => $this->settings()->get('firebase.project_id', ''),
            'storage_bucket' => $this->settings()->get('firebase.storage_bucket', ''),
            'messaging_sender_id' => $this->settings()->get('firebase.messaging_sender_id', ''),
            'app_id' => $this->settings()->get('firebase.app_id', ''),
            'vapid_key' => $this->settings()->getDecrypted('firebase.vapid_key', ''),
        ];

        // Load performance settings
        $this->performance = [
            'cache_ttl' => (int) $this->settings()->get('advanced.cache_ttl', 300),
            'pagination_default' => (string) $this->settings()->get('advanced.pagination_default', '15'),
            'lazy_load_components' => (bool) $this->settings()->get('advanced.lazy_load_components', true),
            'spa_navigation_enabled' => (bool) $this->settings()->get('advanced.spa_navigation_enabled', true),
            'show_progress_bar' => (bool) $this->settings()->get('advanced.show_progress_bar', true),
            'progress_bar_color' => (string) $this->settings()->get('advanced.progress_bar_color', '#22c55e'),
            'max_payload_size' => (int) $this->settings()->get('advanced.max_payload_size', 2048),
            'enable_query_logging' => (bool) $this->settings()->get('advanced.enable_query_logging', false),
            'slow_query_threshold' => (int) $this->settings()->get('advanced.slow_query_threshold', 100),
        ];

        // Load UI settings
        $this->ui = [
            'sidebar_collapsed' => (string) $this->settings()->get('ui.sidebar_collapsed', 'auto'),
            'compact_tables' => (bool) $this->settings()->get('ui.compact_tables', false),
            'show_breadcrumbs' => (bool) $this->settings()->get('ui.show_breadcrumbs', true),
            'enable_keyboard_shortcuts' => (bool) $this->settings()->get('ui.enable_keyboard_shortcuts', true),
            'toast_position' => (string) $this->settings()->get('ui.toast_position', 'top-right'),
            'toast_duration' => (int) $this->settings()->get('ui.toast_duration', 5),
            'auto_save_forms' => (bool) $this->settings()->get('ui.auto_save_forms', true),
            'auto_save_interval' => (int) $this->settings()->get('ui.auto_save_interval', 30),
        ];

        // Load export settings
        $this->export = [
            'default_format' => (string) $this->settings()->get('export.default_format', 'xlsx'),
            'include_headers' => (bool) $this->settings()->get('export.include_headers', true),
            'max_export_rows' => (int) $this->settings()->get('export.max_export_rows', 10000),
            'chunk_size' => (int) $this->settings()->get('export.chunk_size', 1000),
            'pdf_orientation' => (string) $this->settings()->get('export.pdf_orientation', 'portrait'),
            'pdf_paper_size' => (string) $this->settings()->get('export.pdf_paper_size', 'a4'),
        ];
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    protected function redirectToAdvanced(): mixed
    {
        $this->redirectRoute('admin.settings', ['tab' => 'advanced'], navigate: true);
    }

    public function saveGeneral(): mixed
    {
        $this->authorize('settings.update');

        $this->settings()->set('app.name', $this->general['app_name'], ['group' => 'app']);
        $this->settings()->set('app.logo', $this->general['app_logo'], ['group' => 'app']);
        $this->settings()->set('app.currency', $this->general['default_currency'], ['group' => 'app']);
        $this->settings()->set('app.locale', $this->general['default_locale'], ['group' => 'app']);
        $this->settings()->set('app.timezone', $this->general['timezone'], ['group' => 'app']);

        $this->dispatch('settings-saved');
        session()->flash('success', __('General settings saved successfully'));

        return $this->redirectToAdvanced();
    }

    public function saveSms(): mixed
    {
        $this->authorize('settings.update');

        $this->settings()->set('sms.provider', $this->sms['provider'], ['group' => 'sms']);

        $this->settings()->set('sms.3shm.enabled', $this->sms['3shm']['enabled'], ['group' => 'sms']);
        $this->settings()->set('sms.3shm.appkey', $this->sms['3shm']['appkey'], ['group' => 'sms', 'is_encrypted' => true]);
        $this->settings()->set('sms.3shm.authkey', $this->sms['3shm']['authkey'], ['group' => 'sms', 'is_encrypted' => true]);
        $this->settings()->set('sms.3shm.sandbox', $this->sms['3shm']['sandbox'], ['group' => 'sms']);

        $this->settings()->set('sms.smsmisr.enabled', $this->sms['smsmisr']['enabled'], ['group' => 'sms']);
        $this->settings()->set('sms.smsmisr.username', $this->sms['smsmisr']['username'], ['group' => 'sms', 'is_encrypted' => true]);
        $this->settings()->set('sms.smsmisr.password', $this->sms['smsmisr']['password'], ['group' => 'sms', 'is_encrypted' => true]);
        $this->settings()->set('sms.smsmisr.sender_id', $this->sms['smsmisr']['sender_id'], ['group' => 'sms']);
        $this->settings()->set('sms.smsmisr.sandbox', $this->sms['smsmisr']['sandbox'], ['group' => 'sms']);

        $this->dispatch('settings-saved');
        session()->flash('success', __('SMS settings saved successfully'));

        return $this->redirectToAdvanced();
    }

    public function testSms(): mixed
    {
        $result = $this->sms()->testConnection($this->sms['provider']);

        if ($result['success']) {
            session()->flash('success', __('SMS configuration is valid'));
        } else {
            session()->flash('error', $result['error'] ?? __('SMS configuration test failed'));
        }

        return $this->redirectToAdvanced();
    }

    public function saveSecurity(): mixed
    {
        $this->authorize('settings.update');

        if ($this->security['recaptcha_enabled']) {
            if (empty($this->security['recaptcha_site_key']) || empty($this->security['recaptcha_secret_key'])) {
                session()->flash('error', __('reCAPTCHA requires both site key and secret key to be configured'));

                return $this->redirectToAdvanced();
            }
        }

        if ($this->security['2fa_required'] && ! $this->security['2fa_enabled']) {
            session()->flash('error', __('Two-factor authentication must be enabled before making it required'));

            return $this->redirectToAdvanced();
        }

        $this->settings()->set('security.2fa_enabled', $this->security['2fa_enabled'], ['group' => 'security']);
        $this->settings()->set('security.2fa_required', $this->security['2fa_required'], ['group' => 'security']);
        $this->settings()->set('security.recaptcha_enabled', $this->security['recaptcha_enabled'], ['group' => 'security']);
        $this->settings()->set('security.recaptcha_site_key', $this->security['recaptcha_site_key'], ['group' => 'security']);
        $this->settings()->set('security.recaptcha_secret_key', $this->security['recaptcha_secret_key'], ['group' => 'security', 'is_encrypted' => true]);
        $this->settings()->set('security.max_sessions', $this->security['max_sessions'], ['group' => 'security']);
        $this->settings()->set('security.session_lifetime', $this->security['session_lifetime'], ['group' => 'security']);
        $this->settings()->set('security.password_expiry_days', $this->security['password_expiry_days'], ['group' => 'security']);

        $this->dispatch('settings-saved');
        session()->flash('success', __('Security settings saved successfully'));

        return $this->redirectToAdvanced();
    }

    public function saveBackup(): mixed
    {
        $this->authorize('settings.update');

        $this->settings()->set('backup.enabled', $this->backup['enabled'], ['group' => 'backup']);
        $this->settings()->set('backup.frequency', $this->backup['frequency'], ['group' => 'backup']);
        $this->settings()->set('backup.time', $this->backup['time'], ['group' => 'backup']);
        $this->settings()->set('backup.retention_days', $this->backup['retention_days'], ['group' => 'backup']);
        $this->settings()->set('backup.include_uploads', $this->backup['include_uploads'], ['group' => 'backup']);

        $this->dispatch('settings-saved');
        session()->flash('success', __('Backup settings saved successfully'));

        return $this->redirectToAdvanced();
    }

    public function saveNotifications(): mixed
    {
        $this->authorize('settings.update');

        $this->settings()->set('notifications.low_stock_enabled', $this->notifications['low_stock_enabled'], ['group' => 'notifications']);
        $this->settings()->set('notifications.low_stock_threshold', $this->notifications['low_stock_threshold'], ['group' => 'notifications']);
        $this->settings()->set('notifications.rental_reminder_days', $this->notifications['rental_reminder_days'], ['group' => 'notifications']);
        $this->settings()->set('notifications.late_payment_enabled', $this->notifications['late_payment_enabled'], ['group' => 'notifications']);
        $this->settings()->set('notifications.late_penalty_percent', $this->notifications['late_penalty_percent'], ['group' => 'notifications']);

        $this->dispatch('settings-saved');
        session()->flash('success', __('Notification settings saved successfully'));

        return $this->redirectToAdvanced();
    }

    public function saveFirebase(): mixed
    {
        $this->authorize('settings.update');

        if ($this->firebase['enabled']) {
            if (empty($this->firebase['api_key']) || empty($this->firebase['project_id'])) {
                session()->flash('error', __('Firebase requires at least API Key and Project ID'));

                return $this->redirectToAdvanced();
            }
        }

        $this->settings()->set('firebase.enabled', $this->firebase['enabled'], ['group' => 'firebase']);
        $this->settings()->set('firebase.api_key', $this->firebase['api_key'], ['group' => 'firebase', 'is_encrypted' => true]);
        $this->settings()->set('firebase.auth_domain', $this->firebase['auth_domain'], ['group' => 'firebase']);
        $this->settings()->set('firebase.project_id', $this->firebase['project_id'], ['group' => 'firebase']);
        $this->settings()->set('firebase.storage_bucket', $this->firebase['storage_bucket'], ['group' => 'firebase']);
        $this->settings()->set('firebase.messaging_sender_id', $this->firebase['messaging_sender_id'], ['group' => 'firebase']);
        $this->settings()->set('firebase.app_id', $this->firebase['app_id'], ['group' => 'firebase']);
        $this->settings()->set('firebase.vapid_key', $this->firebase['vapid_key'], ['group' => 'firebase', 'is_encrypted' => true]);

        $this->dispatch('settings-saved');
        session()->flash('success', __('Firebase settings saved successfully'));

        return $this->redirectToAdvanced();
    }

    public function savePerformance(): mixed
    {
        $this->authorize('settings.update');

        $this->settings()->set('advanced.cache_ttl', $this->performance['cache_ttl'], ['group' => 'advanced']);
        $this->settings()->set('advanced.pagination_default', $this->performance['pagination_default'], ['group' => 'advanced']);
        $this->settings()->set('advanced.lazy_load_components', $this->performance['lazy_load_components'], ['group' => 'advanced']);
        $this->settings()->set('advanced.spa_navigation_enabled', $this->performance['spa_navigation_enabled'], ['group' => 'advanced']);
        $this->settings()->set('advanced.show_progress_bar', $this->performance['show_progress_bar'], ['group' => 'advanced']);
        $this->settings()->set('advanced.progress_bar_color', $this->performance['progress_bar_color'], ['group' => 'advanced']);
        $this->settings()->set('advanced.max_payload_size', $this->performance['max_payload_size'], ['group' => 'advanced']);
        $this->settings()->set('advanced.enable_query_logging', $this->performance['enable_query_logging'], ['group' => 'advanced']);
        $this->settings()->set('advanced.slow_query_threshold', $this->performance['slow_query_threshold'], ['group' => 'advanced']);

        $this->dispatch('settings-saved');
        session()->flash('success', __('Performance settings saved successfully'));

        return $this->redirectToAdvanced();
    }

    public function saveUi(): mixed
    {
        $this->authorize('settings.update');

        $this->settings()->set('ui.sidebar_collapsed', $this->ui['sidebar_collapsed'], ['group' => 'ui']);
        $this->settings()->set('ui.compact_tables', $this->ui['compact_tables'], ['group' => 'ui']);
        $this->settings()->set('ui.show_breadcrumbs', $this->ui['show_breadcrumbs'], ['group' => 'ui']);
        $this->settings()->set('ui.enable_keyboard_shortcuts', $this->ui['enable_keyboard_shortcuts'], ['group' => 'ui']);
        $this->settings()->set('ui.toast_position', $this->ui['toast_position'], ['group' => 'ui']);
        $this->settings()->set('ui.toast_duration', $this->ui['toast_duration'], ['group' => 'ui']);
        $this->settings()->set('ui.auto_save_forms', $this->ui['auto_save_forms'], ['group' => 'ui']);
        $this->settings()->set('ui.auto_save_interval', $this->ui['auto_save_interval'], ['group' => 'ui']);

        $this->dispatch('settings-saved');
        session()->flash('success', __('UI settings saved successfully'));

        return $this->redirectToAdvanced();
    }

    public function saveExport(): mixed
    {
        $this->authorize('settings.update');

        $this->settings()->set('export.default_format', $this->export['default_format'], ['group' => 'export']);
        $this->settings()->set('export.include_headers', $this->export['include_headers'], ['group' => 'export']);
        $this->settings()->set('export.max_export_rows', $this->export['max_export_rows'], ['group' => 'export']);
        $this->settings()->set('export.chunk_size', $this->export['chunk_size'], ['group' => 'export']);
        $this->settings()->set('export.pdf_orientation', $this->export['pdf_orientation'], ['group' => 'export']);
        $this->settings()->set('export.pdf_paper_size', $this->export['pdf_paper_size'], ['group' => 'export']);

        $this->dispatch('settings-saved');
        session()->flash('success', __('Export settings saved successfully'));

        return $this->redirectToAdvanced();
    }

    public function getSmsProvidersProperty(): array
    {
        return $this->sms()->getAvailableProviders();
    }

    public function render()
    {
        return view('livewire.admin.settings.advanced-settings')
            ->layout('layouts.app');
    }
}
