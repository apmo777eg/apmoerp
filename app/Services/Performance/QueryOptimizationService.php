<?php

declare(strict_types=1);

namespace App\Services\Performance;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * QueryOptimizationService - Advanced Query Performance Optimization
 *
 * PURPOSE: Optimize database queries with caching, indexing recommendations, and query analysis
 * FEATURES:
 *   - Intelligent query result caching with TTL management
 *   - Query performance monitoring and slow query detection
 *   - Missing index detection and recommendations
 *   - N+1 query prevention strategies
 *   - Query result prefetching
 */
class QueryOptimizationService
{
    /**
     * Execute query with automatic caching.
     * Caches query results to reduce database load.
     *
     * @param  string  $cacheKey  Unique cache identifier
     * @param  callable  $query  Query closure to execute
     * @param  int  $ttl  Cache time-to-live in seconds (default: 5 minutes)
     * @return mixed Query results
     */
    public function cachedQuery(string $cacheKey, callable $query, int $ttl = 300)
    {
        return Cache::remember($cacheKey, $ttl, $query);
    }

    /**
     * Execute query with tags for selective cache invalidation.
     * Allows clearing specific groups of cached queries.
     *
     * @param  array  $tags  Cache tags for grouping
     * @param  string  $cacheKey  Unique cache identifier
     * @param  callable  $query  Query closure to execute
     * @param  int  $ttl  Cache time-to-live in seconds
     * @return mixed Query results
     */
    public function taggedCachedQuery(array $tags, string $cacheKey, callable $query, int $ttl = 300)
    {
        return Cache::tags($tags)->remember($cacheKey, $ttl, $query);
    }

    /**
     * Invalidate cache by tags.
     * Clears all cached queries with specified tags.
     *
     * @param  array  $tags  Tags to invalidate
     */
    public function invalidateCacheTags(array $tags): void
    {
        Cache::tags($tags)->flush();
    }

    /**
     * Get slow queries from database logs.
     * Analyzes query performance and identifies bottlenecks.
     *
     * @param  int  $thresholdMs  Query execution time threshold in milliseconds
     * @param  int  $limit  Maximum number of slow queries to return
     * @return array Slow queries with execution times
     */
    public function getSlowQueries(int $thresholdMs = 1000, int $limit = 20): array
    {
        // Check if query logging is enabled
        $logEnabled = config('database.connections.mysql.options.'.\PDO::ATTR_EMULATE_PREPARES, false);

        if (! $logEnabled) {
            return [
                'warning' => 'Query logging is not enabled. Enable it in database configuration.',
                'queries' => [],
            ];
        }

        $slowQueries = DB::getQueryLog();

        // Filter queries slower than threshold
        $filtered = array_filter($slowQueries, fn ($q) => $q['time'] >= $thresholdMs);

        // Sort by execution time descending
        usort($filtered, fn ($a, $b) => $b['time'] <=> $a['time']);

        return array_slice($filtered, 0, $limit);
    }

