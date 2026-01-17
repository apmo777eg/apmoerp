<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for monitoring and optimizing query performance.
 */
class QueryPerformanceService
{
    /**
     * Slow query threshold in milliseconds.
     */
    protected int $slowQueryThreshold;

    /**
     * Whether query logging is enabled.
     */
    protected bool $loggingEnabled;

    public function __construct()
    {
        $this->slowQueryThreshold = (int) config('settings.advanced.slow_query_threshold', 100);
        $this->loggingEnabled = (bool) config('settings.advanced.enable_query_logging', false);
    }

    /**
     * Enable query logging for debugging.
     */
    public function enableQueryLog(): void
    {
        if ($this->loggingEnabled) {
            DB::enableQueryLog();
        }
    }

    /**
     * Get and analyze query log.
     *
     * @return array{queries: array, total_time: float, slow_queries: array}
     */
    public function getQueryAnalysis(): array
    {
        $queries = DB::getQueryLog();
        $totalTime = 0;
        $slowQueries = [];

        foreach ($queries as $query) {
            $totalTime += $query['time'];

            if ($query['time'] > $this->slowQueryThreshold) {
                $slowQueries[] = [
                    'sql' => $query['query'],
                    'bindings' => $query['bindings'],
                    'time' => $query['time'],
                ];
            }
        }

        return [
            'queries' => $queries,
            'total_time' => $totalTime,
            'slow_queries' => $slowQueries,
            'query_count' => count($queries),
        ];
    }

    /**
     * Log slow queries.
     */
    public function logSlowQueries(): void
    {
        if (! $this->loggingEnabled) {
            return;
        }

        $analysis = $this->getQueryAnalysis();

        foreach ($analysis['slow_queries'] as $query) {
            Log::channel('slow-queries')->warning('Slow query detected', [
                'sql' => $query['sql'],
                'time_ms' => $query['time'],
                'threshold_ms' => $this->slowQueryThreshold,
            ]);
        }
    }

