<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * StaticSchemaScan
 *
 * Best-effort static scan to catch common "unknown column" bugs before runtime.
 *
 * - Parses database/migrations for Schema::create(...) AND Schema::table(...).
 * - Scans app/**/*.php for common builder calls.
 * - Reports referenced columns that don't exist in parsed schema.
 *
 * False-positive controls:
 * - For where/orderBy/...: only if FIRST argument is a quoted string literal.
 * - For select/addSelect/groupBy: only captures "direct arguments" and array values,
 *   skips arrow-fn return strings and associative array keys.
 * - Ignores dotted columns and JSON paths.
 * - Treats raw aliases / withCount & aggregates aliases / selectSub aliases as known.
 */
class StaticSchemaScan extends Command
{
    protected $signature = 'db:static-scan
        {--path=app : Root path to scan (default: app)}
        {--migrations=database/migrations : Migrations path}
        {--fail : Exit with non-zero code if any issues found}';

    protected $description = 'Static scan for query columns not present in migrations (best-effort)';

    /** @var array<string, true> */
    private array $ignoreColumns = [
        // Common timestamps
        'id' => true,
        'created_at' => true,
        'updated_at' => true,
        'deleted_at' => true,

        // Common computed / report labels that often appear as aliases
        'count' => true,
        'total' => true,

        // Common ordering strings (avoid noise if someone passes them incorrectly)
        'asc' => true,
        'desc' => true,

        // Spatie permissions (package migrations)
        'guard_name' => true,
    ];

    /** @var array<string, true> */
    private array $scanMethods = [
        'where' => true,
        'orwhere' => true,
        'wherein' => true,
        'wherenotin' => true,
        'wherenull' => true,
        'wherenotnull' => true,
        'wherecolumn' => true,

        'orderby' => true,
        'orderbydesc' => true,
        'groupby' => true,

        'select' => true,
        'addselect' => true,
    ];

    /** @var array<string, true> */
    private array $firstArgOnlyMethods = [
        'where' => true,
        'orwhere' => true,
        'wherein' => true,
        'wherenotin' => true,
        'wherenull' => true,
        'wherenotnull' => true,
        'wherecolumn' => true,

        'orderby' => true,
        'orderbydesc' => true,
    ];

    public function handle(): int
    {
        $migrationsPath = base_path((string) $this->option('migrations'));
        $scanPath = base_path((string) $this->option('path'));

        if (! File::isDirectory($migrationsPath)) {
            $this->error("Migrations path not found: {$migrationsPath}");
            return 1;
        }
        if (! File::isDirectory($scanPath)) {
            $this->error("Scan path not found: {$scanPath}");
            return 1;
        }

        $schemaColumns = $this->parseSchemaColumns($migrationsPath);
        $unknown = $this->scanForUnknownColumns($scanPath, $schemaColumns);

        if (empty($unknown)) {
            $this->info('✅ StaticSchemaScan: no unknown columns found (within scan heuristics).');
            return 0;
        }

        $this->warn('⚠️ StaticSchemaScan found possible unknown columns:');
        foreach ($unknown as $row) {
            [$file, $method, $column, $line] = $row;
            $this->line("- {$file}:{$line}  {$method}('{$column}')");
        }

        return $this->option('fail') ? 2 : 0;
    }