    /**
     * Analyze table and suggest missing indexes.
     * Examines query patterns and recommends indexes for performance.
     *
     * SECURITY NOTE: The $tableName is validated against SQL injection using assertValidIdentifier()
     * before any SQL operations. The validation ensures only valid table names
     * (alphanumeric, underscore, with optional schema prefix) are accepted.
     * The grammar's wrapTable() provides additional escaping.
     *
     * @param  string  $tableName  Table to analyze
     * @return array Index recommendations
     */
    public function suggestIndexes(string $tableName): array
    {
        $this->assertValidIdentifier($tableName);

        if (DB::getDriverName() !== 'mysql') {
            throw new InvalidArgumentException('Index suggestions are only supported on MySQL-compatible drivers.');
        }

        $wrappedTable = DB::getQueryGrammar()->wrapTable($tableName);
        $recommendations = [];

        // Get existing indexes
        $existingIndexes = DB::select("SHOW INDEXES FROM {$wrappedTable}");
        $indexedColumns = array_column($existingIndexes, 'Column_name');

        // Get table columns
        $columns = DB::select("SHOW COLUMNS FROM {$wrappedTable}");

        foreach ($columns as $column) {
            $columnName = $column->Field;

            // Skip if already indexed
            if (in_array($columnName, $indexedColumns)) {
                continue;
            }

            // Recommend indexes for common patterns
            if (str_ends_with($columnName, '_id') && ! in_array($columnName, $indexedColumns)) {
                $recommendations[] = [
                    'column' => $columnName,
                    'type' => 'foreign_key',
                    'reason' => 'Foreign key column should be indexed for join performance',
                    'sql' => "ALTER TABLE {$tableName} ADD INDEX idx_{$tableName}_{$columnName} ({$columnName})",
                ];
            }

            if (in_array($columnName, ['status', 'type', 'state', 'is_active']) && ! in_array($columnName, $indexedColumns)) {
                $recommendations[] = [
                    'column' => $columnName,
                    'type' => 'filter',
                    'reason' => 'Frequently filtered column should be indexed',
                    'sql' => "ALTER TABLE {$tableName} ADD INDEX idx_{$tableName}_{$columnName} ({$columnName})",
                ];
            }

            if (in_array($columnName, ['created_at', 'updated_at', 'deleted_at', 'posted_at']) && ! in_array($columnName, $indexedColumns)) {
                $recommendations[] = [
                    'column' => $columnName,
                    'type' => 'datetime',
                    'reason' => 'Date columns used in sorting and filtering should be indexed',
                    'sql' => "ALTER TABLE {$tableName} ADD INDEX idx_{$tableName}_{$columnName} ({$columnName})",
                ];
            }
        }

        return $recommendations;
    }

