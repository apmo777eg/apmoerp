<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Store;
use App\Models\StoreToken;
use App\Services\BranchContextManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Store Token Authentication Middleware
 *
 * SECURITY (V40-HIGH-39/40): API Token Security
 * ==============================================
 * This middleware handles API token authentication for store integrations.
 *
 * Token Extraction Priority:
 * 1. Authorization: Bearer header (SECURE - required by default)
 * 2. Query parameter 'api_token' (DEPRECATED - disabled by default, leaks via logs/referrers)
 * 3. Request body 'api_token' (DEPRECATED - disabled by default, still insecure)
 *
 * Security Configuration (config/auth.php 'store_token' settings):
 * - allow_deprecated_methods: false (default) → Only Authorization: Bearer header accepted
 * - allow_deprecated_methods: true → Also accepts query/body tokens (legacy mode)
 *
 * When deprecated methods are enabled and used, a warning is logged and X-Deprecation-Warning
 * header is added to the response to inform clients to migrate.
 *
 * V40-HIGH-39/40 FIX: Default changed to reject query/body tokens to prevent token leakage
 * via logs, referrers, and browser history.
 */
class AuthenticateStoreToken
{
    /**
     * Deprecation warning message for insecure token methods.
     */
    private const DEPRECATION_WARNING = 'API token via query/body is deprecated. Use Authorization: Bearer header.';

    public function handle(Request $request, Closure $next, string ...$abilities): Response
    {
        [$token, $tokenSource] = $this->getTokenFromRequest($request);

        if (! $token) {
            return response()->json([
                'success' => false,
                'message' => 'API token required.',
            ], 401);
        }

        // V22-CRIT-01 FIX: Load StoreToken without BranchScope since there's no auth user
        // StoreToken table doesn't have branch_id, but the relationship to Store needs
        // to bypass BranchScope to load properly without authentication
        $storeToken = StoreToken::withoutGlobalScopes()->where('token', $token)->first();

        if (! $storeToken) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid API token.',
            ], 401);
        }

        if ($storeToken->isExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'API token has expired.',
            ], 401);
        }

        // V22-CRIT-01 FIX: Load Store without BranchScope since we're authenticating via token, not user
        // After loading, we'll set the branch context from the store's branch_id
        $store = Store::withoutGlobalScopes()
            ->where('id', $storeToken->store_id)
            ->first();

        if (! $store || ! $store->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Store is not active.',
            ], 403);
        }

        // V22-CRIT-01 FIX: Set branch context from the store's branch_id
        // This allows subsequent queries in the request to be properly scoped
        if ($store->branch_id) {
            BranchContextManager::setBranchContext($store->branch_id);
            $request->attributes->set('branch_id', $store->branch_id);
        }

        if (empty($abilities) && ! $storeToken->hasAbility('*')) {
            return response()->json([
                'success' => false,
                'message' => 'This endpoint requires explicit token abilities.',
            ], 403);
        }

        foreach ($abilities as $ability) {
            if (! $storeToken->hasAbility($ability)) {
                return response()->json([
                    'success' => false,
                    'message' => "Token does not have the '{$ability}' ability.",
                ], 403);
            }
        }

        $storeToken->touchLastUsed();

        // Set store context before passing to next middleware
        $this->setStoreContext($request, $store, $storeToken);

        // Process request through remaining middleware
        $response = $next($request);

        // V37-HIGH-03 FIX: Log deprecation warning when token is passed via query/body
        // Tokens in query strings can leak via logs, referrers, and browser history.
        // Prefer Authorization: Bearer header for secure token transmission.
        if ($tokenSource !== 'header') {
            // Rate limit deprecation logging to prevent log flooding attacks
            // Log at most once per store per minute using cache
            $cacheKey = "deprecated_token_log:{$store->id}";
            if (! cache()->has($cacheKey)) {
                // Log minimal information to avoid exposing sensitive data
                Log::warning('Deprecated API token method used', [
                    'token_source' => $tokenSource,
                    'endpoint' => $request->path(),
                ]);
                cache()->put($cacheKey, true, 60); // Cache for 60 seconds
            }

            // V37-HIGH-03 FIX: Add deprecation header to response to inform clients
            if ($response instanceof Response) {
                $response->headers->set('X-Deprecation-Warning', self::DEPRECATION_WARNING);
            }
        }

        return $response;
    }

    /**
     * V22-CRIT-01 FIX: Clear branch context after the request is handled
     * This prevents context leakage between requests
     */
    public function terminate(Request $request, Response $response): void
    {
        BranchContextManager::clearBranchContext();
    }

    /**
     * Set store and token context on the request.
     *
     * V37-CODE-REVIEW: Extracted to avoid code duplication between
     * secure and deprecated token handling paths.
     */
    protected function setStoreContext(Request $request, Store $store, StoreToken $storeToken): void
    {
        $request->merge([
            'store' => $store,
            'store_token' => $storeToken,
        ]);
    }

    /**
     * Extract API token from the request.
     *
     * SECURITY (V37-HIGH-03): Token extraction priority:
     * 1. Authorization: Bearer header (PREFERRED - most secure)
     * 2. Query parameter 'api_token' (DEPRECATED - leaks via logs/referrers/history)
     * 3. Request body 'api_token' (DEPRECATED - less exposure but still not ideal)
     *
     * When tokens are passed via query/body, a deprecation warning is logged
     * and a warning header is returned to inform clients to migrate.
     *
     * Security can be tightened via config/auth.php 'store_token' settings:
     * - allow_deprecated_methods: When false, rejects query/body tokens (header-only mode)
     *
     * @return array{0: string|null, 1: string} Tuple of [token, source]
     */
    protected function getTokenFromRequest(Request $request): array
    {
        // Preferred: Authorization header (secure, not logged by default)
        $authHeader = $request->header('Authorization');

        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            return [substr($authHeader, 7), 'header'];
        }

        // V40-HIGH-39/40 FIX: Security configuration for deprecated token methods
        // When allow_deprecated is false, deprecated methods (query/body) are rejected.
        // Default: allow_deprecated = false for secure posture. Set to true only for legacy migration.
        // Tokens in query strings leak via logs, referrers, and browser history.
        $allowDeprecated = config('auth.store_token.allow_deprecated_methods', false);

        if (! $allowDeprecated) {
            // Strict mode: only Authorization: Bearer header is accepted
            return [null, 'none'];
        }

        // Deprecated: Query parameter (can leak via logs, referrers, browser history)
        $queryToken = $request->query('api_token');
        if ($queryToken) {
            return [$queryToken, 'query'];
        }

        // Deprecated: Request body (less exposure than query, but still not ideal)
        $bodyToken = $request->input('api_token');
        if ($bodyToken) {
            return [$bodyToken, 'body'];
        }

        return [null, 'none'];
    }
}