    /**
     * @return array<string, true>
     */
    private function parseSchemaColumns(string $migrationsPath): array
    {
        $columns = [];
        $files = File::allFiles($migrationsPath);

        // More complete best-effort regexes (single OR double quotes).
        $colRegexes = [
            // Common types
            '/\$table->(?:string|text|longText|mediumText|integer|bigInteger|tinyInteger|smallInteger|mediumInteger|unsignedBigInteger|unsignedInteger|unsignedTinyInteger|unsignedSmallInteger|unsignedMediumInteger|boolean|date|dateTime|dateTimeTz|timestamp|timestampTz|time|decimal|double|float|json|jsonb|enum|set|uuid|ulid|char|binary|ipAddress|macAddress)\s*\(\s*[\'"]([^\'"]+)[\'"]/i',

            // Foreign ids
            '/\$table->foreignId\s*\(\s*[\'"]([^\'"]+)[\'"]/i',
            '/\$table->foreignUuid\s*\(\s*[\'"]([^\'"]+)[\'"]/i',
            '/\$table->foreignUlid\s*\(\s*[\'"]([^\'"]+)[\'"]/i',

            // Increments & explicit id('name')
            '/\$table->(?:increments|bigIncrements)\s*\(\s*[\'"]([^\'"]+)[\'"]/i',
            '/\$table->id\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/i',

            // Renames: keep the NEW name to reduce false positives
            '/\$table->renameColumn\s*\(\s*[\'"][^\'"]+[\'"]\s*,\s*[\'"]([^\'"]+)[\'"]/i',
        ];

        $morphRegex = '/\$table->(?:morphs|nullableMorphs|uuidMorphs|nullableUuidMorphs|ulidMorphs|nullableUlidMorphs)\s*\(\s*[\'"]([^\'"]+)[\'"]/i';

        foreach ($files as $file) {
            $content = $file->getContents();

            // parse create + table (important to avoid false unknowns)
            if (!str_contains($content, 'Schema::create') && !str_contains($content, 'Schema::table')) {
                continue;
            }

            foreach ($colRegexes as $regex) {
                if (preg_match_all($regex, $content, $m)) {
                    foreach ($m[1] as $col) {
                        $columns[(string) $col] = true;
                    }
                }
            }

            if (preg_match_all($morphRegex, $content, $morphs)) {
                foreach ($morphs[1] as $base) {
                    $columns[$base . '_id'] = true;
                    $columns[$base . '_type'] = true;
                }
            }

            // timestamps variations
            if (preg_match('/\b(?:timestamps|timestampsTz|nullableTimestamps)\s*\(/', $content)) {
                $columns['created_at'] = true;
                $columns['updated_at'] = true;
            }

            // soft deletes variations
            if (preg_match('/\b(?:softDeletes|softDeletesTz)\s*\(/', $content)) {
                $columns['deleted_at'] = true;
            }

            // remember token
            if (preg_match('/\brememberToken\s*\(/', $content)) {
                $columns['remember_token'] = true;
            }
        }

        return $columns;
    }

