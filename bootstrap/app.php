<?php

use App\Console\Commands\ClosePosDay;
use App\Console\Commands\SendScheduledReports;
use App\Console\Commands\SystemDiagnostics;
use App\Models\Branch;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            // Route parameter patterns (moved from RouteServiceProvider for Laravel 11/12 compatibility)
            Route::pattern('id', '[0-9]+');

            // Model binding for {branch} parameter
            Route::bind('branch', function ($value) {
                return Branch::query()->findOrFail($value);
            });
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Security: Trusting all proxies ('*') can allow IP spoofing.
        // In production, configure specific proxy IPs via APP_TRUSTED_PROXIES env var
        $trustedProxies = env('APP_TRUSTED_PROXIES');

        // Parse comma-separated proxy list into array (e.g., "ip1,ip2" -> ["ip1", "ip2"])
        if ($trustedProxies !== null && $trustedProxies !== '*') {
            $trustedProxies = array_filter(
                array_map('trim', explode(',', $trustedProxies)),
                fn ($ip) => $ip !== ''
            );
            // If parsing resulted in empty array, set to null
            $trustedProxies = ! empty($trustedProxies) ? $trustedProxies : null;
        }

        if ($trustedProxies === '*' && app()->environment('production')) {
            logger()->warning('Trusting all proxies (*) in production is a security risk. Set APP_TRUSTED_PROXIES to specific IPs.');
        }

        $middleware->trustProxies(at: $trustedProxies);

        // Enable Sanctum SPA (cookie/session) auth for same-origin API calls (e.g. POS)
        $middleware->api(prepend: [
            EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\RequestId::class,
            \App\Http\Middleware\CorrelationId::class,
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\AutoLogout::class,
            \App\Http\Middleware\ModuleContext::class,
            \App\Http\Middleware\SetUserBranchContext::class,
            \App\Http\Middleware\ClearBranchContext::class,
        ]);

        $middleware->group('api-core', [
            \App\Http\Middleware\EnsureApiEnabled::class,
            \App\Http\Middleware\ForceJsonResponse::class,
            \App\Http\Middleware\ValidateJson::class,
            \App\Http\Middleware\RequestId::class,
            \App\Http\Middleware\CorrelationId::class,
            \App\Http\Middleware\RequestLogger::class,
            \App\Http\Middleware\ServerTiming::class,
            \App\Http\Middleware\SentryContext::class,
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\ETag::class,
            \App\Http\Middleware\PaginationSanitizer::class,
            // V21-MEDIUM-09 Fix: Add ClearBranchContext to API to prevent data leakage
            // In long-running servers (Octane/Swoole/RoadRunner), BranchContextManager
            // may retain branch context from previous requests without this cleanup
            \App\Http\Middleware\ClearBranchContext::class,
        ]);

        // CRIT-AUTH-01 FIX: `AssignGuard` expects an auth *guard name* (as defined in config/auth.php).
        // This project defines the Sanctum guard as `api` (driver: sanctum).
        // Using an undefined guard name (e.g. `sanctum`) causes 500 errors on every API call
        // due to "Auth guard [sanctum] is not defined.".
        $middleware->group('api-auth', [
            \App\Http\Middleware\AssignGuard::class.':api',
            \App\Http\Middleware\EnsureBranchAccess::class,
            \App\Http\Middleware\Authenticate::class,
            \App\Http\Middleware\Require2FA::class,
        ]);

        // CRIT-AUTH-01 FIX: Same guard issue as `api-auth` group.
        $middleware->group('pos-protected', [
            \App\Http\Middleware\ForceJsonResponse::class,
            \App\Http\Middleware\ValidateJson::class,
            \App\Http\Middleware\RequestId::class,
            \App\Http\Middleware\CorrelationId::class,
            \App\Http\Middleware\RequestLogger::class,
            \App\Http\Middleware\ServerTiming::class,
            \App\Http\Middleware\SentryContext::class,
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\ETag::class,
            \App\Http\Middleware\PaginationSanitizer::class,
            \App\Http\Middleware\AssignGuard::class.':api',
            \App\Http\Middleware\EnsureBranchAccess::class,
            \App\Http\Middleware\Authenticate::class,
            \App\Http\Middleware\VerifyPosOpen::class,
        ]);

        $middleware->alias([
            'impersonate' => \App\Http\Middleware\Impersonate::class,
            'api-branch' => \App\Http\Middleware\SetBranchContext::class,
            'module' => \App\Http\Middleware\SetModuleContext::class,
            'module.enabled' => \App\Http\Middleware\EnsureModuleEnabled::class,
            'perm' => \App\Http\Middleware\EnsurePermission::class,
            'permission' => \App\Http\Middleware\EnsurePermission::class,
            'assign.guard' => \App\Http\Middleware\AssignGuard::class,
            'store.token' => \App\Http\Middleware\AuthenticateStoreToken::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'auth' => \App\Http\Middleware\Authenticate::class,
            'guest' => \Illuminate\Auth\Middleware\RedirectIfAuthenticated::class,
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
            '2fa' => \App\Http\Middleware\Require2FA::class,
            'track.session' => \App\Http\Middleware\TrackUserSession::class,
            'recaptcha' => \App\Http\Middleware\ValidateRecaptcha::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withCommands([
        SendScheduledReports::class,
        ClosePosDay::class,
        SystemDiagnostics::class,
    ])
    // Note: Scheduling is defined in routes/console.php using Schedule facade
    // See docs/SCHEDULER_SETUP.md for cron configuration
    ->create();
