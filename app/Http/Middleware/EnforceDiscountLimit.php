<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnforceDiscountLimit
 *
 * - Protects checkout/sales endpoints against excessive discounts or price overrides.
 * - Reads user/role/setting-driven limits. Expected request shape:
 *     { items: [ {price, qty, discount}, ... ], invoice_discount }
 * - Bypasses if user has 'discount.unlimited' OR 'price.override'.
 *
 * Adjust the getters (getUserMaxLineDiscount, getUserMaxInvoiceDiscount) to your schema.
 */
class EnforceDiscountLimit
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $this->error('Unauthenticated.', 401);
        }

        // Exempt privileged users
        if ($this->hasAnyPermission($user, ['discount.unlimited', 'price.override'])) {
            return $next($request);
        }

        $payload = $request->all();

        $maxLine = $this->getUserMaxLineDiscount($user);    // e.g., 15 (%)
        $maxInvoice = $this->getUserMaxInvoiceDiscount($user); // e.g., 20 (%)

        // Validate line discounts
        $items = (array) ($payload['items'] ?? []);
        foreach ($items as $idx => $row) {
            $disc = decimal_float($row['discount'] ?? 0);
            if ($disc > $maxLine) {
                return $this->error('Line #'.($idx + 1)." discount exceeds your limit ($maxLine%).", 422);
            }
        }

        // Validate invoice-level discount
        $invDisc = decimal_float($payload['invoice_discount'] ?? 0);
        if ($invDisc > $maxInvoice) {
            return $this->error("Invoice discount exceeds your limit ($maxInvoice%).", 422);
        }

        return $next($request);
    }

    protected function hasAnyPermission($user, array $perms): bool
    {
        foreach ($perms as $p) {
            if (method_exists($user, 'hasPermissionTo') && $user->hasPermissionTo($p)) {
                return true;
            }
            if (method_exists($user, 'can') && $user->can($p)) {
                return true;
            }
        }

        return false;
    }

    protected function getUserMaxLineDiscount($user): float
    {
        // Priority: user attribute -> role/permission meta -> system setting
        if (! is_null($user->max_line_discount) && is_numeric($user->max_line_discount)) {
            // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
            return decimal_float($user->max_line_discount);
        }

        // If using spatie permissions meta, you can read from a profile/settings table.
        return decimal_float(config('erp.discount.max_line', 15)); // sensible default
    }

    protected function getUserMaxInvoiceDiscount($user): float
    {
        if (! is_null($user->max_invoice_discount) && is_numeric($user->max_invoice_discount)) {
            // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
            return decimal_float($user->max_invoice_discount);
        }

        return decimal_float(config('erp.discount.max_invoice', 20));
    }

    protected function error(string $message, int $status): Response
    {
        return response()->json(['success' => false, 'message' => $message], $status);
    }
}
