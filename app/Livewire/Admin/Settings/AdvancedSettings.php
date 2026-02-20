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

    protected SettingsService $settingsService;

    protected SmsManager $smsManager;

    public function boot(SettingsService $settingsService, SmsManager $smsManager): void
    {
        $this->settingsService = $settingsService;
        $this->smsManager = $smsManager;
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
            'app_name' => $this->settingsService->get('app.name', config('app.name')),
            'app_logo' => $this->settingsService->get('app.logo', ''),
            'default_currency' => $this->settingsService->get('app.currency', 'EGP'),
            'default_locale' => $this->settingsService->get('app.locale', 'ar'),
            'timezone' => $this->settingsService->get('app.timezone', 'Africa/Cairo'),
        ];

        $this->sms = [
            'provider' => $this->settingsService->get('sms.provider', 'none'),
            '3shm' => [
                'enabled' => (bool) $this->settingsService->get('sms.3shm.enabled', false),
                'appkey' => $this->settingsService->getDecrypted('sms.3shm.appkey', ''),
                'authkey' => $this->settingsService->getDecrypted('sms.3shm.authkey', ''),
                'sandbox' => (bool) $this->settingsService->get('sms.3shm.sandbox', false),
            ],
            'smsmisr' => [
                'enabled' => (bool) $this->settingsService->get('sms.smsmisr.enabled', false),
                'username' => $this->settingsService->getDecrypted('sms.smsmisr.username', ''),
                'password' => $this->settingsService->getDecrypted('sms.smsmisr.password', ''),
                'sender_id' => $this->settingsService->get('sms.smsmisr.sender_id', ''),
                'sandbox' => (bool) $this->settingsService->get('sms.smsmisr.sandbox', false),
            ],
        ];

        $securityConfig = $this->settingsService->getSecurityConfig();
        $this->security = [
            '2fa_enabled' => $securityConfig['2fa_enabled'],
            '2fa_required' => $securityConfig['2fa_required'],
            'recaptcha_enabled' => $securityConfig['recaptcha_enabled'],
            'recaptcha_site_key' => $securityConfig['recaptcha_site_key'] ?? '',
            'recaptcha_secret_key' => $this->settingsService->getDecrypted('security.recaptcha_secret_key', ''),
            'max_sessions' => $securityConfig['max_sessions'],
            'session_lifetime' => $securityConfig['session_lifetime'],
            'password_expiry_days' => $securityConfig['password_expiry_days'],
        ];

        $backupConfig = $this->settingsService->getBackupConfig();
        $this->backup = [
            'enabled' => $backupConfig['enabled'],
            'frequency' => $backupConfig['frequency'],
            'time' => $backupConfig['time'],
            'retention_days' => $backupConfig['retention_days'],
            'include_uploads' => $backupConfig['include_uploads'],
        ];

        $this->notifications = [
            'low_stock_enabled' => (bool) $this->settingsService->get('notifications.low_stock_enabled', true),
            'low_stock_threshold' => (int) $this->settingsService->get('notifications.low_stock_threshold', 10),
            'rental_reminder_days' => (int) $this->settingsService->get('notifications.rental_reminder_days', 3),
            'late_payment_enabled' => (bool) $this->settingsService->get('notifications.late_payment_enabled', true),
            // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
            'late_penalty_percent' => decimal_float($this->settingsService->get('notifications.late_penalty_percent', 5)),
        ];

        $this->firebase = [
            'enabled' => (bool) $this->settingsService->get('firebase.enabled', false),
            'api_key' => $this->settingsService->getDecrypted('firebase.api_key', ''),
            'auth_domain' => $this->settingsService->get('firebase.auth_domain', ''),
            'project_id' => $this->settingsService->get('firebase.project_id', ''),
            'storage_bucket' => $this->settingsService->get('firebase.storage_bucket', ''),
            'messaging_sender_id' => $this->settingsService->get('firebase.messaging_sender_id', ''),
            'app_id' => $this->settingsService->get('firebase.app_id', ''),
            'vapid_key' => $this->settingsService->getDecrypted('firebase.vapid_key', ''),
        ];

        // Load performance settings
        $this->performance = [
            'cache_ttl' => (int) $this->settingsService->get('advanced.cache_ttl', 300),
            'pagination_default' => (string) $this->settingsService->get('advanced.pagination_default', '15'),
            'lazy_load_components' => (bool) $this->settingsService->get('advanced.lazy_load_components', true),
            'spa_navigation_enabled' => (bool) $this->settingsService->get('advanced.spa_navigation_enabled', true),
            'show_progress_bar' => (bool) $this->settingsService->get('advanced.show_progress_bar', true),
            'progress_bar_color' => (string) $this->settingsService->get('advanced.progress_bar_color', '#22c55e'),
            'max_payload_size' => (int) $this->settingsService->get('advanced.max_payload_size', 2048),
            'enable_query_logging' => (bool) $this->settingsService->get('advanced.enable_query_logging', false),
            'slow_query_threshold' => (int) $this->settingsService->get('advanced.slow_query_threshold', 100),
        ];

        // Load UI settings
        $this->ui = [
            'sidebar_collapsed' => (string) $this->settingsService->get('ui.sidebar_collapsed', 'auto'),
            'compact_tables' => (bool) $this->settingsService->get('ui.compact_tables', false),
            'show_breadcrumbs' => (bool) $this->settingsService->get('ui.show_breadcrumbs', true),
            'enable_keyboard_shortcuts' => (bool) $this->settingsService->get('ui.enable_keyboard_shortcuts', true),
            'toast_position' => (string) $this->settingsService->get('ui.toast_position', 'top-right'),
            'toast_duration' => (int) $this->settingsService->get('ui.toast_duration', 5),
            'auto_save_forms' => (bool) $this->settingsService->get('ui.auto_save_forms', true),
            'auto_save_interval' => (int) $this->settingsService->get('ui.auto_save_interval', 30),
        ];

        // Load export settings
        $this->export = [
            'default_format' => (string) $this->settingsService->get('export.default_format', 'xlsx'),
            'include_headers' => (bool) $this->settingsService->get('export.include_headers', true),
            'max_export_rows' => (int) $this->settingsService->get('export.max_export_rows', 10000),
            'chunk_size' => (int) $this->settingsService->get('export.chunk_size', 1000),
            'pdf_orientation' => (string) $this->settingsService->get('export.pdf_orientation', 'portrait'),
            'pdf_paper_size' => (string) $this->settingsService->get('export.pdf_paper_size', 'a4'),
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

        $this->settingsService->set('app.name', $this->general['app_name'], ['group' => 'app']);
        $this->settingsService->set('app.logo', $this->general['app_logo'], ['group' => 'app']);
        $this->settingsService->set('app.currency', $this->general['default_currency'], ['group' => 'app']);
        $this->settingsService->set('app.locale', $this->general['default_locale'], ['group' => 'app']);
        $this->settingsService->set('app.timezone', $this->general['timezone'], ['group' => 'app']);

        $this->dispatch('settings-saved');
        session()->flash('success', __('General settings saved successfully'));

        return $this->redirectToAdvanced();
    }

    public function saveSms(): mixed
    {
        $this->authorize('settings.update');

        $this->settingsService->set('sms.provider', $this->sms['provider'], ['group' => 'sms']);

        $this->settingsService->set('sms.3shm.enabled', $this->sms['3shm']['enabled'], ['group' => 'sms']);
        $this->settingsService->set('sms.3shm.appkey', $this->sms['3shm']['appkey'], ['group' => 'sms', 'is_encrypted' => true]);
        $this->settingsService->set('sms.3shm.authkey', $this->sms['3shm']['authkey'], ['group' => 'sms', 'is_encrypted' => true]);
        $this->settingsService->set('sms.3shm.sandbox', $this->sms['3shm']['sandbox'], ['group' => 'sms']);

        $this->settingsService->set('sms.smsmisr.enabled', $this->sms['smsmisr']['enabled'], ['group' => 'sms']);
        $this->settingsService->set('sms.smsmisr.username', $this->sms['smsmisr']['username'], ['group' => 'sms', 'is_encrypted' => true]);
        $this->settingsService->set('sms.smsmisr.password', $this->sms['smsmisr']['password'], ['group' => 'sms', 'is_encrypted' => true]);
        $this->settingsService->set('sms.smsmisr.sender_id', $this->sms['smsmisr']['sender_id'], ['group' => 'sms']);
        $this->settingsService->set('sms.smsmisr.sandbox', $this->sms['smsmisr']['sandbox'], ['group' => 'sms']);

        $this->dispatch('settings-saved');
        session()->flash('success', __('SMS settings saved successfully'));

        return $this->redirectToAdvanced();
    }

    public function testSms(): mixed
    {
        $result = $this->smsManager->testConnection($this->sms['provider']);

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

        $this->settingsService->set('security.2fa_enabled', $this->security['2fa_enabled'], ['group' => 'security']);
        $this->settingsService->set('security.2fa_required', $this->security['2fa_required'], ['group' => 'security']);
        $this->settingsService->set('security.recaptcha_enabled', $this->security['recaptcha_enabled'], ['group' => 'security']);
        $this->settingsService->set('security.recaptcha_site_key', $this->security['recaptcha_site_key'], ['group' => 'security']);
        $this->settingsService->set('security.recaptcha_secret_key', $this->security['recaptcha_secret_key'], ['group' => 'security', 'is_encrypted' => true]);
        $this->settingsService->set('security.max_sessions', $this->security['max_sessions'], ['group' => 'security']);
        $this->settingsService->set('security.session_lifetime', $this->security['session_lifetime'], ['group' => 'security']);
        $this->settingsService->set('security.password_expiry_days', $this->security['password_expiry_days'], ['group' => 'security']);

        $this->dispatch('settings-saved');
        session()->flash('success', __('Security settings saved successfully'));

        return $this->redirectToAdvanced();
    }

    public function saveBackup(): mixed
    {
        $this->authorize('settings.update');

        $this->settingsService->set('backup.enabled', $this->backup['enabled'], ['group' => 'backup']);
        $this->settingsService->set('backup.frequency', $this->backup['frequency'], ['group' => 'backup']);
        $this->settingsService->set('backup.time', $this->backup['time'], ['group' => 'backup']);
        $this->settingsService->set('backup.retention_days', $this->backup['retention_days'], ['group' => 'backup']);
        $this->settingsService->set('backup.include_uploads', $this->backup['include_uploads'], ['group' => 'backup']);

        $this->dispatch('settings-saved');
        session()->flash('success', __('Backup settings saved successfully'));

        return $this->redirectToAdvanced();
    }

    public function saveNotifications(): mixed
    {
        $this->authorize('settings.update');

        $this->settingsService->set('notifications.low_stock_enabled', $this->notifications['low_stock_enabled'], ['group' => 'notifications']);
        $this->settingsService->set('notifications.low_stock_threshold', $this->notifications['low_stock_threshold'], ['group' => 'notifications']);
        $this->settingsService->set('notifications.rental_reminder_days', $this->notifications['rental_reminder_days'], ['group' => 'notifications']);
        $this->settingsService->set('notifications.late_payment_enabled', $this->notifications['late_payment_enabled'], ['group' => 'notifications']);
        $this->settingsService->set('notifications.late_penalty_percent', $this->notifications['late_penalty_percent'], ['group' => 'notifications']);

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

        $this->settingsService->set('firebase.enabled', $this->firebase['enabled'], ['group' => 'firebase']);
        $this->settingsService->set('firebase.api_key', $this->firebase['api_key'], ['group' => 'firebase', 'is_encrypted' => true]);
        $this->settingsService->set('firebase.auth_domain', $this->firebase['auth_domain'], ['group' => 'firebase']);
        $this->settingsService->set('firebase.project_id', $this->firebase['project_id'], ['group' => 'firebase']);
        $this->settingsService->set('firebase.storage_bucket', $this->firebase['storage_bucket'], ['group' => 'firebase']);
        $this->settingsService->set('firebase.messaging_sender_id', $this->firebase['messaging_sender_id'], ['group' => 'firebase']);
        $this->settingsService->set('firebase.app_id', $this->firebase['app_id'], ['group' => 'firebase']);
        $this->settingsService->set('firebase.vapid_key', $this->firebase['vapid_key'], ['group' => 'firebase', 'is_encrypted' => true]);

        $this->dispatch('settings-saved');
        session()->flash('success', __('Firebase settings saved successfully'));

        return $this->redirectToAdvanced();
    }

    public function savePerformance(): mixed
    {
        $this->authorize('settings.update');

        $this->settingsService->set('advanced.cache_ttl', $this->performance['cache_ttl'], ['group' => 'advanced']);
        $this->settingsService->set('advanced.pagination_default', $this->performance['pagination_default'], ['group' => 'advanced']);
        $this->settingsService->set('advanced.lazy_load_components', $this->performance['lazy_load_components'], ['group' => 'advanced']);
        $this->settingsService->set('advanced.spa_navigation_enabled', $this->performance['spa_navigation_enabled'], ['group' => 'advanced']);
        $this->settingsService->set('advanced.show_progress_bar', $this->performance['show_progress_bar'], ['group' => 'advanced']);
        $this->settingsService->set('advanced.progress_bar_color', $this->performance['progress_bar_color'], ['group' => 'advanced']);
        $this->settingsService->set('advanced.max_payload_size', $this->performance['max_payload_size'], ['group' => 'advanced']);
        $this->settingsService->set('advanced.enable_query_logging', $this->performance['enable_query_logging'], ['group' => 'advanced']);
        $this->settingsService->set('advanced.slow_query_threshold', $this->performance['slow_query_threshold'], ['group' => 'advanced']);

        $this->dispatch('settings-saved');
        session()->flash('success', __('Performance settings saved successfully'));

        return $this->redirectToAdvanced();
    }

    public function saveUi(): mixed
    {
        $this->authorize('settings.update');

        $this->settingsService->set('ui.sidebar_collapsed', $this->ui['sidebar_collapsed'], ['group' => 'ui']);
        $this->settingsService->set('ui.compact_tables', $this->ui['compact_tables'], ['group' => 'ui']);
        $this->settingsService->set('ui.show_breadcrumbs', $this->ui['show_breadcrumbs'], ['group' => 'ui']);
        $this->settingsService->set('ui.enable_keyboard_shortcuts', $this->ui['enable_keyboard_shortcuts'], ['group' => 'ui']);
        $this->settingsService->set('ui.toast_position', $this->ui['toast_position'], ['group' => 'ui']);
        $this->settingsService->set('ui.toast_duration', $this->ui['toast_duration'], ['group' => 'ui']);
        $this->settingsService->set('ui.auto_save_forms', $this->ui['auto_save_forms'], ['group' => 'ui']);
        $this->settingsService->set('ui.auto_save_interval', $this->ui['auto_save_interval'], ['group' => 'ui']);

        $this->dispatch('settings-saved');
        session()->flash('success', __('UI settings saved successfully'));

        return $this->redirectToAdvanced();
    }

    public function saveExport(): mixed
    {
        $this->authorize('settings.update');

        $this->settingsService->set('export.default_format', $this->export['default_format'], ['group' => 'export']);
        $this->settingsService->set('export.include_headers', $this->export['include_headers'], ['group' => 'export']);
        $this->settingsService->set('export.max_export_rows', $this->export['max_export_rows'], ['group' => 'export']);
        $this->settingsService->set('export.chunk_size', $this->export['chunk_size'], ['group' => 'export']);
        $this->settingsService->set('export.pdf_orientation', $this->export['pdf_orientation'], ['group' => 'export']);
        $this->settingsService->set('export.pdf_paper_size', $this->export['pdf_paper_size'], ['group' => 'export']);

        $this->dispatch('settings-saved');
        session()->flash('success', __('Export settings saved successfully'));

        return $this->redirectToAdvanced();
    }

    public function getSmsProvidersProperty(): array
    {
        return $this->smsManager->getAvailableProviders();
    }

    public function render()
    {
        return view('livewire.admin.settings.advanced-settings')
            ->layout('layouts.app');
    }
}
