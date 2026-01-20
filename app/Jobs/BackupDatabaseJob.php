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

    public function __construct(public bool $verify = true) {}

    public function handle(): void
    {
        // Get configured disk and path
        $diskName = (string) config('backup.disk', 'local');
        $backupDir = (string) config('backup.dir', 'backups');
        $disk = Storage::disk($diskName);

        $filename = 'backup_'.now()->format('Ymd_His').'.sql.gz';
        $path = trim($backupDir, '/').'/'.$filename;

        // Try using an artisan command if exists; fallback to mysqldump
        if (Artisan::has('db:dump')) {
            Artisan::call('db:dump', ['--path' => $path]);
        } else {
            // Get the current default database connection
            $connection = config('database.default');
            $db = config("database.connections.{$connection}");

            if ($db['driver'] !== 'mysql') {
                throw new \RuntimeException(
                    "Database backup fallback only supports MySQL. Current driver: {$db['driver']}. ".
                    'Consider installing spatie/laravel-backup for multi-database support.'
                );
            }

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

                // Upload temp file to the configured disk
                $disk->put($path, file_get_contents($tempFile));

                // Clean up temp file
                @unlink($tempFile);
            } finally {
                // Clear the password from environment
                putenv('MYSQL_PWD');
            }
        }

        if ($this->verify && ! $disk->exists($path)) {
            throw new \RuntimeException('Backup file was not generated.');
        }
    }

    public function tags(): array
    {
        return ['maintenance', 'backup'];
    }
}
