<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Database Compatibility Service
 *
 * Provides database-portable SQL expressions for common operations
 * that differ between MySQL, PostgreSQL, and SQLite.
 *
 * This service ensures the application works consistently across:
 * - MySQL 8.4+
 * - PostgreSQL 13+
 * - SQLite 3.35+
 */
class DatabaseCompatibilityService
{
    /**
     * Get the current database driver name.
     */
    public function getDriver(): string
    {
        return DB::getDriverName();
    }

    /**
     * Check if current driver is PostgreSQL.
     */
    public function isPostgres(): bool
    {
        return $this->getDriver() === 'pgsql';
    }

    /**
     * Check if current driver is MySQL or MariaDB.
     */
    public function isMySQL(): bool
    {
        return in_array($this->getDriver(), ['mysql', 'mariadb']);
    }

    /**
     * Check if current driver is SQLite.
     */
    public function isSQLite(): bool
    {
        return $this->getDriver() === 'sqlite';
    }

    /**
     * Get SQL expression to extract hour from a datetime column.
     *
     * @param  string  $column  The datetime column name
     * @return string SQL expression that returns hour as integer (0-23)
     */
    public function hourExpression(string $column): string
    {
        return match ($this->getDriver()) {
            'pgsql' => "CAST(EXTRACT(HOUR FROM {$column}) AS INTEGER)",
            'sqlite' => "CAST(strftime('%H', {$column}) AS INTEGER)",
            default => "HOUR({$column})", // MySQL, MariaDB
        };
    }

    /**
     * Get SQL expression to extract day from a datetime column.
     *
     * @param  string  $column  The datetime column name
     * @return string SQL expression that returns day as integer (1-31)
     */
    public function dayExpression(string $column): string
    {
        return match ($this->getDriver()) {
            'pgsql' => "CAST(EXTRACT(DAY FROM {$column}) AS INTEGER)",
            'sqlite' => "CAST(strftime('%d', {$column}) AS INTEGER)",
            default => "DAY({$column})", // MySQL, MariaDB
        };
    }

    /**
     * Get SQL expression to extract month from a datetime column.
     *
     * @param  string  $column  The datetime column name
     * @return string SQL expression that returns month as integer (1-12)
     */
    public function monthExpression(string $column): string
    {
        return match ($this->getDriver()) {
            'pgsql' => "CAST(EXTRACT(MONTH FROM {$column}) AS INTEGER)",
            'sqlite' => "CAST(strftime('%m', {$column}) AS INTEGER)",
            default => "MONTH({$column})", // MySQL, MariaDB
        };
    }

    /**
     * Get SQL expression to extract year from a datetime column.
     *
     * @param  string  $column  The datetime column name
     * @return string SQL expression that returns year as integer
     */
    public function yearExpression(string $column): string
    {
        return match ($this->getDriver()) {
            'pgsql' => "CAST(EXTRACT(YEAR FROM {$column}) AS INTEGER)",
            'sqlite' => "CAST(strftime('%Y', {$column}) AS INTEGER)",
            default => "YEAR({$column})", // MySQL, MariaDB
        };
    }

    /**
     * Get SQL expression to truncate datetime to date (YYYY-MM-DD).
     *
     * @param  string  $column  The datetime column name
     * @return string SQL expression that returns date
     */
    public function dateExpression(string $column): string
    {
        return "DATE({$column})"; // Standard SQL, works on all
    }

    /**
     * Get SQL expression to truncate datetime to start of month.
     *
     * @param  string  $column  The datetime column name
     * @return string SQL expression that returns first day of month
     */
    public function monthTruncateExpression(string $column): string
    {
        return match ($this->getDriver()) {
            'pgsql' => "DATE_TRUNC('month', {$column})",
            'sqlite' => "DATE({$column}, 'start of month')",
            default => "DATE_FORMAT({$column}, '%Y-%m-01')", // MySQL, MariaDB
        };
    }

    /**
     * Get SQL expression to truncate datetime to start of week (Saturday).
     *
     * All database drivers are aligned to return the Saturday preceding or equal to
     * the given date, ensuring consistent weekly analytics across MySQL, PostgreSQL,
     * and SQLite environments.
     *
     * @param  string  $column  The datetime column name
     * @return string SQL expression that returns first day of week (Saturday)
     */
    public function weekTruncateExpression(string $column): string
    {
        return match ($this->getDriver()) {
            // PostgreSQL: DATE_TRUNC('week', ...) returns Monday (ISO-8601 week start).
            // To get Saturday: shift date +2 days, truncate to week (Monday), then shift back -2 days.
            // This maps Monday->Monday, so Saturday+2=Monday->Monday, Monday-2=Saturday.
            'pgsql' => "DATE(DATE_TRUNC('week', {$column}::date + INTERVAL '2 days') - INTERVAL '2 days')",
            // SQLite: strftime('%w', date) returns 0=Sunday, 1=Monday, ..., 6=Saturday.
            // To get the Saturday on or before a date, subtract (dow + 1) % 7 days:
            // Saturday(6)->(6+1)%7=0, Sunday(0)->1, Monday(1)->2, ..., Friday(5)->6
            'sqlite' => "DATE({$column}, '-' || ((CAST(strftime('%w', {$column}) AS INTEGER) + 1) % 7) || ' days')",
            // MySQL/MariaDB: WEEKDAY() returns 0=Monday, 1=Tuesday, ..., 5=Saturday, 6=Sunday.
            // To get Saturday: subtract (WEEKDAY + 2) % 7 days.
            // Saturday(5)->0, Sunday(6)->1, Monday(0)->2, ..., Friday(4)->6
            default => "DATE(DATE_SUB({$column}, INTERVAL MOD(WEEKDAY({$column}) + 2, 7) DAY))",
        };
    }

