<?php

declare(strict_types=1);

use App\Http\Middleware\Impersonate;
use Illuminate\Support\Facades\Auth;

if (! function_exists('current_user')) {
    function current_user(): ?\Illuminate\Contracts\Auth\Authenticatable
    {
        return Auth::user();
    }
}

if (! function_exists('current_branch_id')) {
    function current_branch_id(): ?int
    {
        // Request::instance() is no longer available on the facade in modern Laravel versions
        // and would trigger a bad method call. Use the request helper to access the current
        // request instance instead.
        $req = request();

        if ($req && $req->attributes->has('branch_id')) {
            $id = $req->attributes->get('branch_id');

            return $id !== null ? (int) $id : null;
        }

        if (app()->has('req.branch_id')) {
            $id = app('req.branch_id');

            return $id !== null ? (int) $id : null;
        }

        return null;
    }
}

if (! function_exists('is_impersonating')) {
    /**
     * Check if the current request is being performed during an impersonation session.
     *
     * @return bool True if impersonation is active
     */
    function is_impersonating(): bool
    {
        return Impersonate::isImpersonating();
    }
}

if (! function_exists('actual_user_id')) {
    /**
     * Get the ID of the actual user performing actions.
     * During impersonation, this returns the impersonator's ID.
     * Otherwise, returns the current authenticated user's ID.
     *
     * @return int|null The actual user's ID
     */
    function actual_user_id(): ?int
    {
        // If impersonating, return the impersonator's ID
        $performerId = Impersonate::getActualPerformerId();
        if ($performerId !== null) {
            return $performerId;
        }

        // Otherwise, return the authenticated user's ID
        return Auth::id();
    }
}

if (! function_exists('impersonation_context')) {
    /**
     * Get the full impersonation context for audit logging.
     *
     * @return array{performed_by_id: int|null, impersonating_as_id: int|null, is_impersonating: bool}
     */
    function impersonation_context(): array
    {
        $isImpersonating = Impersonate::isImpersonating();

        return [
            'performed_by_id' => $isImpersonating ? Impersonate::getActualPerformerId() : Auth::id(),
            'impersonating_as_id' => $isImpersonating ? Impersonate::getImpersonatedUserId() : null,
            'is_impersonating' => $isImpersonating,
        ];
    }
}

if (! function_exists('money')) {
    /**
     * Format a monetary value with currency.
     * V38-FINANCE-01 FIX: Use BCMath for normalization and bcround before float conversion
     * to minimize floating-point precision issues.
     */
    function money(float|string|int $amount, string $currency = 'EGP'): string
    {
        $scales = [
            'EGP' => 2,
            'USD' => 2,
            'EUR' => 2,
            'GBP' => 2,
            'KWD' => 3,
            'BHD' => 3,
            'OMR' => 3,
            'JOD' => 3,
            'IQD' => 3,
        ];

        $scale = $scales[$currency] ?? 2;
        // V38-FINANCE-01 FIX: Use bcround for proper rounding before conversion
        $normalized = bcround((string) $amount, $scale);
        $formatted = number_format(decimal_float($normalized, $scale), $scale, '.', ',');

        return $formatted.' '.$currency;
    }
}

if (! function_exists('percent')) {
    /**
     * Format a percentage value.
     * V38-FINANCE-01 FIX: Use bcround before float conversion
     * to minimize floating-point precision issues.
     */
    function percent(float|string|int $value, int $decimals = 2): string
    {
        // V38-FINANCE-01 FIX: Use bcround for proper rounding before conversion
        $normalized = bcround((string) $value, $decimals);

        return number_format(decimal_float($normalized, $decimals), $decimals, '.', ',').'%';
    }
}

if (! function_exists('setting')) {
    /**
     * Get or set a system setting value.
     *
     * @param  string  $key  Setting key (e.g., 'pos.max_discount_percent', 'inventory.default_costing_method')
     * @param  mixed  $default  Default value if setting doesn't exist
     */
    function setting(string $key, mixed $default = null): mixed
    {
        static $settingsService = null;

        if ($settingsService === null) {
            $settingsService = app(\App\Services\SettingsService::class);
        }

        return $settingsService->get($key, $default);
    }
}