    /**
     * @param array<string, true> $schemaColumns
     * @return array<int, array{0:string,1:string,2:string,3:int}>
     */
    private function scanForUnknownColumns(string $scanPath, array $schemaColumns): array
    {
        $unknown = [];
        $seen = [];

        foreach (File::allFiles($scanPath) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $content = $file->getContents();
            $aliases = $this->extractAliasColumnsFromContent($content);

            $tokens = token_get_all($content);
            $n = count($tokens);

            for ($i = 0; $i < $n; $i++) {
                // Look for "->"
                if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_OBJECT_OPERATOR) {
                    continue;
                }

                // Next meaningful token should be method name
                $j = $i + 1;
                while ($j < $n && is_array($tokens[$j]) && in_array($tokens[$j][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                    $j++;
                }

                if ($j >= $n || !is_array($tokens[$j]) || $tokens[$j][0] !== T_STRING) {
                    continue;
                }

                $method = strtolower($tokens[$j][1]);
                if (!isset($this->scanMethods[$method])) {
                    continue;
                }

                // Find "("
                $k = $j + 1;
                while ($k < $n && is_array($tokens[$k]) && in_array($tokens[$k][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                    $k++;
                }

                if ($k >= $n || $tokens[$k] !== '(') {
                    continue;
                }

                $candidates = $this->extractColumnsFromBuilderCall($tokens, $k, $method);

                foreach ($candidates as [$col, $line]) {
                    $col = (string) $col;

                    // Only treat simple identifiers as columns.
                    if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $col)) {
                        continue;
                    }

                    // Ignore dotted columns and JSON paths
                    if (str_contains($col, '.') || str_contains($col, '->')) {
                        continue;
                    }

                    if (isset($this->ignoreColumns[$col])) {
                        continue;
                    }

                    // Treat aliases as known
                    if (isset($aliases[$col])) {
                        continue;
                    }

                    if (!isset($schemaColumns[$col])) {
                        $rel = $this->relativePath((string) $file->getPathname());
                        $key = $rel.'|'.$method.'|'.$col.'|'.$line;

                        if (!isset($seen[$key])) {
                            $seen[$key] = true;
                            $unknown[] = [$rel, $method, $col, (int) $line];
                        }
                    }
                }
            }
        }

        return $unknown;
    }

    /**
     * Extract column names from a builder call starting at the "(" token.
     *
     * Returns list of [column, line].
     *
     * False-positive controls:
     * - first-arg-only methods: require FIRST arg to be a quoted string literal.
     * - multi-column methods: capture only "direct arguments" at top level, and array VALUES (not keys).
     *   Also skips strings that appear after T_DOUBLE_ARROW (arrow fn return / associative keys/values).
     *
     * @param array<int, mixed> $tokens
     * @return array<int, array{0:string,1:int}>
     */
    private function extractColumnsFromBuilderCall(array $tokens, int $openParenIndex, string $method): array
    {
        $results = [];
        $n = count($tokens);

        // first-arg-only: enforce "FIRST arg is string literal"
        if (isset($this->firstArgOnlyMethods[$method])) {
            $t = $this->firstSignificantTokenAfter($tokens, $openParenIndex + 1);
            if ($t !== null && is_array($t) && $t[0] === T_CONSTANT_ENCAPSED_STRING) {
                $results[] = [$this->unquotePhpString($t[1]), (int) $t[2]];
            }
            return $results;
        }

        // multi: select/addSelect/groupBy
        $parenDepth = 1;
        $bracketDepth = 0;
        $curlyDepth = 0;

        for ($i = $openParenIndex + 1; $i < $n; $i++) {
            $t = $tokens[$i];

            // Depth tracking
            if ($t === '(') { $parenDepth++; continue; }
            if ($t === ')') {
                $parenDepth--;
                if ($parenDepth === 0) break;
                continue;
            }
            if ($t === '[') { $bracketDepth++; continue; }
            if ($t === ']') { $bracketDepth = max(0, $bracketDepth - 1); continue; }
            if ($t === '{') { $curlyDepth++; continue; }
            if ($t === '}') { $curlyDepth = max(0, $curlyDepth - 1); continue; }

            if ($curlyDepth > 0) {
                // skip strings inside closure bodies
                continue;
            }

            if (!is_array($t) || $t[0] !== T_CONSTANT_ENCAPSED_STRING) {
                continue;
            }

            // Only consider top-level args and simple array values
            // - direct arguments: previous significant token is '(' or ','
            // - array values: bracketDepth==1 and previous significant token is '[' or ','
            // Additionally: SKIP strings that are part of arrow fn return or associative (=>)
            $prev = $this->prevNonWhitespaceTokenText($tokens, $i - 1);
            $next = $this->nextNonWhitespaceTokenText($tokens, $i + 1);

            // Skip anything immediately after/before "=>" (arrow fn return, associative arrays)
            if ($prev === '=>' || $next === '=>') {
                continue;
            }

            $isDirectArg = ($parenDepth === 1 && $bracketDepth === 0 && ($prev === '(' || $prev === ','));
            $isArrayValue = ($parenDepth === 1 && $bracketDepth === 1 && ($prev === '[' || $prev === ','));

            if (!($isDirectArg || $isArrayValue)) {
                continue;
            }

            $results[] = [$this->unquotePhpString($t[1]), (int) $t[2]];
        }

        return $results;
    }

    /**
     * Best-effort extraction of query aliases defined via selectRaw/DB::raw and withCount/withSum/etc + selectSub alias.
     *
     * @return array<string, true>
     */
    private function extractAliasColumnsFromContent(string $content): array
    {
        $aliases = [];

        // 1) Raw SQL aliases: "... as alias"
        $rawPatterns = [
            "/selectRaw\\(\\s*['\"](.+?)['\"]\\s*(?:,\\s*\\[.*?\\])?\\)\\s*;?/si",
            "/DB::raw\\(\\s*['\"](.+?)['\"]\\s*\\)/si",
        ];

        foreach ($rawPatterns as $p) {
            if (preg_match_all($p, $content, $m)) {
                foreach ($m[1] as $expr) {
                    if (preg_match_all('/\bas\s+([A-Za-z_][A-Za-z0-9_]*)\b/i', (string) $expr, $am)) {
                        foreach ($am[1] as $a) {
                            $aliases[(string) $a] = true;
                        }
                    }
                }
            }
        }

        // 2) selectSub($query, 'alias')
        if (preg_match_all('/selectSub\(\s*.+?\s*,\s*[\'"]([A-Za-z_][A-Za-z0-9_]*)[\'"]\s*\)/si', $content, $m)) {
            foreach ($m[1] as $a) {
                $aliases[(string) $a] = true;
            }
        }

        // 3) withCount aliases: relation_count
        if (preg_match_all('/withCount\(\s*(?:\[\s*[\'"]([A-Za-z0-9_]+)[\'"]\s*=>|[\'"]([A-Za-z0-9_]+)[\'"])/s', $content, $m)) {
            foreach ($m[1] as $i => $k) {
                $rel = $k ?: ($m[2][$i] ?? null);
                if ($rel) {
                    $aliases[$rel . '_count'] = true;
                }
            }
        }

        // 4) withSum/withAvg/withMin/withMax aliases: relation_sum_column ...
        $aggMethods = ['withSum' => 'sum', 'withAvg' => 'avg', 'withMin' => 'min', 'withMax' => 'max'];
        foreach ($aggMethods as $method => $suffix) {
            // Array form: withSum(['sales' => fn() => ...], 'total_amount')
            $p1 = '/'.$method.'\(\s*\[\s*[\'"]([A-Za-z0-9_]+)[\'"]\s*=>.*?\]\s*,\s*[\'"]([A-Za-z0-9_]+)[\'"]\s*\)/si';
            if (preg_match_all($p1, $content, $m1)) {
                foreach ($m1[1] as $i => $rel) {
                    $col = $m1[2][$i] ?? null;
                    if ($col) {
                        $aliases[$rel.'_'.$suffix.'_'.$col] = true;
                    }
                }
            }

            // String form: withSum('sales', 'total_amount')
            $p2 = '/'.$method.'\(\s*[\'"]([A-Za-z0-9_]+)[\'"]\s*,\s*[\'"]([A-Za-z0-9_]+)[\'"]\s*\)/si';
            if (preg_match_all($p2, $content, $m2)) {
                foreach ($m2[1] as $i => $rel) {
                    $col = $m2[2][$i] ?? null;
                    if ($col) {
                        $aliases[$rel.'_'.$suffix.'_'.$col] = true;
                    }
                }
            }
        }

        return $aliases;
    }

    private function unquotePhpString(string $s): string
    {
        $q = $s[0] ?? '';
        if (($q === "'" || $q === '"') && str_ends_with($s, $q)) {
            $s = substr($s, 1, -1);
        }
        return stripcslashes($s);
    }

    private function relativePath(string $path): string
    {
        $base = base_path();
        $pathNorm = str_replace('\\', '/', $path);
        $baseNorm = str_replace('\\', '/', $base);

        if (str_starts_with($pathNorm, $baseNorm . '/')) {
            return substr($pathNorm, strlen($baseNorm) + 1);
        }

        return $pathNorm;
    }

    /**
     * @param array<int, mixed> $tokens
     * @return array{0:int,1:string,2:int}|string|null
     */
    private function firstSignificantTokenAfter(array $tokens, int $start)
    {
        $n = count($tokens);
        for ($i = $start; $i < $n; $i++) {
            $t = $tokens[$i];
            if (is_array($t) && in_array($t[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }
            return $t;
        }
        return null;
    }

    private function prevNonWhitespaceTokenText(array $tokens, int $start): ?string
    {
        for ($i = $start; $i >= 0; $i--) {
            $t = $tokens[$i];
            if (is_array($t) && in_array($t[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }
            return is_array($t) ? $t[1] : (string) $t;
        }
        return null;
    }

    private function nextNonWhitespaceTokenText(array $tokens, int $start): ?string
    {
        $n = count($tokens);
        for ($i = $start; $i < $n; $i++) {
            $t = $tokens[$i];
            if (is_array($t) && in_array($t[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }
            return is_array($t) ? $t[1] : (string) $t;
        }
        return null;
    }
}
