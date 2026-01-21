<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Services\BackupService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

/**
 * BackupRestore - One-click backup and restore management
 *
 * Provides a user-friendly interface for:
 * - Creating database backups
 * - Viewing existing backups
 * - Restoring from backup
 * - Downloading backups
 * - Deleting old backups
 */
class BackupRestore extends Component
{
    use AuthorizesRequests;
    
    public array $backups = [];

    public bool $isCreating = false;

    public bool $isRestoring = false;

    public ?string $selectedBackup = null;

    public bool $showRestoreConfirm = false;

    public ?string $lastBackupResult = null;

    public ?string $lastRestoreResult = null;

    protected BackupService $backupService;

    public function boot(BackupService $backupService): void
    {
        $this->backupService = $backupService;
    }

    public function mount(): void
    {
        // V57-HIGH-01 FIX: Add authorization for backup management
        $this->authorize('system.backup.manage');
        
        $this->loadBackups();
    }

    /**
     * Load list of available backups
     */
    public function loadBackups(): void
    {
        $this->backups = $this->backupService->list();
    }

    /**
     * Create a new backup
     */
    public function createBackup(): void
    {
        // V57-HIGH-01 FIX: Add authorization for backup creation
        $this->authorize('system.backup.manage');
        
        $this->isCreating = true;
        $this->lastBackupResult = null;

        try {
            $result = $this->backupService->run(verify: true);

            if (isset($result['path'])) {
                $this->lastBackupResult = 'success';
                session()->flash('success', __('Backup created successfully!'));
                $this->loadBackups();
            } else {
                $this->lastBackupResult = 'error';
                session()->flash('error', __('Failed to create backup'));
            }
        } catch (\Exception $e) {
            $this->lastBackupResult = 'error';
            session()->flash('error', __('Backup failed: ').$e->getMessage());
        }

        $this->isCreating = false;
    }

    /**
     * Initiate restore process (show confirmation)
     */
    public function initiateRestore(string $path): void
    {
        // V57-HIGH-01 FIX: Add authorization for restore
        $this->authorize('system.backup.manage');
        
        $this->selectedBackup = $path;
        $this->showRestoreConfirm = true;
    }

    /**
     * Cancel restore
     */
    public function cancelRestore(): void
    {
        $this->selectedBackup = null;
        $this->showRestoreConfirm = false;
    }

    /**
     * Confirm and execute restore
     */
    public function confirmRestore(): void
    {
        // V57-HIGH-01 FIX: Add authorization for restore
        $this->authorize('system.backup.manage');
        
        if (! $this->selectedBackup) {
            return;
        }

        $this->isRestoring = true;
        $this->lastRestoreResult = null;
        $this->showRestoreConfirm = false;

        try {
            // Create a pre-restore backup first
            $preBackup = $this->backupService->createPreRestoreBackup();

            // Perform restore
            $result = $this->backupService->restore($this->selectedBackup);

            if ($result['success'] ?? false) {
                $this->lastRestoreResult = 'success';
                session()->flash('success', __('Database restored successfully! A pre-restore backup was created.'));
                $this->loadBackups();
            } else {
                $this->lastRestoreResult = 'error';
                session()->flash('error', __('Restore failed'));
            }
        } catch (\Exception $e) {
            $this->lastRestoreResult = 'error';
            session()->flash('error', __('Restore failed: ').$e->getMessage());
        }

        $this->isRestoring = false;
        $this->selectedBackup = null;
    }

    /**
     * Download a backup file
     */
    public function download(string $path): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        // V57-HIGH-01 FIX: Add authorization for download
        $this->authorize('system.backup.manage');
        
        $fullPath = $this->backupService->download($path);

        if (! $fullPath) {
            session()->flash('error', __('Backup file not found'));

            return back();
        }

        return response()->download($fullPath, basename($path));
    }

    /**
     * Delete a backup
     */
    public function deleteBackup(string $path): void
    {
        // V57-HIGH-01 FIX: Add authorization for delete
        $this->authorize('system.backup.manage');
        
        try {
            if ($this->backupService->delete($path)) {
                session()->flash('success', __('Backup deleted'));
                $this->loadBackups();
            } else {
                session()->flash('error', __('Failed to delete backup'));
            }
        } catch (\Exception $e) {
            session()->flash('error', __('Delete failed: ').$e->getMessage());
        }
    }

    /**
     * Format file size
     */
    public function formatSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2).' '.$units[$pow];
    }

    /**
     * Format timestamp
     */
    public function formatDate(int $timestamp): string
    {
        return \Carbon\Carbon::createFromTimestamp($timestamp)->format('Y-m-d H:i:s');
    }

    public function render()
    {
        return view('livewire.admin.backup-restore')
            ->layout('layouts.app')
            ->title(__('Backup & Restore'));
    }
}
