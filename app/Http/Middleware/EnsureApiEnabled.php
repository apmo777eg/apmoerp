<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to check if API access is enabled via system settings.
 *
 * This middleware respects the 'advanced.enable_api' system setting
 * and returns a 503 Service Unavailable if API is disabled.
 */
class EnsureApiEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiEnabled = Cache::remember('api_enabled_setting', 300, function () {
            // Use value() to only retrieve the value column for better performance
            $value = SystemSetting::where('setting_key', 'advanced.enable_api')->value('value');

            // Default to true if setting doesn't exist
            if ($value === null) {
                return true;
            }

            // Handle various possible stored values with explicit whitelist
            if (is_bool($value)) {
                return $value;
            }

            if (is_string($value)) {
                // Explicit whitelist for truthy values - unknown values default to false for security
                return in_array(strtolower(trim($value)), ['1', 'true', 'on', 'yes'], true);
            }

            // Numeric or other types - 1 is true, everything else is false
            return $value === 1 || $value === '1';
        });

        if (! $apiEnabled) {
            return response()->json([
                'success' => false,
                'message' => 'API access is currently disabled. Please contact the administrator.',
                'error' => 'api_disabled',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return $next($request);
    }
}
