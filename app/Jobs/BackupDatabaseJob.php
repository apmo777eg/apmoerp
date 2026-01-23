<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class BackupDatabaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public $timeout = 900; // 15 min

    /**
     * V49-HIGH-02 FIX: Added prefix parameter to support pre-restore backups
     *
     * @param  bool  $verify  Whether to verify the backup was created
     * @param  string  $prefix  Filename prefix (default: 'backup')
     */
    public function __construct(
        public bool $verify = true,
        public string $prefix = 'backup'
    ) {}

    public function handle(): void
    {
        // HIGH-001 FIX: Validate prefix to prevent path traversal attacks
        // Only allow alphanumeric characters, underscores, and hyphens
        if (! preg_match('/^[a-zA-Z0-9_-]+$/', $this->prefix)) {
            throw new \InvalidArgumentException('Invalid backup prefix: only alphanumeric characters, underscores, and hyphens are allowed');
        }

        // Get configured disk and path
        $diskName = (string) config('backup.disk', 'local');
        $backupDir = (string) config('backup.dir', 'backups');
        $disk = Storage::disk($diskName);

        // V49-HIGH-02 FIX: Use the prefix parameter for filename generation
        $filename = $this->prefix.'_'.now()->format('Ymd_His').'.sql.gz';
        $path = trim($backupDir, '/').'/'.$filename;

        // Try using an artisan command if exists; fallback to mysqldump
        if (Artisan::has('db:dump')) {
            Artisan::call('db:dump', ['--path' => $path]);
        } else {
            // Get the current default database connection
            $connection = config('database.default');
            $db = config("database.connections.{$connection}");

            if ($db['driver'] !== 'mysql' && $db['driver'] !== 'pgsql') {
                throw new \RuntimeException(
                    "Database backup fallback only supports MySQL and PostgreSQL. Current driver: {$db['driver']}. ".
                    'Consider installing spatie/laravel-backup for multi-database support.'
                );
            }

            // OLD-UNSOLVED-01 FIX: Support PostgreSQL backup via pg_dump
            if ($db['driver'] === 'pgsql') {
                $this->backupPostgres($db, $disk, $backupDir, $path);
            } else {
                $this->backupMysql($db, $disk, $backupDir, $path);
            }
        }

        if ($this->verify && ! $disk->exists($path)) {
            throw new \RuntimeException('Backup file was not generated.');
        }
    }

    /**
     * Backup MySQL database using mysqldump via Symfony Process
     *
     * HIGH-002 FIX: Replaced exec() with Symfony Process for better security and error handling:
     * - Proper timeout handling (prevents hanging processes)
     * - Binary availability validation
     * - Secure environment variable handling
     * - Detailed logging for debugging
     */
    protected function backupMysql(array $db, $disk, string $backupDir, string $path): void
    {
        // HIGH-002 FIX: Verify mysqldump binary exists before attempting backup
        $mysqldumpPath = $this->findBinary('mysqldump');

        // Create temp file for the dump
        $tempSqlFile = sys_get_temp_dir().'/'.uniqid('backup_', true).'.sql';
        $tempGzFile = $tempSqlFile.'.gz';

        // Set password via environment variable instead of command line for security
        $env = [];
        $password = $db['password'] ?? '';
        if ($password !== '') {
            $env['MYSQL_PWD'] = $password;
        }

        try {
            // HIGH-002 FIX: Use Symfony Process with proper timeout and error handling
            $mysqldumpProcess = new Process([
                $mysqldumpPath,
                '-h', $db['host'] ?? '127.0.0.1',
                '-P', (string) ($db['port'] ?? 3306),
                '-u', $db['username'] ?? '',
                '--single-transaction',
                '--routines',
                '--triggers',
                $db['database'] ?? '',
            ], null, $env, null, 600); // 10 minute timeout

            Log::info('BackupDatabaseJob: Starting MySQL backup', [
                'database' => $db['database'] ?? 'unknown',
                'host' => $db['host'] ?? '127.0.0.1',
            ]);

            $mysqldumpProcess->run();

            if (! $mysqldumpProcess->isSuccessful()) {
                throw new ProcessFailedException($mysqldumpProcess);
            }

            // Write SQL output to temp file
            file_put_contents($tempSqlFile, $mysqldumpProcess->getOutput());

            // Compress the SQL file using gzip
            $gzipPath = $this->findBinary('gzip');
            $gzipProcess = new Process([$gzipPath, '-9', '-f', $tempSqlFile], null, null, null, 300);
            $gzipProcess->run();

            if (! $gzipProcess->isSuccessful()) {
                throw new ProcessFailedException($gzipProcess);
            }

            // Ensure backup directory exists on disk
            if (! $disk->exists(trim($backupDir, '/'))) {
                $disk->makeDirectory(trim($backupDir, '/'));
            }

            // Upload compressed file to the configured disk using stream
            $stream = fopen($tempGzFile, 'rb');
            if ($stream === false) {
                throw new \RuntimeException('Failed to open temp backup file for reading');
            }

            try {
                $disk->writeStream($path, $stream);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            Log::info('BackupDatabaseJob: MySQL backup completed successfully', ['path' => $path]);
        } finally {
            // Clean up temp files
            foreach ([$tempSqlFile, $tempGzFile] as $file) {
                if (file_exists($file) && ! unlink($file)) {
                    Log::warning('BackupDatabaseJob: Failed to clean up temp file', ['file' => $file]);
                }
            }
        }
    }

    /**
     * Backup PostgreSQL database using pg_dump via Symfony Process
     *
     * HIGH-002 FIX: Replaced exec() with Symfony Process for better security and error handling:
     * - Proper timeout handling (prevents hanging processes)
     * - Binary availability validation
     * - Secure environment variable handling
     * - Detailed logging for debugging
     */
    protected function backupPostgres(array $db, $disk, string $backupDir, string $path): void
    {
        // HIGH-002 FIX: Verify pg_dump binary exists before attempting backup
        $pgdumpPath = $this->findBinary('pg_dump');

        // Create temp file for the dump
        $tempSqlFile = sys_get_temp_dir().'/'.uniqid('backup_', true).'.sql';
        $tempGzFile = $tempSqlFile.'.gz';

        // Set password via environment variable for security
        $env = [];
        $password = $db['password'] ?? '';
        if ($password !== '') {
            $env['PGPASSWORD'] = $password;
        }

        try {
            // HIGH-002 FIX: Use Symfony Process with proper timeout and error handling
            $pgdumpProcess = new Process([
                $pgdumpPath,
                '-h', $db['host'] ?? '127.0.0.1',
                '-p', (string) ($db['port'] ?? 5432),
                '-U', $db['username'] ?? '',
                '-d', $db['database'] ?? '',
                '--no-password',
            ], null, $env, null, 600); // 10 minute timeout

            Log::info('BackupDatabaseJob: Starting PostgreSQL backup', [
                'database' => $db['database'] ?? 'unknown',
                'host' => $db['host'] ?? '127.0.0.1',
            ]);

            $pgdumpProcess->run();

            if (! $pgdumpProcess->isSuccessful()) {
                throw new ProcessFailedException($pgdumpProcess);
            }

            // Write SQL output to temp file
            file_put_contents($tempSqlFile, $pgdumpProcess->getOutput());

            // Compress the SQL file using gzip
            $gzipPath = $this->findBinary('gzip');
            $gzipProcess = new Process([$gzipPath, '-9', '-f', $tempSqlFile], null, null, null, 300);
            $gzipProcess->run();

            if (! $gzipProcess->isSuccessful()) {
                throw new ProcessFailedException($gzipProcess);
            }

            // Ensure backup directory exists on disk
            if (! $disk->exists(trim($backupDir, '/'))) {
                $disk->makeDirectory(trim($backupDir, '/'));
            }

            // Upload compressed file to the configured disk using stream
            $stream = fopen($tempGzFile, 'rb');
            if ($stream === false) {
                throw new \RuntimeException('Failed to open temp backup file for reading');
            }

            try {
                $disk->writeStream($path, $stream);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            Log::info('BackupDatabaseJob: PostgreSQL backup completed successfully', ['path' => $path]);
        } finally {
            // Clean up temp files
            foreach ([$tempSqlFile, $tempGzFile] as $file) {
                if (file_exists($file) && ! unlink($file)) {
                    Log::warning('BackupDatabaseJob: Failed to clean up temp file', ['file' => $file]);
                }
            }
        }
    }

    /**
     * Find a binary executable path
     *
     * HIGH-002 FIX: Added binary validation to ensure required tools are available
     *
     * @throws \RuntimeException if binary is not found
     */
    protected function findBinary(string $name): string
    {
        $finder = new ExecutableFinder();
        $path = $finder->find($name);

        if ($path === null) {
            throw new \RuntimeException(
                "Required binary '{$name}' not found. Please ensure it is installed and available in PATH. ".
                'For production, consider installing spatie/laravel-backup for multi-database support.'
            );
        }

        return $path;
    }

    public function tags(): array
    {
        return ['maintenance', 'backup'];
    }
}
