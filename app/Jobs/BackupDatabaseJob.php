<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

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
     * Backup MySQL database using mysqldump
     */
    protected function backupMysql(array $db, $disk, string $backupDir, string $path): void
    {
        // Minimal portable fallback using mysqldump with environment variable for password
        // Using MYSQL_PWD env var to avoid password exposure in process list
        // For production, consider using spatie/laravel-backup or .my.cnf config files

        // Set password via environment variable instead of command line
        $password = $db['password'] ?? '';
        if ($password !== '') {
            putenv('MYSQL_PWD='.$password);
        }

        try {
            // Create temp file for the dump, then move to disk
            $tempFile = sys_get_temp_dir().'/'.uniqid('backup_', true).'.sql.gz';

            // All values are from config and properly escaped - no user input
            $cmd = sprintf(
                'mysqldump -h%s -u%s %s | gzip > %s',
                escapeshellarg($db['host'] ?? '127.0.0.1'),
                escapeshellarg($db['username'] ?? ''),
                escapeshellarg($db['database'] ?? ''),
                escapeshellarg($tempFile)
            );

            // Execute with proper error handling
            $output = [];
            $returnCode = 0;
            exec($cmd, $output, $returnCode);

            if ($returnCode !== 0) {
                throw new \RuntimeException('Backup command failed with exit code: '.$returnCode);
            }

            // Ensure backup directory exists on disk
            if (! $disk->exists(trim($backupDir, '/'))) {
                $disk->makeDirectory(trim($backupDir, '/'));
            }

            // Upload temp file to the configured disk using stream to avoid memory issues
            $stream = fopen($tempFile, 'rb');
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

            // Clean up temp file with proper error handling
            if (file_exists($tempFile) && ! unlink($tempFile)) {
                // Log warning but don't fail - the backup was successful
                report(new \RuntimeException('Failed to clean up temp backup file: '.$tempFile));
            }
        } finally {
            // Clear the password from environment
            putenv('MYSQL_PWD');
        }
    }

    /**
     * Backup PostgreSQL database using pg_dump
     * OLD-UNSOLVED-01 FIX: Added PostgreSQL backup support
     */
    protected function backupPostgres(array $db, $disk, string $backupDir, string $path): void
    {
        // Set password via environment variable to avoid exposure in process list
        $password = $db['password'] ?? '';
        if ($password !== '') {
            putenv('PGPASSWORD='.$password);
        }

        try {
            // Create temp file for the dump, then move to disk
            $tempFile = sys_get_temp_dir().'/'.uniqid('backup_', true).'.sql.gz';

            // Build pg_dump command with proper escaping
            // All values are from config - no user input
            $cmd = sprintf(
                'pg_dump -h %s -p %s -U %s %s | gzip > %s',
                escapeshellarg($db['host'] ?? '127.0.0.1'),
                escapeshellarg((string) ($db['port'] ?? '5432')),
                escapeshellarg($db['username'] ?? ''),
                escapeshellarg($db['database'] ?? ''),
                escapeshellarg($tempFile)
            );

            // Execute with proper error handling
            $output = [];
            $returnCode = 0;
            exec($cmd, $output, $returnCode);

            if ($returnCode !== 0) {
                throw new \RuntimeException('PostgreSQL backup command failed with exit code: '.$returnCode);
            }

            // Ensure backup directory exists on disk
            if (! $disk->exists(trim($backupDir, '/'))) {
                $disk->makeDirectory(trim($backupDir, '/'));
            }

            // Upload temp file to the configured disk using stream to avoid memory issues
            $stream = fopen($tempFile, 'rb');
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

            // Clean up temp file with proper error handling
            if (file_exists($tempFile) && ! unlink($tempFile)) {
                // Log warning but don't fail - the backup was successful
                report(new \RuntimeException('Failed to clean up temp backup file: '.$tempFile));
            }
        } finally {
            // Clear the password from environment
            putenv('PGPASSWORD');
        }
    }

    public function tags(): array
    {
        return ['maintenance', 'backup'];
    }
}
