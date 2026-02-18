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
 * What it does:
 *  - Parses database/migrations for Schema::create(...) blocks and extracts column names.
 *  - Scans app/**/*.php for common query builders: where/orderBy/groupBy/select/addSelect.
 *  - Reports any referenced columns that are not present in any parsed schema.
 *
 * Notes:
 *  - It intentionally ignores dotted columns (table.column) because those may refer to joins.
 *  - It intentionally ignores JSON paths like data->type and payload->meta->source.
 *  - It will also flag package-managed tables (e.g., spatie roles/permissions) unless you add them
 *    to the ignore list.
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

        // Common computed / selectRaw aliases in reports
        'hour' => true,
        'segment' => true,
        'branch_name' => true,
        'category_name' => true,
        'activities_count' => true,
        'total_qty' => true,
        'margin_percent' => true,
        'days_since_purchase' => true,
        'days_since_sale' => true,

        // Spatie permissions (package migrations)
        'guard_name' => true,
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

        if ($this->option('fail')) {
            return 2;
        }

        return 0;
    }

    /**
     * @return array<string, true>
     */
    private function parseSchemaColumns(string $migrationsPath): array
    {
        $columns = [];
        $files = File::allFiles($migrationsPath);

        // Best-effort regexes for common column definitions.
        $colRegexes = [
            "/\\\$table->(?:string|text|longText|integer|bigInteger|unsignedBigInteger|unsignedInteger|boolean|date|dateTime|timestamp|decimal|json|enum|uuid|time|float|double|char|tinyInteger|unsignedTinyInteger|smallInteger|unsignedSmallInteger)\\('([^']+)'/",
            "/\\\$table->foreignId\\('([^']+)'/",
        ];

        foreach ($files as $file) {
            $content = $file->getContents();

            // Extract Schema::create blocks only (keeps the signal high).
            if (! str_contains($content, "Schema::create('")) {
                continue;
            }

            foreach ($colRegexes as $regex) {
                if (preg_match_all($regex, $content, $m)) {
                    foreach ($m[1] as $col) {
                        $columns[$col] = true;
                    }
                }
            }

            // Add timestamps/softDeletes if present.
            if (str_contains($content, 'timestamps()')) {
                $columns['created_at'] = true;
                $columns['updated_at'] = true;
            }
            if (str_contains($content, 'softDeletes()')) {
                $columns['deleted_at'] = true;
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

        $regex = "/->\\s*(where|orWhere|orderBy|orderByDesc|groupBy|select|addSelect|whereIn|whereNotIn|whereNull|whereNotNull)\\(\\s*'([^']+)'/";

        foreach (File::allFiles($scanPath) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $content = $file->getContents();

            if (! preg_match_all($regex, $content, $matches, PREG_OFFSET_CAPTURE)) {
                continue;
            }

            $methods = $matches[1];
            $cols = $matches[2];

            foreach ($cols as $idx => $colMatch) {
                $col = (string) $colMatch[0];

                // Ignore dotted columns (join-qualified) and JSON paths.
                if (str_contains($col, '.') || str_contains($col, '->')) {
                    continue;
                }
                if (isset($this->ignoreColumns[$col])) {
                    continue;
                }

                if (! isset($schemaColumns[$col])) {
                    $offset = (int) $colMatch[1];
                    $line = substr_count(substr($content, 0, $offset), "\n") + 1;
                    $unknown[] = [
                        str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname()),
                        (string) $methods[$idx][0],
                        $col,
                        $line,
                    ];
                }
            }
        }

        return $unknown;
    }
}