    /**
     * Get SQL expression to truncate datetime to start of year.
     *
     * @param  string  $column  The datetime column name
     * @return string SQL expression that returns first day of year
     */
    public function yearTruncateExpression(string $column): string
    {
        return match ($this->getDriver()) {
            'pgsql' => "DATE_TRUNC('year', {$column})",
            'sqlite' => "DATE({$column}, 'start of year')",
            default => "DATE_FORMAT({$column}, '%Y-01-01')", // MySQL, MariaDB
        };
    }

    /**
     * Get SQL expression for case-insensitive LIKE comparison.
     *
     * @param  string  $column  The column name
     * @param  string  $pattern  The pattern to match (use :placeholder for binding)
     * @return string SQL expression for case-insensitive comparison
     */
    public function ilike(string $column, string $pattern): string
    {
        return match ($this->getDriver()) {
            'pgsql' => "{$column} ILIKE {$pattern}",
            default => "LOWER({$column}) LIKE LOWER({$pattern})", // MySQL, MariaDB, SQLite
        };
    }

    /**
     * Get SQL expression to concatenate strings.
     *
     * @param  array  $columns  Array of column names or string literals
     * @return string SQL expression for concatenation
     */
    public function concat(array $columns): string
    {
        $escapedColumns = array_map(function ($col) {
            // If it's a string literal (contains quotes), use as-is
            if (str_contains($col, "'") || str_contains($col, '"')) {
                return $col;
            }

            return $col;
        }, $columns);

        return match ($this->getDriver()) {
            'pgsql', 'sqlite' => implode(' || ', $escapedColumns),
            default => 'CONCAT('.implode(', ', $escapedColumns).')', // MySQL, MariaDB
        };
    }

    /**
     * Get SQL expression for current timestamp.
     *
     * @return string SQL expression for current timestamp
     */
    public function now(): string
    {
        return match ($this->getDriver()) {
            'pgsql' => 'NOW()',
            'sqlite' => "datetime('now')",
            default => 'NOW()', // MySQL, MariaDB
        };
    }

    /**
     * Get SQL expression to add days to a datetime.
     *
     * @param  string  $column  The datetime column name
     * @param  int  $days  Number of days to add (can be negative)
     * @return string SQL expression
     */
    public function addDays(string $column, int $days): string
    {
        return match ($this->getDriver()) {
            'pgsql' => "{$column} + INTERVAL '{$days} days'",
            'sqlite' => "datetime({$column}, '+{$days} days')",
            default => "DATE_ADD({$column}, INTERVAL {$days} DAY)", // MySQL, MariaDB
        };
    }

    /**
     * Get SQL expression to calculate difference in days between two dates.
     *
     * @param  string  $date1  First date column
     * @param  string  $date2  Second date column
     * @return string SQL expression that returns days as integer
     */
    public function daysDifference(string $date1, string $date2): string
    {
        return match ($this->getDriver()) {
            'pgsql' => "CAST(EXTRACT(DAY FROM ({$date1} - {$date2})) AS INTEGER)",
            'sqlite' => "CAST((julianday({$date1}) - julianday({$date2})) AS INTEGER)",
            default => "DATEDIFF({$date1}, {$date2})", // MySQL, MariaDB
        };
    }

    /**
     * Get SQL expression for JSON value extraction.
     *
     * Note: This provides basic JSON extraction. For complex JSON operations,
     * consider using Eloquent casts instead.
     *
     * @param  string  $column  The JSON column name
     * @param  string  $path  The JSON path (e.g., '$.key' or 'key')
     * @return string SQL expression
     */
    public function jsonExtract(string $column, string $path): string
    {
        // Normalize path to start with $. if not present
        $normalizedPath = str_starts_with($path, '$.') ? $path : "$.{$path}";

        return match ($this->getDriver()) {
            'pgsql' => "{$column}->'{$path}'",
            'sqlite', 'mysql', 'mariadb' => "JSON_EXTRACT({$column}, '{$normalizedPath}')",
            default => "JSON_EXTRACT({$column}, '{$normalizedPath}')",
        };
    }
}
