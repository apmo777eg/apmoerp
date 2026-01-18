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
 *
 * SECURITY (V37-SQL-02): SQL Expression Safety
 * =============================================
 * This service generates SQL expressions used in selectRaw(), whereRaw(), orderByRaw(), and groupBy().
 * All generated expressions are safe because:
 *
 * 1. All expressions are constructed from hardcoded SQL syntax based on the database driver
 *
 * 2. Column name parameters ($column) passed to these methods come from:
 *    - Hardcoded strings in the calling code (e.g., 'sale_date', 'created_at')
 *    - Never from user input or request parameters
 *
 * 3. The returned expressions are pattern-matched database functions:
 *    - DATE(), HOUR(), MONTH(), DATE_TRUNC(), strftime(), etc.
 *    - No dynamic SQL construction beyond column name substitution
 *
 * Static analysis tools may flag these patterns as SQL injection risks because they see
 * variable interpolation. This is a false positive - the variables contain only hardcoded
 * column names from the codebase, never user-provided input.
 *
 * IMPORTANT: Callers must ensure they only pass hardcoded column names to these methods.
 * Never pass user-provided input as the column parameter.
 *
 * @see hourExpression() for hour extraction
 * @see dateExpression() for date truncation
 * @see daysDifference() for date difference calculation
 */
class DatabaseCompatibilityService
{
    /**
     * Regex pattern for valid SQL column identifiers.
     * Allows: table.column format or simple column names.
     */
    private const COLUMN_PATTERN = '/^[a-zA-Z_][a-zA-Z0-9_]*(\.[a-zA-Z_][a-zA-Z0-9_]*)?$/';

    /**
     * Regex pattern for valid JSON path expressions.
     * Allows: Optional $. prefix, property names, and array indices.
     * Examples: "key", "$.key", "user.name", "items[0]", "$.data.items[0].name"
     */
    private const JSON_PATH_PATTERN = '/^(\$\.)?[a-zA-Z_][a-zA-Z0-9_]*(\.[a-zA-Z_][a-zA-Z0-9_]*|\[\d+\])*$/';

    /**
     * Whitelist of safe SQL expressions that can be used as date values.
     * These are safe because they are fixed SQL functions with no user input.
     * Note: These correspond to the output of the now() method for different drivers.
     */
    private const SAFE_DATE_EXPRESSIONS = [
        'NOW()',
        "datetime('now')",
        'CURRENT_TIMESTAMP',
    ];

    /**
     * Validate a column name to prevent SQL injection.
     *
     * @param  string  $column  The column name to validate
     *
     * @throws \InvalidArgumentException if column name is invalid
     */
    private function validateColumnName(string $column): void
    {
        if (! preg_match(self::COLUMN_PATTERN, $column)) {
            throw new \InvalidArgumentException('Invalid column name format');
        }
    }

    /**
     * Validate a date expression (column name or safe SQL function).
     *
     * @param  string  $expr  The date expression to validate
     *
     * @throws \InvalidArgumentException if expression is invalid
     */
    private function validateDateExpression(string $expr): void
    {
        // Check if it's a whitelisted SQL expression (e.g., NOW(), datetime('now'))
        if (in_array($expr, self::SAFE_DATE_EXPRESSIONS, true)) {
            return;
        }

        // Check if it's a valid aggregate function with table.column format
        // Pattern matches: MAX(column), MAX(table.column), MIN(column), etc.
        // Uses same format as COLUMN_PATTERN: alphanumeric/underscore with optional single dot
        if (preg_match('/^(MAX|MIN|AVG|SUM|COUNT)\s*\(\s*[a-zA-Z_][a-zA-Z0-9_]*(\.[a-zA-Z_][a-zA-Z0-9_]*)?\s*\)$/i', $expr)) {
            return;
        }

        // Check plain column name
        if (preg_match(self::COLUMN_PATTERN, $expr)) {
            return;
        }

        throw new \InvalidArgumentException('Invalid date expression format');
    }

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
     * @param  string  $column  The datetime column name (must be a valid SQL identifier)
     * @return string SQL expression that returns hour as integer (0-23)
     *
     * @throws \InvalidArgumentException if column name is invalid
     */
    public function hourExpression(string $column): string
    {
        $this->validateColumnName($column);

        return match ($this->getDriver()) {
            'pgsql' => "CAST(EXTRACT(HOUR FROM {$column}) AS INTEGER)",
            'sqlite' => "CAST(strftime('%H', {$column}) AS INTEGER)",
            default => "HOUR({$column})", // MySQL, MariaDB
        };
    }

