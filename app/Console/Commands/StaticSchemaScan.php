<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class StaticSchemaScan extends Command
{
    protected $signature = 'db:static-scan
        {--path=app : Root path to scan (default: app)}
        {--migrations=database/migrations : Migrations path}
        {--fail : Exit with non-zero code if any issues found}';

    protected $description = 'Static scan for query columns not present in migrations (best-effort)';

    /** @var array<string, true> */
    private array $ignoreColumns = [
        'id' => true,
        'created_at' => true,
        'updated_at' => true,
        'deleted_at' => true,

        'count' => true,
        'total' => true,

        'asc' => true,
        'desc' => true,

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

    /** @var array<string, true> */
    private array $collectionMaterializers = [
        // Query -> Collection / Paginator-ish
        'get' => true,
        'pluck' => true,
        'cursor' => true,
        'paginate' => true,
        'simplepaginate' => true,
    ];

    /** @var array<string, true> */
    private array $collectionTransforms = [
        // Collection -> Collection (useful to propagate "this var is a Collection")
        'map' => true,
        'filter' => true,
        'groupby' => true,
        'keyby' => true,
        'sortby' => true,
        'sortbydesc' => true,
        'values' => true,
        'flatten' => true,
        'unique' => true,
        'tap' => true,
    ];

    public function handle(): int
    {
        $migrationsPath = base_path((string) $this->option('migrations'));
        $scanPath = base_path((string) $this->option('path'));

        if (!File::isDirectory($migrationsPath)) {
            $this->error("Migrations path not found: {$migrationsPath}");
            return 1;
        }
        if (!File::isDirectory($scanPath)) {
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

        $colRegexes = [
            // Types (single/double quotes)
            '/\$table->(?:string|text|longText|mediumText|integer|bigInteger|tinyInteger|smallInteger|mediumInteger|unsignedBigInteger|unsignedInteger|unsignedTinyInteger|unsignedSmallInteger|unsignedMediumInteger|boolean|date|dateTime|dateTimeTz|timestamp|timestampTz|time|decimal|double|float|json|jsonb|enum|set|uuid|ulid|char|binary|ipAddress|macAddress)\s*\(\s*[\'"]([^\'"]+)[\'"]/i',

            // Foreign ids
            '/\$table->foreignId\s*\(\s*[\'"]([^\'"]+)[\'"]/i',
            '/\$table->foreignUuid\s*\(\s*[\'"]([^\'"]+)[\'"]/i',
            '/\$table->foreignUlid\s*\(\s*[\'"]([^\'"]+)[\'"]/i',

            // Increments & explicit id('name')
            '/\$table->(?:increments|bigIncrements)\s*\(\s*[\'"]([^\'"]+)[\'"]/i',
            '/\$table->id\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/i',

            // Renames: keep the NEW name
            '/\$table->renameColumn\s*\(\s*[\'"][^\'"]+[\'"]\s*,\s*[\'"]([^\'"]+)[\'"]/i',
        ];

        $morphRegex = '/\$table->(?:morphs|nullableMorphs|uuidMorphs|nullableUuidMorphs|ulidMorphs|nullableUlidMorphs)\s*\(\s*[\'"]([^\'"]+)[\'"]/i';

        foreach ($files as $file) {
            $content = $file->getContents();

            // include Schema::create + Schema::table (important!)
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

            if (preg_match('/\b(?:timestamps|timestampsTz|nullableTimestamps)\s*\(/', $content)) {
                $columns['created_at'] = true;
                $columns['updated_at'] = true;
            }
            if (preg_match('/\b(?:softDeletes|softDeletesTz)\s*\(/', $content)) {
                $columns['deleted_at'] = true;
            }
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
            $statements = $this->splitIntoStatements($tokens);

            /** @var array<string, true> $collectionVars */
            $collectionVars = [];

            foreach ($statements as $stmt) {
                // 1) update known collection variables from assignments in this statement
                $this->learnCollectionVarsFromStatement($stmt, $collectionVars);

                // 2) scan builder-like calls in this statement (but skip Collection context)
                $this->scanStatementForUnknowns($stmt, $collectionVars, $schemaColumns, $aliases, $unknown, $seen, (string) $file->getPathname());
            }
        }

        return $unknown;
    }

    /**
     * @param array<int, mixed> $statementTokens
     * @param array<string, true> &$collectionVars
     */
    private function learnCollectionVarsFromStatement(array $statementTokens, array &$collectionVars): void
    {
        // Detect: $var = ...;
        // Mark $var as Collection if RHS:
        // - contains ->get()/->pluck()/->cursor()/->paginate() ...
        // - calls collect(...)
        // - OR starts from known collection var and applies a collection transform (map/filter/groupBy/etc)
        $paren = 0;
        $bracket = 0;
        $curly = 0;

        $lhsVar = null;
        $eqIndex = null;

        $n = count($statementTokens);
        for ($i = 0; $i < $n; $i++) {
            $t = $statementTokens[$i];

            if ($t === '(') { $paren++; continue; }
            if ($t === ')') { $paren = max(0, $paren - 1); continue; }
            if ($t === '[') { $bracket++; continue; }
            if ($t === ']') { $bracket = max(0, $bracket - 1); continue; }
            if ($t === '{') { $curly++; continue; }
            if ($t === '}') { $curly = max(0, $curly - 1); continue; }

            // Only consider top-level "="
            if ($paren === 0 && $bracket === 0 && $curly === 0 && $t === '=') {
                // Previous significant token must be T_VARIABLE
                $prev = $this->prevNonWhitespaceToken($statementTokens, $i - 1);
                if ($prev !== null && is_array($prev) && $prev[0] === T_VARIABLE) {
                    $lhsVar = ltrim((string) $prev[1], '$');
                    $eqIndex = $i;
                }
                break;
            }
        }

        if (!$lhsVar || $eqIndex === null) {
            return;
        }

        // Analyze RHS
        $rhs = array_slice($statementTokens, $eqIndex + 1);

        // collect(...)
        if ($this->rhsCallsCollect($rhs)) {
            $collectionVars[$lhsVar] = true;
            return;
        }

        // RHS contains builder materializer (->get etc)
        if ($this->rhsHasMaterializerCall($rhs)) {
            $collectionVars[$lhsVar] = true;
            return;
        }

        // RHS derived from known collection var with transforms
        $baseVar = $this->firstRhsVariable($rhs);
        if ($baseVar && isset($collectionVars[$baseVar]) && $this->rhsHasCollectionTransform($rhs)) {
            $collectionVars[$lhsVar] = true;
            return;
        }
    }

    /**
     * @param array<int, mixed> $statementTokens
     * @param array<string, true> $collectionVars
     * @param array<string, true> $schemaColumns
     * @param array<string, true> $aliases
     * @param array<int, array{0:string,1:string,2:string,3:int}> &$unknown
     * @param array<string, true> &$seen
     */
    private function scanStatementForUnknowns(
        array $statementTokens,
        array $collectionVars,
        array $schemaColumns,
        array $aliases,
        array &$unknown,
        array &$seen,
        string $fullPath
    ): void {
        $n = count($statementTokens);

        for ($i = 0; $i < $n; $i++) {
            if (!is_array($statementTokens[$i]) || $statementTokens[$i][0] !== T_OBJECT_OPERATOR) {
                continue;
            }

            $j = $i + 1;
            while ($j < $n && is_array($statementTokens[$j]) && in_array($statementTokens[$j][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                $j++;
            }

            if ($j >= $n || !is_array($statementTokens[$j]) || $statementTokens[$j][0] !== T_STRING) {
                continue;
            }

            $method = strtolower((string) $statementTokens[$j][1]);
            if (!isset($this->scanMethods[$method])) {
                continue;
            }

            // Find "("
            $k = $j + 1;
            while ($k < $n && is_array($statementTokens[$k]) && in_array($statementTokens[$k][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                $k++;
            }
            if ($k >= $n || $statementTokens[$k] !== '(') {
                continue;
            }

            // Collection context check (key fix)
            if ($this->isCollectionContextCall($statementTokens, $i, $method, $collectionVars)) {
                continue;
            }

            $candidates = $this->extractColumnsFromBuilderCall($statementTokens, $k, $method);

            foreach ($candidates as [$col, $line]) {
                $col = (string) $col;

                if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $col)) {
                    continue;
                }

                if (str_contains($col, '.') || str_contains($col, '->')) {
                    continue;
                }

                if (isset($this->ignoreColumns[$col])) {
                    continue;
                }

                if (isset($aliases[$col])) {
                    continue;
                }

                if (!isset($schemaColumns[$col])) {
                    $rel = $this->relativePath($fullPath);
                    $key = $rel.'|'.$method.'|'.$col.'|'.$line;

                    if (!isset($seen[$key])) {
                        $seen[$key] = true;
                        $unknown[] = [$rel, $method, $col, (int) $line];
                    }
                }
            }
        }
    }

    /**
     * The key heuristic:
     * - If receiver is a known Collection var -> ignore.
     * - If statement had ->get()/->pluck()/... BEFORE this call -> ignore (same-chain best-effort).
     */
    private function isCollectionContextCall(array $statementTokens, int $objectOpIndex, string $method, array $collectionVars): bool
    {
        // 1) Receiver variable known as collection: $items->groupBy(...)
        $prev = $this->prevNonWhitespaceToken($statementTokens, $objectOpIndex - 1);
        if ($prev !== null && is_array($prev) && $prev[0] === T_VARIABLE) {
            $var = ltrim((string) $prev[1], '$');
            if ($var !== '' && isset($collectionVars[$var])) {
                return true;
            }
        }

        // 2) Same statement: if a materializer happened before this call, treat as collection chain
        if ($this->statementHasMaterializerBefore($statementTokens, $objectOpIndex)) {
            return true;
        }

        // 3) Also: collect(...) before this call in same statement
        if ($this->statementHasCollectBefore($statementTokens, $objectOpIndex)) {
            return true;
        }

        return false;
    }

    private function statementHasMaterializerBefore(array $statementTokens, int $pos): bool
    {
        $n = count($statementTokens);
        $limit = min($pos, $n);

        for ($i = 0; $i < $limit; $i++) {
            if (!is_array($statementTokens[$i]) || $statementTokens[$i][0] !== T_OBJECT_OPERATOR) {
                continue;
            }

            $j = $i + 1;
            while ($j < $limit && is_array($statementTokens[$j]) && in_array($statementTokens[$j][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                $j++;
            }

            if ($j >= $limit || !is_array($statementTokens[$j]) || $statementTokens[$j][0] !== T_STRING) {
                continue;
            }

            $m = strtolower((string) $statementTokens[$j][1]);
            if (!isset($this->collectionMaterializers[$m])) {
                continue;
            }

            // ensure it's a call: next significant token is "("
            $k = $j + 1;
            while ($k < $limit && is_array($statementTokens[$k]) && in_array($statementTokens[$k][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                $k++;
            }
            if ($k < $limit && $statementTokens[$k] === '(') {
                return true;
            }
        }

        return false;
    }

    private function statementHasCollectBefore(array $statementTokens, int $pos): bool
    {
        for ($i = 0; $i < $pos; $i++) {
            $t = $statementTokens[$i];
            if (is_array($t) && $t[0] === T_STRING && strtolower((string) $t[1]) === 'collect') {
                $next = $this->nextNonWhitespaceToken($statementTokens, $i + 1);
                if ($next === '(') {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param array<int, mixed> $tokens
     * @return array<int, array<int, mixed>>
     */
    private function splitIntoStatements(array $tokens): array
    {
        $stmts = [];
        $current = [];

        $paren = 0;
        $bracket = 0;
        $curly = 0;

        foreach ($tokens as $t) {
            $current[] = $t;

            if ($t === '(') { $paren++; continue; }
            if ($t === ')') { $paren = max(0, $paren - 1); continue; }
            if ($t === '[') { $bracket++; continue; }
            if ($t === ']') { $bracket = max(0, $bracket - 1); continue; }
            if ($t === '{') { $curly++; continue; }
            if ($t === '}') { $curly = max(0, $curly - 1); continue; }

            if ($t === ';' && $paren === 0 && $bracket === 0 && $curly === 0) {
                $stmts[] = $current;
                $current = [];
            }
        }

        if (!empty($current)) {
            $stmts[] = $current;
        }

        return $stmts;
    }

    private function rhsCallsCollect(array $rhsTokens): bool
    {
        $n = count($rhsTokens);
        for ($i = 0; $i < $n; $i++) {
            $t = $rhsTokens[$i];
            if (is_array($t) && $t[0] === T_STRING && strtolower((string) $t[1]) === 'collect') {
                $next = $this->nextNonWhitespaceToken($rhsTokens, $i + 1);
                if ($next === '(') {
                    return true;
                }
            }
        }
        return false;
    }

    private function rhsHasMaterializerCall(array $rhsTokens): bool
    {
        $n = count($rhsTokens);
        for ($i = 0; $i < $n; $i++) {
            if (!is_array($rhsTokens[$i]) || $rhsTokens[$i][0] !== T_OBJECT_OPERATOR) {
                continue;
            }

            $j = $i + 1;
            while ($j < $n && is_array($rhsTokens[$j]) && in_array($rhsTokens[$j][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                $j++;
            }

            if ($j >= $n || !is_array($rhsTokens[$j]) || $rhsTokens[$j][0] !== T_STRING) {
                continue;
            }

            $m = strtolower((string) $rhsTokens[$j][1]);
            if (!isset($this->collectionMaterializers[$m])) {
                continue;
            }

            $k = $j + 1;
            while ($k < $n && is_array($rhsTokens[$k]) && in_array($rhsTokens[$k][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                $k++;
            }

            if ($k < $n && $rhsTokens[$k] === '(') {
                return true;
            }
        }

        return false;
    }

    private function firstRhsVariable(array $rhsTokens): ?string
    {
        foreach ($rhsTokens as $t) {
            if (is_array($t) && in_array($t[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }
            if (is_array($t) && $t[0] === T_VARIABLE) {
                return ltrim((string) $t[1], '$');
            }
            break;
        }
        return null;
    }

    private function rhsHasCollectionTransform(array $rhsTokens): bool
    {
        $n = count($rhsTokens);
        for ($i = 0; $i < $n; $i++) {
            if (!is_array($rhsTokens[$i]) || $rhsTokens[$i][0] !== T_OBJECT_OPERATOR) {
                continue;
            }

            $j = $i + 1;
            while ($j < $n && is_array($rhsTokens[$j]) && in_array($rhsTokens[$j][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                $j++;
            }

            if ($j >= $n || !is_array($rhsTokens[$j]) || $rhsTokens[$j][0] !== T_STRING) {
                continue;
            }

            $m = strtolower((string) $rhsTokens[$j][1]);
            if (!isset($this->collectionTransforms[$m])) {
                continue;
            }

            $k = $j + 1;
            while ($k < $n && is_array($rhsTokens[$k]) && in_array($rhsTokens[$k][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                $k++;
            }

            if ($k < $n && $rhsTokens[$k] === '(') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, mixed> $tokens
     * @return array<int, array{0:string,1:int}>
     */
    private function extractColumnsFromBuilderCall(array $tokens, int $openParenIndex, string $method): array
    {
        $results = [];
        $n = count($tokens);

        // where/orderBy/etc: FIRST arg must be string literal
        if (isset($this->firstArgOnlyMethods[$method])) {
            $t = $this->firstSignificantTokenAfter($tokens, $openParenIndex + 1);
            if ($t !== null && is_array($t) && $t[0] === T_CONSTANT_ENCAPSED_STRING) {
                $results[] = [$this->unquotePhpString((string) $t[1]), (int) $t[2]];
            }
            return $results;
        }

        // select/addSelect/groupBy: capture multi columns safely
        $parenDepth = 1;
        $bracketDepth = 0;
        $curlyDepth = 0;

        for ($i = $openParenIndex + 1; $i < $n; $i++) {
            $t = $tokens[$i];

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
                continue;
            }

            if (!is_array($t) || $t[0] !== T_CONSTANT_ENCAPSED_STRING) {
                continue;
            }

            // Skip arrow-fn return strings and associative keys/values: anything adjacent to "=>"
            $prev = $this->prevNonWhitespaceTokenText($tokens, $i - 1);
            $next = $this->nextNonWhitespaceTokenText($tokens, $i + 1);
            if ($prev === '=>' || $next === '=>') {
                continue;
            }

            $isDirectArg = ($parenDepth === 1 && $bracketDepth === 0 && ($prev === '(' || $prev === ','));
            $isArrayValue = ($parenDepth === 1 && $bracketDepth === 1 && ($prev === '[' || $prev === ','));

            if (!($isDirectArg || $isArrayValue)) {
                continue;
            }

            $results[] = [$this->unquotePhpString((string) $t[1]), (int) $t[2]];
        }

        return $results;
    }

    /**
     * @return array<string, true>
     */
    private function extractAliasColumnsFromContent(string $content): array
    {
        $aliases = [];

        // Raw aliases: "... as alias"
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

        // selectSub($query, 'alias')
        if (preg_match_all('/selectSub\(\s*.+?\s*,\s*[\'"]([A-Za-z_][A-Za-z0-9_]*)[\'"]\s*\)/si', $content, $m)) {
            foreach ($m[1] as $a) {
                $aliases[(string) $a] = true;
            }
        }

        // withCount aliases: relation_count
        if (preg_match_all('/withCount\(\s*(?:\[\s*[\'"]([A-Za-z0-9_]+)[\'"]\s*=>|[\'"]([A-Za-z0-9_]+)[\'"])/s', $content, $m)) {
            foreach ($m[1] as $i => $k) {
                $rel = $k ?: ($m[2][$i] ?? null);
                if ($rel) {
                    $aliases[$rel . '_count'] = true;
                }
            }
        }

        // withSum/withAvg/withMin/withMax aliases
        $aggMethods = ['withSum' => 'sum', 'withAvg' => 'avg', 'withMin' => 'min', 'withMax' => 'max'];
        foreach ($aggMethods as $method => $suffix) {
            $p1 = '/'.$method.'\(\s*\[\s*[\'"]([A-Za-z0-9_]+)[\'"]\s*=>.*?\]\s*,\s*[\'"]([A-Za-z0-9_]+)[\'"]\s*\)/si';
            if (preg_match_all($p1, $content, $m1)) {
                foreach ($m1[1] as $i => $rel) {
                    $col = $m1[2][$i] ?? null;
                    if ($col) {
                        $aliases[$rel.'_'.$suffix.'_'.$col] = true;
                    }
                }
            }

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

    private function prevNonWhitespaceToken(array $tokens, int $start)
    {
        for ($i = $start; $i >= 0; $i--) {
            $t = $tokens[$i];
            if (is_array($t) && in_array($t[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }
            return $t;
        }
        return null;
    }

    private function nextNonWhitespaceToken(array $tokens, int $start)
    {
        $n = count($tokens);
        for ($i = $start; $i < $n; $i++) {
            $t = $tokens[$i];
            if (is_array($t) && in_array($t[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }
            return is_array($t) ? $t[1] : $t;
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
            return is_array($t) ? (string) $t[1] : (string) $t;
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
            return is_array($t) ? (string) $t[1] : (string) $t;
        }
        return null;
    }
}