if (! function_exists('sanitize_svg_icon')) {
    /**
     * Sanitize SVG icon content using a strict allow-list approach.
     * Only allows safe SVG elements and attributes to prevent XSS.
     *
     * SECURITY (V37-XSS-01): XSS Prevention via DOM-based Sanitization
     * ================================================================
     * This function is designed to safely render SVG icons in Blade templates
     * using {!! sanitize_svg_icon($icon) !!} syntax.
     *
     * Security measures implemented:
     *
     * 1. ALLOW-LIST ELEMENTS: Only explicitly safe SVG elements are permitted:
     *    svg, path, circle, rect, line, polyline, polygon, ellipse, g, defs,
     *    symbol, title, desc, lineargradient, radialgradient, stop, clippath, mask
     *    - Dangerous elements like <script>, <foreignObject>, <use> are blocked
     *
     * 2. ALLOW-LIST ATTRIBUTES: Only safe presentational attributes are permitted:
     *    - Structural: id, class, width, height, viewbox, xmlns
     *    - Visual: fill, stroke, stroke-width, opacity, transform
     *    - Geometry: d, cx, cy, r, rx, ry, x, y, points
     *    - Event handlers (on*) are explicitly blocked
     *    - href/xlink:href/src/data are explicitly blocked (javascript: vectors)
     *    - style attribute is blocked (CSS-based exploits)
     *
     * 3. VALUE SANITIZATION: Attribute values are checked for malicious patterns:
     *    - javascript:, data:, expression(), vbscript:, behavior:, binding:
     *    - Control characters are stripped
     *    - Whitespace is normalized
     *
     * 4. DOM-BASED PARSING: Uses DOMDocument for proper HTML/SVG parsing,
     *    preventing parser differential attacks
     *
     * Static analysis tools flag {!! !!} as XSS risks. This is a false positive when
     * the content is passed through this sanitizer, as all dangerous content is removed.
     *
     * @param  string|null  $svg  The SVG content to sanitize
     * @return string Sanitized SVG or empty string
     */
    function sanitize_svg_icon(?string $svg): string
    {
        if (empty($svg)) {
            return '';
        }

        // Define allowed SVG elements (strict subset - no foreignObject, script, etc.)
        $allowedTags = [
            'svg', 'path', 'circle', 'rect', 'line', 'polyline', 'polygon',
            'ellipse', 'g', 'defs', 'symbol', 'title', 'desc',
            'lineargradient', 'radialgradient', 'stop', 'clippath', 'mask',
        ];

        // Define allowed attributes (strict subset - no event handlers, no href for safety)
        $allowedAttrs = [
            'id', 'class', 'width', 'height', 'viewbox', 'fill',
            'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin',
            'd', 'cx', 'cy', 'r', 'rx', 'ry', 'x', 'x1', 'x2', 'y', 'y1', 'y2',
            'points', 'transform', 'opacity', 'fill-opacity', 'stroke-opacity',
            'clip-path', 'offset', 'stop-color', 'stop-opacity',
            'xmlns', 'preserveaspectratio', 'fill-rule', 'clip-rule', 'vector-effect',
        ];

        // Normalize to lowercase for case-insensitive matching
        $normalizedAllowedTags = array_map('strtolower', $allowedTags);
        $normalizedAllowedAttrs = array_map('strtolower', $allowedAttrs);

        // Use DOMDocument for proper parsing
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument;

        // Wrap in HTML to ensure proper parsing
        $wrapped = '<!DOCTYPE html><html><body>'.$svg.'</body></html>';
        $dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR);
        libxml_clear_errors();

        // Collect elements to remove (cannot modify during iteration)
        $toRemove = [];

        // Find and sanitize all elements
        $xpath = new \DOMXPath($dom);
        $allElements = $xpath->query('//*');

        foreach ($allElements as $element) {
            if (! $element instanceof \DOMElement) {
                continue;
            }

            $tagName = strtolower($element->tagName);

            // Only allow explicitly whitelisted elements
            if (! in_array($tagName, array_merge($normalizedAllowedTags, ['html', 'body']), true)) {
                $toRemove[] = $element;

                continue;
            }

            // Remove disallowed attributes
            $attrsToRemove = [];
            foreach ($element->attributes as $attr) {
                $attrName = strtolower($attr->name);

                // Normalize value: decode entities, remove control chars, collapse whitespace
                $attrValue = html_entity_decode($attr->value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $attrValue = preg_replace('/[\x00-\x1f\x7f]/u', '', $attrValue); // Remove control chars
                $attrValue = preg_replace('/\s+/', ' ', $attrValue); // Collapse whitespace
                $attrValue = strtolower(trim($attrValue));

                // Remove any event handlers (on*)
                if (preg_match('/^on[a-z]/i', $attrName)) {
                    $attrsToRemove[] = $attr->name;

                    continue;
                }

                // Block any href/xlink:href attributes (potential javascript: vectors)
                if (in_array($attrName, ['href', 'xlink:href', 'src', 'data', 'action', 'formaction'])) {
                    $attrsToRemove[] = $attr->name;

                    continue;
                }

                // Remove dangerous attribute values (with normalized value check)
                if (preg_match('/(javascript|data\s*:|expression|vbscript|behavior|binding)/i', $attrValue)) {
                    $attrsToRemove[] = $attr->name;

                    continue;
                }

                // Remove style attribute entirely for safety (CSS can contain exploits)
                if ($attrName === 'style') {
                    $attrsToRemove[] = $attr->name;

                    continue;
                }

                // Only allow explicitly whitelisted attributes
                if (! in_array($attrName, $normalizedAllowedAttrs, true)) {
                    $attrsToRemove[] = $attr->name;
                }
            }

            foreach ($attrsToRemove as $attrName) {
                $element->removeAttribute($attrName);
            }
        }

        // Remove elements marked for removal
        foreach ($toRemove as $element) {
            $element->parentNode?->removeChild($element);
        }

        // Extract content from body
        $body = $dom->getElementsByTagName('body')->item(0);
        if (! $body) {
            return '';
        }

        $result = '';
        foreach ($body->childNodes as $child) {
            $result .= $dom->saveHTML($child);
        }

        return trim($result);
    }
}

