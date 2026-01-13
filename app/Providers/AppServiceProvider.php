<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\BranchModule;
use App\Models\Module;
use App\Models\ModuleNavigation;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Observers\BranchModuleObserver;
use App\Observers\FinancialTransactionObserver;
use App\Observers\ModuleNavigationObserver;
use App\Observers\ModuleObserver;
use App\Observers\PriceAuditObserver;
use App\Observers\ProductObserver;
use App\Services\Contracts\ModuleFieldServiceInterface;
use App\Services\Contracts\ProductServiceInterface;
use App\Services\ModuleFieldService;
// Models & observers
use App\Services\ProductService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
// Services
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProductServiceInterface::class, ProductService::class);
        $this->app->bind(ModuleFieldServiceInterface::class, ModuleFieldService::class);
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);
        JsonResource::withoutWrapping();

        if (config('app.force_https')) {
            URL::forceScheme('https');
        }

        if (app()->environment('local')) {
            Model::shouldBeStrict();
            Model::preventSilentlyDiscardingAttributes();
            Model::preventAccessingMissingAttributes();
            Model::preventLazyLoading();
        }

        // Configurable query logging for non-production environments
        if (config('database.query_log.enabled')) {
            DB::listen(function ($query) {
                $threshold = config('database.query_log.slow_threshold', 1000);
                if ($query->time >= $threshold) {
                    Log::warning('Slow query detected', [
                        'sql' => $query->sql,
                        'bindings' => $query->bindings,
                        'time' => $query->time,
                    ]);
                }
            });
        }

        // Observers
        Product::observe(ProductObserver::class);
        Product::observe(PriceAuditObserver::class); // Critical ERP: Price audit trail

        // Module cache invalidation observers
        Module::observe(ModuleObserver::class);
        BranchModule::observe(BranchModuleObserver::class);
        ModuleNavigation::observe(ModuleNavigationObserver::class);

        // V7-MEDIUM-N09 FIX: Register FinancialTransactionObserver for Sale and Purchase models
        // This enables auto-updating customer/supplier balances and payment status tracking
        Sale::observe(FinancialTransactionObserver::class);
        Purchase::observe(FinancialTransactionObserver::class);

        // Configure rate limiting (moved from RouteServiceProvider for Laravel 11/12 compatibility)
        $this->configureRateLimiting();
    }

    /**
     * Configure rate limiting for different contexts.
     * Moved from RouteServiceProvider for Laravel 11/12 compatibility.
     */
    protected function configureRateLimiting(): void
    {
        // General API rate limiting - 120 requests per minute
        RateLimiter::for('api', function (Request $request) {
            $key = optional($request->user())->getKey() ?: $request->ip();

            return Limit::perMinute(120)->by($key);
        });

        // Authentication endpoints - stricter limits to prevent brute force
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // Export/Report endpoints - limited to prevent abuse
        RateLimiter::for('exports', function (Request $request) {
            $key = optional($request->user())->getKey() ?: $request->ip();

            return Limit::perMinute(10)->by($key);
        });

        // Bulk operations - very limited
        RateLimiter::for('bulk', function (Request $request) {
            $key = optional($request->user())->getKey() ?: $request->ip();

            return Limit::perMinute(5)->by($key);
        });

        // Uploads - moderate limits
        RateLimiter::for('uploads', function (Request $request) {
            $key = optional($request->user())->getKey() ?: $request->ip();

            return Limit::perMinute(30)->by($key);
        });
    }
}
