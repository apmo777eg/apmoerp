<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * UI Helper Service
 *
 * Provides utility methods for consistent UI/UX across the application
 */
class UIHelperService
{
    /**
     * Generate breadcrumb items from route name
     */
    public function generateBreadcrumbs(string $routeName, array $parameters = []): array
    {
        $parts = explode('.', $routeName);
        $breadcrumbs = [];
        $accumulated = '';

        foreach ($parts as $index => $part) {
            $accumulated .= ($accumulated ? '.' : '').$part;

            $label = Str::title(str_replace(['-', '_'], ' ', $part));

            // Skip if it's a CRUD action
            if (in_array($part, ['index', 'create', 'edit', 'show'], true)) {
                $label = match ($part) {
                    'index' => __('List'),
                    'create' => __('Create'),
                    'edit' => __('Edit'),
                    'show' => __('View'),
                    default => $label
                };
            }

            $breadcrumbs[] = [
                'label' => __($label),
                'url' => $index === count($parts) - 1
                    ? null
                    : (Route::has($accumulated) ? route($accumulated, $parameters) : null),
                'active' => $index === count($parts) - 1,
            ];
        }

        return $breadcrumbs;
    }

    /**
     * Format status badge HTML
     */
    public function getStatusBadgeClass(string $status): string
    {
        return match (strtolower($status)) {
            'active', 'completed', 'paid', 'approved', 'confirmed' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
            'inactive', 'cancelled', 'rejected', 'void' => 'bg-gray-100 text-gray-700 border-gray-200',
            'pending', 'processing', 'in_progress' => 'bg-amber-100 text-amber-700 border-amber-200',
            'draft', 'unpaid', 'partial' => 'bg-blue-100 text-blue-700 border-blue-200',
            'overdue', 'expired', 'failed', 'blocked' => 'bg-red-100 text-red-700 border-red-200',
            default => 'bg-slate-100 text-slate-700 border-slate-200',
        };
    }

    /**
     * Format currency amount with proper localization
     */
    public function formatCurrency(float $amount, string $currency = 'USD', bool $showSymbol = true): string
    {
        $currency = strtoupper($currency);

        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'SAR' => 'ر.س',
            'AED' => 'د.إ',
            'EGP' => 'ج.م',
        ];

        $symbol = $symbols[$currency] ?? $currency;
        $isNegative = $amount < 0;
        $absAmount = abs($amount);
        $formatted = number_format($absAmount, 2);

        if (! $showSymbol) {
            return $isNegative ? '-'.$formatted : $formatted;
        }

        // RTL currencies go after the number
        if (in_array($currency, ['SAR', 'AED', 'EGP'], true)) {
            return $isNegative ? '-'.$formatted.' '.$symbol : $formatted.' '.$symbol;
        }

        // Symbol-leading currencies (USD, EUR, GBP, etc.)
        return $isNegative ? '-'.$symbol.' '.$formatted : $symbol.' '.$formatted;
    }

    /**
     * Generate initials from name for avatars
     */
    public function getInitials(string $name, int $length = 2): string
    {
        $words = explode(' ', trim($name));
        $initials = '';

        foreach ($words as $word) {
            if (mb_strlen($initials) >= $length) {
                break;
            }
            $initials .= mb_substr($word, 0, 1);
        }

        return mb_strtoupper($initials);
    }

    /**
     * Generate color for avatar based on name
     */
    public function getAvatarColor(string $name): string
    {
        $colors = [
            'bg-red-500', 'bg-orange-500', 'bg-amber-500',
            'bg-yellow-500', 'bg-lime-500', 'bg-green-500',
            'bg-emerald-500', 'bg-teal-500', 'bg-cyan-500',
            'bg-sky-500', 'bg-blue-500', 'bg-indigo-500',
            'bg-violet-500', 'bg-purple-500', 'bg-fuchsia-500',
            'bg-pink-500', 'bg-rose-500',
        ];

        $hash = 0;
        for ($i = 0; $i < strlen($name); $i++) {
            $hash = ord($name[$i]) + (($hash << 5) - $hash);
        }

        return $colors[abs($hash) % count($colors)];
    }

    /**
     * Format date range for display
     */
    public function formatDateRange(?string $start, ?string $end): string
    {
        if (! $start && ! $end) {
            return __('All time');
        }

        if ($start && ! $end) {
            return __('From :date', ['date' => date('M d, Y', strtotime($start))]);
        }

        if (! $start && $end) {
            return __('Until :date', ['date' => date('M d, Y', strtotime($end))]);
        }

        return date('M d, Y', strtotime($start)).' - '.date('M d, Y', strtotime($end));
    }

    /**
     * Generate pagination summary text
     */
    public function getPaginationSummary(int $from, int $to, int $total): string
    {
        return __('Showing :from to :to of :total results', [
            'from' => number_format($from),
            'to' => number_format($to),
            'total' => number_format($total),
        ]);
    }

    /**
     * Convert bytes to human readable format
     */
    public function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        if ($bytes === 0) {
            return '0 B';
        }

        $i = 0;
        $value = $bytes;

        while ($value >= 1024 && $i < count($units) - 1) {
            $value = decimal_float(bcdiv((string) $value, '1024', $precision + 2));
            $i++;
        }

        // Round before formatting to check for promotion
        $rounded = round($value, $precision);

        // If rounded value is 1024 or more, promote to next unit
        if ($rounded >= 1024 && $i < count($units) - 1) {
            $value = $rounded / 1024;
            $i++;
            $rounded = round($value, $precision);
        }

        // Format the value without thousand separators and remove trailing zeros
        $formatted = rtrim(rtrim(number_format($rounded, $precision, '.', ''), '0'), '.');

        return $formatted.' '.$units[$i];
    }

    /**
     * Generate safe HTML attributes for data-* attributes
     */
    public function dataAttributes(array $data): string
    {
        $attributes = [];
        foreach ($data as $key => $value) {
            $safeKey = htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
            $safeValue = htmlspecialchars(is_array($value) ? json_encode($value) : $value, ENT_QUOTES, 'UTF-8');
            $attributes[] = "data-{$safeKey}=\"{$safeValue}\"";
        }

        return implode(' ', $attributes);
    }

    /**
     * Truncate text with ellipsis
     */
    public function truncate(string $text, int $length = 100, string $ending = '...'): string
    {
        if ($length <= mb_strlen($ending)) {
            return mb_substr($text, 0, $length);
        }

        if (mb_strlen($text) <= $length) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $length - mb_strlen($ending))).$ending;
    }
}