// Polyfills for Laravel helper functions (offline environment support)

if (! function_exists('first_accessible_route_for_user')) {
    /**
     * Get the first accessible route for a user based on their permissions.
     * This is used for post-login redirects to determine where the user should land.
     *
     * @return string The route name of the first accessible module
     */
    function first_accessible_route_for_user(?\Illuminate\Contracts\Auth\Authenticatable $user = null): string
    {
        $user = $user ?? Auth::user();

        if ($user === null) {
            return 'login';
        }

        // Define routes and their required permissions in priority order
        // First match wins (dashboard is highest priority)
        $routePermissions = [
            'dashboard' => 'dashboard.view',
            'pos.terminal' => 'pos.use',
            'app.sales.index' => 'sales.view',
            'app.purchases.index' => 'purchases.view',
            'customers.index' => 'customers.view',
            'suppliers.index' => 'suppliers.view',
            'app.inventory.products.index' => 'inventory.products.view',
            'app.warehouse.index' => 'warehouse.view',
            'app.accounting.index' => 'accounting.view',
            'app.expenses.index' => 'expenses.view',
            'app.income.index' => 'income.view',
            'app.banking.accounts.index' => 'banking.view',
            'app.hrm.index' => 'hrm.employees.view',
            'app.rental.index' => 'rental.units.view',
            'app.manufacturing.index' => 'manufacturing.view',
            'app.fixed-assets.index' => 'fixed-assets.view',
            'app.projects.index' => 'projects.view',
            'app.documents.index' => 'documents.view',
            'app.helpdesk.index' => 'helpdesk.view',
            'admin.users.index' => 'users.manage',
            'admin.roles.index' => 'roles.manage',
            'admin.branches.index' => 'branches.view',
            'admin.settings' => 'settings.view',
            'admin.modules.index' => 'modules.manage',
            'admin.reports.index' => 'reports.view',
            'admin.logs.audit' => 'logs.audit.view',
        ];

        foreach ($routePermissions as $route => $permission) {
            if (\Illuminate\Support\Facades\Route::has($route) && $user->can($permission)) {
                return $route;
            }
        }

        // Fallback to profile if no other route is accessible
        return 'profile.edit';
    }
}