    /**
     * Get SQL expression to extract day from a datetime column.
     *
     * @param  string  $column  The datetime column name (must be a valid SQL identifier)
     * @return string SQL expression that returns day as integer (1-31)
     *
     * @throws \InvalidArgumentException if column name is invalid
     */
    public function dayExpression(string $column): string
    {
        $this->validateColumnName($column);

        return match ($this->getDriver()) {
            'pgsql' => "CAST(EXTRACT(DAY FROM {$column}) AS INTEGER)",
            'sqlite' => "CAST(strftime('%d', {$column}) AS INTEGER)",
            default => "DAY({$column})", // MySQL, MariaDB
        };
    }

    /**
     * Get SQL expression to extract month from a datetime column.
     *
     * @param  string  $column  The datetime column name (must be a valid SQL identifier)
     * @return string SQL expression that returns month as integer (1-12)
     *
     * @throws \InvalidArgumentException if column name is invalid
     */
    public function monthExpression(string $column): string
    {
        $this->validateColumnName($column);

        return match ($this->getDriver()) {
            'pgsql' => "CAST(EXTRACT(MONTH FROM {$column}) AS INTEGER)",
            'sqlite' => "CAST(strftime('%m', {$column}) AS INTEGER)",
            default => "MONTH({$column})", // MySQL, MariaDB
        };
    }

    /**
     * Get SQL expression to extract year from a datetime column.
     *
     * @param  string  $column  The datetime column name (must be a valid SQL identifier)
     * @return string SQL expression that returns year as integer
     *
     * @throws \InvalidArgumentException if column name is invalid
     */
    public function yearExpression(string $column): string
    {
        $this->validateColumnName($column);

        return match ($this->getDriver()) {
            'pgsql' => "CAST(EXTRACT(YEAR FROM {$column}) AS INTEGER)",
            'sqlite' => "CAST(strftime('%Y', {$column}) AS INTEGER)",
            default => "YEAR({$column})", // MySQL, MariaDB
        };
    }

    /**
     * Get SQL expression to truncate datetime to date (YYYY-MM-DD).
     *
     * @param  string  $column  The datetime column name (must be a valid SQL identifier)
     * @return string SQL expression that returns date
     *
     * @throws \InvalidArgumentException if column name is invalid
     */
    public function dateExpression(string $column): string
    {
        $this->validateColumnName($column);

        return "DATE({$column})"; // Standard SQL, works on all
    }

