<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\SettingsService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

/**
 * Apply selected DB-backed settings to runtime configuration.
 *
 * This provider intentionally only maps a small set of safe configuration values
 * (e.g., mail SMTP config). It avoids heavy queries by relying on SettingsService caching.
 */
class SettingsConfigServiceProvider extends ServiceProvider
{
    public function boot(SettingsService $settings): void
    {
        // During early install or when the database isn't available, skip safely.
        try {
            if (! Schema::hasTable('system_settings')) {
                return;
            }
        } catch (\Throwable $e) {
            return;
        }

        $this->applyMailConfig($settings);
    }

    protected function applyMailConfig(SettingsService $settings): void
    {
        $host = (string) $settings->get('mail.smtp_host', '');
        $port = (int) $settings->get('mail.smtp_port', (int) config('mail.mailers.smtp.port', 587));
        $username = (string) $settings->get('mail.smtp_username', '');
        $password = $settings->get('mail.smtp_password'); // decrypted automatically when encrypted
        $encryption = $settings->get('mail.smtp_encryption', config('mail.mailers.smtp.encryption'));

        if (is_string($encryption) && strtolower($encryption) === 'none') {
            $encryption = null;
        }

        $fromAddress = (string) $settings->get('mail.from_address', (string) config('mail.from.address', ''));
        $fromName = (string) $settings->get('mail.from_name', (string) config('mail.from.name', config('app.name', 'App')));

        // Apply only if SMTP host is configured. Otherwise keep environment config.
        if ($host !== '') {
            config([
                'mail.default' => (string) ($settings->get('mail.default_mailer', config('mail.default', 'smtp')) ?: 'smtp'),

                'mail.mailers.smtp.host' => $host,
                'mail.mailers.smtp.port' => $port,
                'mail.mailers.smtp.username' => $username !== '' ? $username : null,
                'mail.mailers.smtp.password' => is_string($password) && $password !== '' ? $password : null,
                'mail.mailers.smtp.encryption' => $encryption,

                'mail.from.address' => $fromAddress !== '' ? $fromAddress : config('mail.from.address'),
                'mail.from.name' => $fromName !== '' ? $fromName : config('mail.from.name'),
            ]);
        }
    }
}