    /**
     * Optimize table by analyzing and reorganizing data.
     * Defragments table and rebuilds indexes for better performance.
     *
     * SECURITY NOTE: The $tableName is validated against SQL injection using assertValidIdentifier()
     * before any SQL operations. The grammar's wrapTable() provides additional escaping.
     *
     * @param  string  $tableName  Table to optimize
     * @return array Optimization results
     */
    public function optimizeTable(string $tableName): array
    {
        try {
            $this->assertValidIdentifier($tableName);

            $table = DB::getQueryGrammar()->wrapTable($tableName);
            $optimizeStatement = match (DB::getDriverName()) {
                'pgsql' => "ANALYZE VERBOSE {$table}",
                'sqlite' => "ANALYZE {$table}",
                default => "OPTIMIZE TABLE {$table}",
            };

            DB::statement($optimizeStatement);

            return [
                'success' => true,
                'table' => $tableName,
                'message' => "Table {$tableName} optimized successfully",
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'table' => $tableName,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Analyze query execution plan.
     * Uses EXPLAIN to analyze query performance.
     *
     * SECURITY NOTE: The $query is validated by assertExplainableQuery() which:
     * - Removes trailing semicolons
     * - Rejects empty queries
     * - Blocks multiple statements (no stacked queries)
     * - Only allows SELECT/INSERT/UPDATE/DELETE statements
     * This prevents SQL injection via EXPLAIN statement stacking.
     *
     * @param  string  $query  SQL query to analyze
     * @return array Execution plan details
     */
    public function explainQuery(string $query): array
    {
        try {
            $trimmedQuery = trim($query);
            $this->assertExplainableQuery($trimmedQuery);

            $keyword = DB::getDriverName() === 'sqlite' ? 'EXPLAIN QUERY PLAN' : 'EXPLAIN';
            $explainResults = DB::select("{$keyword} {$trimmedQuery}");

            return [
                'success' => true,
                'plan' => $explainResults,
                'recommendations' => $this->analyzeExplainResults($explainResults),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Analyze EXPLAIN results and provide recommendations.
     *
     * @param  array  $explainResults  EXPLAIN query results
     * @return array Performance recommendations
     */
    protected function analyzeExplainResults(array $explainResults): array
    {
        $recommendations = [];

        foreach ($explainResults as $row) {
            // Check for table scans
            if ($row->type === 'ALL') {
                $recommendations[] = [
                    'severity' => 'high',
                    'table' => $row->table,
                    'issue' => 'Full table scan detected',
                    'suggestion' => "Add index on filtered columns for table: {$row->table}",
                ];
            }

            // Check for filesort
            if (isset($row->Extra) && str_contains($row->Extra, 'Using filesort')) {
                $recommendations[] = [
                    'severity' => 'medium',
                    'table' => $row->table,
                    'issue' => 'Filesort operation detected',
                    'suggestion' => "Add index on ORDER BY columns for table: {$row->table}",
                ];
            }

            // Check for temp table
            if (isset($row->Extra) && str_contains($row->Extra, 'Using temporary')) {
                $recommendations[] = [
                    'severity' => 'medium',
                    'table' => $row->table,
                    'issue' => 'Temporary table created',
                    'suggestion' => "Optimize query or add composite index for table: {$row->table}",
                ];
            }
        }

        return $recommendations;
    }

    /**
     * Validate table or schema.table identifier to avoid SQL injection.
     */
    protected function assertValidIdentifier(string $identifier): void
    {
        if (! preg_match('/^[A-Za-z_][A-Za-z0-9_]*(\.[A-Za-z_][A-Za-z0-9_]*)?$/', $identifier)) {
            throw new InvalidArgumentException('Invalid table name provided for optimization.');
        }
    }

    /**
     * Ensure the query passed to EXPLAIN is a single data statement without stacked statements.
     */
    protected function assertExplainableQuery(string &$query): void
    {
        $query = rtrim($query, ';');

        if ($query === '') {
            throw new InvalidArgumentException('Query cannot be empty.');
        }

        if (str_contains($query, ';')) {
            throw new InvalidArgumentException('Multiple statements are not allowed in EXPLAIN.');
        }

        if (! preg_match('/^(select|insert|update|delete)\s/i', $query)) {
            throw new InvalidArgumentException('Only SELECT/INSERT/UPDATE/DELETE statements can be explained.');
        }
    }

    /**
     * Prefetch related data to prevent N+1 queries.
     * Eagerly loads relationships for collection.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $collection  Collection to prefetch for
     * @param  array  $relations  Relations to load
     * @return \Illuminate\Database\Eloquent\Collection Collection with loaded relations
     */
    public function prefetchRelations($collection, array $relations)
    {
        return $collection->load($relations);
    }

    /**
     * Get cache statistics.
     * Returns cache hit/miss rates and memory usage.
     *
     * @return array Cache performance metrics
     */
    public function getCacheStats(): array
    {
        // Note: Actual implementation depends on cache driver
        // This is a placeholder for Redis stats
        try {
            // Check if Redis driver is configured
            $store = Cache::getStore();
            if (!$store instanceof \Illuminate\Cache\RedisStore) {
                return [
                    'driver' => config('cache.default'),
                    'message' => 'Detailed cache stats only available for Redis driver',
                ];
            }

            $redis = $store->connection();
            $info = $redis->info();

            return [
                'hits' => $info['keyspace_hits'] ?? 0,
                'misses' => $info['keyspace_misses'] ?? 0,
                'hit_rate' => $this->calculateHitRate($info['keyspace_hits'] ?? 0, $info['keyspace_misses'] ?? 0),
                'memory_used' => $info['used_memory_human'] ?? 'N/A',
                'keys' => $info['db0']['keys'] ?? 0,
            ];
        } catch (\Exception $e) {
            return [
                'error' => 'Cache stats not available',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Calculate cache hit rate percentage.
     *
     * @param  int  $hits  Cache hits
     * @param  int  $misses  Cache misses
     * @return string Hit rate percentage using bcmath
     */
    protected function calculateHitRate(int $hits, int $misses): string
    {
        $total = bcadd((string) $hits, (string) $misses, 0);

        if (bccomp($total, '0', 0) === 0) {
            return '0.00%';
        }

        $rate = bcdiv(bcmul((string) $hits, '100', 2), $total, 2);

        return $rate.'%';
    }
}