if (! function_exists('join_paths')) {
    /**
     * Join file paths with the appropriate directory separator.
     */
    function join_paths(string ...$paths): string
    {
        if (count($paths) === 0) {
            return '';
        }

        $result = $paths[0];
        for ($i = 1; $i < count($paths); $i++) {
            $result = rtrim($result, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.ltrim($paths[$i], DIRECTORY_SEPARATOR);
        }

        return $result;
    }
}

if (! function_exists('value')) {
    /**
     * Return the default value of the given value.
     */
    function value(mixed $value, mixed ...$args): mixed
    {
        return $value instanceof Closure ? $value(...$args) : $value;
    }
}

if (! function_exists('array_last')) {
    /**
     * Get the last element from an array.
     */
    function array_last(array $array): mixed
    {
        return count($array) > 0 ? end($array) : null;
    }
}

if (! function_exists('bcround')) {
    /**
     * Round a string number using bcmath with proper half-up rounding.
     * V7-MEDIUM-U10 FIX: Implement proper rounding instead of truncation.
     * V56-CRITICAL-01 FIX: Correct rounding for negative numbers using "half away from zero"
     * behavior to match PHP's standard round() function (PHP_ROUND_HALF_UP).
     *
     * Examples:
     * - bcround('1.235', 2) returns '1.24' (positive rounds away from zero)
     * - bcround('-1.235', 2) returns '-1.24' (negative rounds away from zero)
     * - bcround('1.234', 2) returns '1.23'
     * - bcround('-1.234', 2) returns '-1.23'
     *
     * @param string|null $value The value to round
     * @param int $precision Number of decimal places
     * @return string Rounded value
     */
    function bcround(?string $value, int $precision = 2): string
    {
        // Handle empty/null values
        if ($value === '' || $value === null) {
            return '0';
        }

        // V56-CRITICAL-01 FIX: Use absolute value approach with proper offset handling
        // to achieve "half away from zero" rounding (matching PHP's standard round()).
        // bcmath truncates toward zero, so we need to work with absolute values
        // and then restore the sign to get correct results for negative numbers.
        //
        // V57-CRITICAL-01 FIX: Added normalization to prevent "-0.00" display issue.
        // When a small negative number rounds to zero, we should return "0.XX" not "-0.XX".
        $isNegative = str_starts_with($value, '-');
        $absValue = $isNegative ? ltrim($value, '-') : $value;

        // Add 0.5 * 10^(-precision) to achieve half-up rounding on absolute value
        // e.g., for precision=2, add 0.005
        $offset = '0.' . str_repeat('0', $precision) . '5';
        $rounded = bcadd($absValue, $offset, $precision);

        // V57-CRITICAL-01 FIX: Normalize result to avoid "-0.00" display
        // If the rounded value is zero (or effectively zero), return positive zero
        if (bccomp($rounded, '0', $precision) === 0) {
            return $rounded; // Return positive zero format
        }

        // Restore sign if negative
        return $isNegative ? '-' . $rounded : $rounded;
    }
}

if (! function_exists('decimal')) {
    /**
     * Convert a numeric value to a safe decimal string for JSON/API output.
     * V38-FINANCE-01 FIX: Avoid float cast precision loss in financial contexts.
     *
     * This function provides a consistent way to output decimal values in API responses
     * and JSON output without the precision loss that comes from (float) casting.
     *
     * For most JSON consumers (JavaScript, etc.), a string representation of a decimal
     * is actually safer than a float because it preserves exact precision.
     *
     * @param mixed $value The value to convert
     * @param int $precision Number of decimal places (default 2 for currency)
     * @return string Formatted decimal string suitable for JSON output
     */
    function decimal(mixed $value, int $precision = 2): string
    {
        if ($value === null || $value === '') {
            return bcadd('0', '0', $precision);
        }

        return bcadd((string) $value, '0', $precision);
    }
}

if (! function_exists('decimal_float')) {
    /**
     * Convert a numeric value to a float with controlled precision.
     * V38-FINANCE-01 FIX: Use BCMath for rounding before float conversion.
     *
     * When float output is absolutely required (e.g., for external APIs that expect numbers),
     * this function ensures the value is properly rounded using BCMath first, minimizing
     * floating-point precision issues.
     *
     * PREFER using decimal() for API responses when possible, as string representation
     * is safer for financial data.
     *
     * @param mixed $value The value to convert
     * @param int $precision Number of decimal places (default 2 for currency)
     * @return float Rounded float value
     */
    function decimal_float(mixed $value, int $precision = 2): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        // First round using BCMath, then convert to float
        // V43-FINANCE-01: This internal cast is safe as the value has been
        // properly rounded using BCMath to eliminate floating-point drift
        $rounded = bcround((string) $value, $precision);

        // @phpstan-ignore-next-line - Float cast is intentional after BCMath rounding
        return (float) $rounded;
    }
}
