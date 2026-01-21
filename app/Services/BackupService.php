<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Contracts\BackupServiceInterface;
use App\Traits\HandlesServiceErrors;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class BackupService implements BackupServiceInterface
{
    use HandlesServiceErrors;

    /**
     * Regex pattern for valid backup filenames.
     * Matches: backup_YYYYMMDD_HHMMSS.sql.gz or pre_restore_YYYYMMDD_HHMMSS.sql.gz
     */
    protected const FILENAME_PATTERN = '/^(backup|pre_restore)_\d{8}_\d{6}\.sql(\.gz)?$/';

    protected string $disk;

    protected string $dir;

    public function __construct()
    {
        $this->disk = (string) config('backup.disk', 'local');
        $this->dir = (string) config('backup.dir', 'backups');
    }

    /**
     * Run a database backup.
     *
     * V49-HIGH-02 FIX: Added optional $prefix parameter to allow custom filename prefixes.
     * This enables createPreRestoreBackup() to generate properly named backup files.
     *
     * @param  bool  $verify  Whether to verify the backup was created
     * @param  string  $prefix  Optional filename prefix (default: 'backup')
     * @return array{path: string, size: int}
     */
    public function run(bool $verify = true, string $prefix = 'backup'): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($verify, $prefix) {
                $filename = $prefix.'_'.now()->format('Ymd_His').'.sql.gz';
                $path = trim($this->dir, '/').'/'.$filename;

                if (Artisan::has('db:dump')) {
                    Artisan::call('db:dump', ['--path' => $path]);
                } else {
                    dispatch_sync(new \App\Jobs\BackupDatabaseJob(verify: $verify, prefix: $prefix));
                }

                if ($verify && ! Storage::disk($this->disk)->exists($path)) {
                    throw new \RuntimeException('Backup file missing after run.');
                }

                $size = Storage::disk($this->disk)->exists($path)
                    ? Storage::disk($this->disk)->size($path)
                    : 0;

                return [
                    'path' => $path,
                    'size' => $size,
                ];
            },
            operation: 'run',
            context: ['verify' => $verify, 'prefix' => $prefix]
        );
    }

    public function verify(array $result): bool
    {
        return $this->handleServiceOperation(
            callback: function () use ($result) {
                $path = $result['path'] ?? '';

                if (empty($path)) {
                    return false;
                }

                // Check if file exists
                if (! Storage::disk($this->disk)->exists($path)) {
                    return false;
                }

                // Check if file has content (size > 0)
                $size = Storage::disk($this->disk)->size($path);
                if ($size <= 0) {
                    return false;
                }

                // Basic verification passed
                return true;
            },
            operation: 'verify',
            context: ['path' => $result['path'] ?? ''],
            defaultValue: false
        );
    }

    public function list(): array
    {
        return $this->handleServiceOperation(
            callback: function () {
                $disk = Storage::disk($this->disk);
                $files = $disk->files($this->dir);
                $out = [];
                foreach ($files as $f) {
                    $out[] = [
                        'path' => $f,
                        'size' => (int) $disk->size($f),
                        'modified' => (int) $disk->lastModified($f),
                    ];
                }
                usort($out, fn ($a, $b) => $b['modified'] <=> $a['modified']);

                return $out;
            },
            operation: 'list',
            context: [],
            defaultValue: []
        );
    }

    public function delete(string $path): bool
    {
        // Validate path before any operation
        $validPath = $this->validatePath($path);

        return $this->handleServiceOperation(
            callback: fn () => Storage::disk($this->disk)->delete($validPath),
            operation: 'delete',
            context: ['path' => $validPath],
            defaultValue: false
        );
    }

    /**
     * Restore database from a backup file
     *
     * WARNING: This will replace all current data with the backup data.
     * Make sure to create a backup before restoring.
     */
    public function restore(string $path): array
    {
        // Validate path before any operation
        $validPath = $this->validatePath($path);

        return $this->handleServiceOperation(
            callback: function () use ($validPath) {
                // Validate the backup file exists
                if (! Storage::disk($this->disk)->exists($validPath)) {
                    throw new \RuntimeException('Backup file not found: '.$validPath);
                }

                // Get the full path
                $fullPath = Storage::disk($this->disk)->path($validPath);

                // Determine if file is compressed
                $isCompressed = str_ends_with($validPath, '.gz');

                // Get database connection settings
                $connection = config('database.default');
                $config = config("database.connections.{$connection}");

                if ($config['driver'] !== 'mysql' && $config['driver'] !== 'pgsql') {
                    throw new \RuntimeException('Only MySQL and PostgreSQL database restore is supported');
                }

                // OLD-UNSOLVED-01 FIX: Support PostgreSQL restore
                if ($config['driver'] === 'pgsql') {
                    $this->restorePostgres($config, $fullPath, $isCompressed);
                } else {
                    $this->restoreMysql($config, $fullPath, $isCompressed);
                }

                // Clear all caches after restore
                Artisan::call('cache:clear');
                Artisan::call('config:clear');

                return [
                    'success' => true,
                    'path' => $validPath,
                    'restored_at' => now()->toISOString(),
                ];
            },
            operation: 'restore',
            context: ['path' => $validPath]
        );
    }

    /**
     * Restore MySQL database from backup
     * OLD-UNSOLVED-01 FIX: Extracted from restore() for better organization
     */
    protected function restoreMysql(array $config, string $fullPath, bool $isCompressed): void
    {
        $host = $config['host'];
        $port = $config['port'] ?? 3306;
        $database = $config['database'];
        $username = $config['username'];
        $password = $config['password'] ?? '';

        // Set password via environment variable to avoid exposure in process list
        if ($password !== '') {
            putenv('MYSQL_PWD='.$password);
        }

        try {
            // Build the mysql restore command properly
            if ($isCompressed) {
                // For compressed files: gunzip -c file.sql.gz | mysql ...
                $command = sprintf(
                    'gunzip -c %s | mysql -h %s -P %s -u %s %s 2>&1',
                    escapeshellarg($fullPath),
                    escapeshellarg($host),
                    escapeshellarg((string) $port),
                    escapeshellarg($username),
                    escapeshellarg($database)
                );
            } else {
                // For uncompressed files: mysql ... < file.sql
                $command = sprintf(
                    'mysql -h %s -P %s -u %s %s < %s 2>&1',
                    escapeshellarg($host),
                    escapeshellarg((string) $port),
                    escapeshellarg($username),
                    escapeshellarg($database),
                    escapeshellarg($fullPath)
                );
            }

            // Execute restore
            $output = [];
            $returnVar = 0;
            exec($command, $output, $returnVar);

            if ($returnVar !== 0) {
                throw new \RuntimeException('MySQL restore failed: '.implode("\n", $output));
            }
        } finally {
            // Always clear the password from environment
            putenv('MYSQL_PWD');
        }
    }

    /**
     * Restore PostgreSQL database from backup
     * OLD-UNSOLVED-01 FIX: Added PostgreSQL restore support
     */
    protected function restorePostgres(array $config, string $fullPath, bool $isCompressed): void
    {
        $host = $config['host'];
        $port = $config['port'] ?? 5432;
        $database = $config['database'];
        $username = $config['username'];
        $password = $config['password'] ?? '';

        // Set password via environment variable to avoid exposure in process list
        if ($password !== '') {
            putenv('PGPASSWORD='.$password);
        }

        try {
            // Build the psql restore command properly
            if ($isCompressed) {
                // For compressed files: gunzip -c file.sql.gz | psql ...
                $command = sprintf(
                    'gunzip -c %s | psql -h %s -p %s -U %s %s 2>&1',
                    escapeshellarg($fullPath),
                    escapeshellarg($host),
                    escapeshellarg((string) $port),
                    escapeshellarg($username),
                    escapeshellarg($database)
                );
            } else {
                // For uncompressed files: psql ... < file.sql
                $command = sprintf(
                    'psql -h %s -p %s -U %s %s < %s 2>&1',
                    escapeshellarg($host),
                    escapeshellarg((string) $port),
                    escapeshellarg($username),
                    escapeshellarg($database),
                    escapeshellarg($fullPath)
                );
            }

            // Execute restore
            $output = [];
            $returnVar = 0;
            exec($command, $output, $returnVar);

            if ($returnVar !== 0) {
                throw new \RuntimeException('PostgreSQL restore failed: '.implode("\n", $output));
            }
        } finally {
            // Always clear the password from environment
            putenv('PGPASSWORD');
        }
    }

    /**
     * Download a backup file
     */
    public function download(string $path): ?string
    {
        // Validate path before any operation
        $validPath = $this->validatePath($path);

        return $this->handleServiceOperation(
            callback: function () use ($validPath) {
                if (! Storage::disk($this->disk)->exists($validPath)) {
                    throw new \RuntimeException('Backup file not found');
                }

                return Storage::disk($this->disk)->path($validPath);
            },
            operation: 'download',
            context: ['path' => $validPath],
            defaultValue: null
        );
    }

    /**
     * Get backup info (size, date, etc.)
     */
    public function getInfo(string $path): ?array
    {
        // Validate path before any operation
        $validPath = $this->validatePath($path);

        return $this->handleServiceOperation(
            callback: function () use ($validPath) {
                if (! Storage::disk($this->disk)->exists($validPath)) {
                    return null;
                }

                $disk = Storage::disk($this->disk);

                return [
                    'path' => $validPath,
                    'filename' => basename($validPath),
                    'size' => $disk->size($validPath),
                    'size_human' => $this->formatBytes($disk->size($validPath)),
                    'modified' => $disk->lastModified($validPath),
                    'modified_human' => \Carbon\Carbon::createFromTimestamp($disk->lastModified($validPath))->diffForHumans(),
                ];
            },
            operation: 'getInfo',
            context: ['path' => $validPath],
            defaultValue: null
        );
    }

    /**
     * Format bytes to human-readable format
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision).' '.$units[$pow];
    }

    /**
     * Validate and sanitize the backup file path.
     *
     * Ensures path is within allowed directory and matches expected patterns.
     *
     * @throws \InvalidArgumentException if path is invalid
     */
    protected function validatePath(string $path): string
    {
        // Reject paths with directory traversal attempts
        if (str_contains($path, '..') || str_contains($path, "\0")) {
            throw new \InvalidArgumentException('Invalid backup path: directory traversal not allowed');
        }

        // Normalize directory
        $dir = trim($this->dir, '/').'/';

        // Path must start with the configured backup directory
        if (! str_starts_with($path, $dir)) {
            throw new \InvalidArgumentException('Invalid backup path: must be within backup directory');
        }

        // Extract filename from path
        $filename = basename($path);

        // Validate filename matches expected backup pattern:
        // backup_YYYYMMDD_HHMMSS.sql.gz or pre_restore_YYYYMMDD_HHMMSS.sql.gz
        if (! preg_match(self::FILENAME_PATTERN, $filename)) {
            throw new \InvalidArgumentException('Invalid backup path: filename does not match expected pattern');
        }

        return $path;
    }

    /**
     * Create a pre-restore backup
     *
     * V49-HIGH-02 FIX: Now uses the 'pre_restore' prefix when calling run(),
     * ensuring the backup file is named 'pre_restore_YYYYMMDD_HHMMSS.sql.gz'
     * for easy identification and audit trail.
     */
    public function createPreRestoreBackup(): array
    {
        return $this->run(true, 'pre_restore');
    }
}