    /**
     * Get SQL expression to truncate datetime to start of month.
     *
     * @param  string  $column  The datetime column name (must be a valid SQL identifier)
     * @return string SQL expression that returns first day of month
     *
     * @throws \InvalidArgumentException if column name is invalid
     */
    public function monthTruncateExpression(string $column): string
    {
        $this->validateColumnName($column);

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
     * @param  string  $column  The datetime column name (must be a valid SQL identifier)
     * @return string SQL expression that returns first day of week (Saturday)
     *
     * @throws \InvalidArgumentException if column name is invalid
     */
    public function weekTruncateExpression(string $column): string
    {
        $this->validateColumnName($column);

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
     * @param  string  $column  The datetime column name (must be a valid SQL identifier)
     * @return string SQL expression that returns first day of year
     *
     * @throws \InvalidArgumentException if column name is invalid
     */
    public function yearTruncateExpression(string $column): string
    {
        $this->validateColumnName($column);

        return match ($this->getDriver()) {
            'pgsql' => "DATE_TRUNC('year', {$column})",
            'sqlite' => "DATE({$column}, 'start of year')",
            default => "DATE_FORMAT({$column}, '%Y-01-01')", // MySQL, MariaDB
        };
    }

    /**
     * Get SQL expression for case-insensitive LIKE comparison.
     *
     * @param  string  $column  The column name (must be a valid SQL identifier)
     * @param  string  $pattern  The pattern to match (use :placeholder for binding)
     * @return string SQL expression for case-insensitive comparison
     *
     * @throws \InvalidArgumentException if column name is invalid
     */
    public function ilike(string $column, string $pattern): string
    {
        $this->validateColumnName($column);

        return match ($this->getDriver()) {
            'pgsql' => "{$column} ILIKE {$pattern}",
            default => "LOWER({$column}) LIKE LOWER({$pattern})", // MySQL, MariaDB, SQLite
        };
    }

    /**
     * Get SQL expression to concatenate strings.
     *
     * @param  array  $columns  Array of column names or string literals (column names must be valid SQL identifiers)
     * @return string SQL expression for concatenation
     *
     * @throws \InvalidArgumentException if any column name is invalid
     */
    public function concat(array $columns): string
    {
        $escapedColumns = array_map(function ($col) {
            // If it's a string literal (contains quotes), use as-is
            if (str_contains($col, "'") || str_contains($col, '"')) {
                return $col;
            }

            // Validate column names
            $this->validateColumnName($col);

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
     * @param  string  $column  The datetime column name or safe SQL expression (must be a valid SQL identifier or whitelisted expression)
     * @param  int  $days  Number of days to add (can be negative)
     * @return string SQL expression
     *
     * @throws \InvalidArgumentException if column name/expression is invalid
     */
    public function addDays(string $column, int $days): string
    {
        $this->validateDateExpression($column);

        return match ($this->getDriver()) {
            'pgsql' => "{$column} + INTERVAL '{$days} days'",
            'sqlite' => "datetime({$column}, '+{$days} days')",
            default => "DATE_ADD({$column}, INTERVAL {$days} DAY)", // MySQL, MariaDB
        };
    }

    /**
     * Get SQL expression to calculate difference in days between two dates.
     *
     * @param  string  $date1  First date column or safe SQL expression (must be a valid SQL identifier or whitelisted expression)
     * @param  string  $date2  Second date column or safe SQL expression (must be a valid SQL identifier or whitelisted expression)
     * @return string SQL expression that returns days as integer
     *
     * @throws \InvalidArgumentException if date expressions are invalid
     */
    public function daysDifference(string $date1, string $date2): string
    {
        $this->validateDateExpression($date1);
        $this->validateDateExpression($date2);

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
     * @param  string  $column  The JSON column name (must be a valid SQL identifier)
     * @param  string  $path  The JSON path (e.g., '$.key' or 'key') - must contain only safe characters
     * @return string SQL expression
     *
     * @throws \InvalidArgumentException if column name or path is invalid
     */
    public function jsonExtract(string $column, string $path): string
    {
        $this->validateColumnName($column);

        // Validate JSON path using constant pattern
        // Empty paths are not allowed
        if (empty($path) || ! preg_match(self::JSON_PATH_PATTERN, $path)) {
            throw new \InvalidArgumentException('Invalid JSON path format');
        }

        // Normalize path - strip leading $ or $. for PostgreSQL which uses key names directly
        $plainPath = $path;
        if (str_starts_with($plainPath, '$.')) {
            $plainPath = substr($plainPath, 2);
        } elseif (str_starts_with($plainPath, '$')) {
            $plainPath = substr($plainPath, 1);
        }

        // MySQL/SQLite path with $. prefix
        $normalizedPath = str_starts_with($path, '$.') ? $path : "$.{$path}";

        return match ($this->getDriver()) {
            // PostgreSQL uses arrow operator with plain key name
            'pgsql' => "{$column}->'{$plainPath}'",
            'sqlite', 'mysql', 'mariadb' => "JSON_EXTRACT({$column}, '{$normalizedPath}')",
            default => "JSON_EXTRACT({$column}, '{$normalizedPath}')",
        };
    }
}