    /**
     * Get database statistics.
     *
     * @return array<string, mixed>
     */
    public function getDatabaseStats(): array
    {
        return Cache::remember('db_stats', 3600, function () {
            $stats = [];

            // Get table sizes
            $tables = DB::select('
                SELECT 
                    table_name,
                    table_rows,
                    ROUND(data_length / 1024 / 1024, 2) as data_size_mb,
                    ROUND(index_length / 1024 / 1024, 2) as index_size_mb
                FROM information_schema.tables 
                WHERE table_schema = ?
                ORDER BY data_length DESC
                LIMIT 20
            ', [config('database.connections.mysql.database')]);

            $stats['largest_tables'] = $tables;

            // Get index usage
            $indexUsage = DB::select('
                SELECT 
                    table_name,
                    index_name,
                    seq_in_index,
                    column_name,
                    cardinality
                FROM information_schema.statistics
                WHERE table_schema = ?
                ORDER BY table_name, index_name, seq_in_index
                LIMIT 100
            ', [config('database.connections.mysql.database')]);

            $stats['indexes'] = $indexUsage;

            return $stats;
        });
    }

    /**
     * Analyze query and suggest optimizations.
     *
     * SECURITY: This method is designed for internal use only with queries from
     * trusted sources (e.g., query log entries, admin debugging tools).
     * 
     * Input validation includes:
     * - Only SELECT statements are allowed (EXPLAIN requirement)
     * - Dangerous patterns are blocked (UNION SELECT, INTO OUTFILE, etc.)
     * 
     * Note: EXPLAIN FORMAT=JSON syntax requires the query to be concatenated
     * (not parameterized) as MySQL doesn't support binding the query itself.
     * The validation above provides defense-in-depth against injection.
     *
     * @param  string  $sql  SQL query (must be a SELECT statement from trusted source)
     * @return array<string, mixed>
     * 
     * @internal This method should only be called with queries from trusted sources
     */
    public function analyzeQuery(string $sql): array
    {
        try {
            // Normalize whitespace for validation
            $normalizedSql = preg_replace('/\s+/', ' ', trim($sql));

            // Only allow SELECT statements for EXPLAIN (security measure)
            if (! preg_match('/^\s*SELECT\s/i', $normalizedSql)) {
                return [
                    'error' => 'Only SELECT queries can be analyzed with EXPLAIN.',
                    'suggestions' => [],
                ];
            }

            // Block dangerous patterns that could be used for SQL injection or information disclosure
            // Patterns are grouped by type for better error reporting
            $dangerousPatterns = [
                'statement_injection' => [
                    '/;\s*(DROP|DELETE|UPDATE|INSERT|ALTER|CREATE|TRUNCATE|EXEC|EXECUTE)/i',
                    '/UNION\s+(ALL\s+)?SELECT/i',
                ],
                'file_operations' => [
                    '/INTO\s+(OUTFILE|DUMPFILE)/i',
                    '/LOAD_FILE\s*\(/i',
                ],
                'time_based_attacks' => [
                    '/BENCHMARK\s*\(/i',
                    '/SLEEP\s*\(/i',
                ],
                'information_disclosure' => [
                    '/INFORMATION_SCHEMA\./i',
                    '/mysql\./i',
                    '/performance_schema\./i',
                ],
                'sql_comments' => [
                    '/--\s/',
                    '/\/\*/',
                    '/\*\//',
                ],
                'nested_statements' => [
                    '/\(\s*(UPDATE|DELETE|INSERT|DROP|ALTER|CREATE)/i',
                ],
            ];

            foreach ($dangerousPatterns as $category => $patterns) {
                foreach ($patterns as $pattern) {
                    if (preg_match($pattern, $normalizedSql)) {
                        return [
                            'error' => 'Query analysis blocked: detected disallowed '
                                .str_replace('_', ' ', $category)
                                .'. Only simple SELECT queries are allowed.',
                            'suggestions' => [],
                        ];
                    }
                }
            }

            // EXPLAIN FORMAT=JSON requires query concatenation (MySQL doesn't support binding)
            // The validation above ensures only safe SELECT queries reach this point
            $explain = DB::select('EXPLAIN FORMAT=JSON '.$sql);
            $explainData = json_decode($explain[0]->EXPLAIN ?? '{}', true);

            return [
                'explain' => $explainData,
                'suggestions' => $this->generateSuggestions($explainData),
            ];
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
                'suggestions' => [],
            ];
        }
    }

    /**
     * Generate optimization suggestions from EXPLAIN output.
     */
    protected function generateSuggestions(array $explain): array
    {
        $suggestions = [];

        $queryBlock = $explain['query_block'] ?? [];

        // Check for table scans
        if (isset($queryBlock['table']['access_type']) &&
            $queryBlock['table']['access_type'] === 'ALL') {
            $suggestions[] = 'Full table scan detected. Consider adding an index.';
        }

        // Check for filesort
        if (! empty($queryBlock['ordering_operation']['using_filesort'])) {
            $suggestions[] = 'Query uses filesort. Consider adding an index for ORDER BY columns.';
        }

        // Check for temporary tables
        if (! empty($queryBlock['ordering_operation']['using_temporary_table'])) {
            $suggestions[] = 'Query creates temporary table. Consider optimizing GROUP BY or ORDER BY.';
        }

        return $suggestions;
    }

    /**
     * Get recommended MySQL 8.4 optimizations.
     *
     * @return array<string, array<string, string>>
     */
    public function getRecommendedSettings(): array
    {
        return [
            'innodb' => [
                'innodb_buffer_pool_size' => 'Set to 70-80% of available RAM for dedicated DB server',
                'innodb_log_file_size' => '256M recommended for write-heavy workloads',
                'innodb_flush_log_at_trx_commit' => '2 for better performance (1 for strict durability)',
                'innodb_flush_method' => 'O_DIRECT to avoid double buffering',
            ],
            'query_cache' => [
                'note' => 'Query cache removed in MySQL 8.0+. Use application-level caching instead.',
            ],
            'optimizer' => [
                'optimizer_switch' => 'Enable index_merge_intersection for better multi-index usage',
                'optimizer_prune_level' => '1 (default) for query plan pruning',
            ],
            'connections' => [
                'max_connections' => 'Set based on expected concurrent connections',
                'wait_timeout' => '300 seconds recommended for web applications',
                'interactive_timeout' => '300 seconds recommended',
            ],
        ];
    }
}
