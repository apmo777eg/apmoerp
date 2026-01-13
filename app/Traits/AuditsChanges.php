<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

trait AuditsChanges
{
    public static function bootAuditsChanges(): void
    {
        static::created(function (Model $model): void {
            $model->writeAudit('created', [], $model->attributesToArray());
        });

        static::updated(function (Model $model): void {
            $changes = $model->getChanges();
            $old = Arr::only(array_merge($model->getOriginal(), []), array_keys($changes));
            $model->writeAudit('updated', $old, $changes);
        });

        static::deleted(function (Model $model): void {
            $model->writeAudit('deleted', $model->attributesToArray(), []);
        });
    }

    protected function writeAudit(string $action, array $old, array $new): void
    {
        // FIX U-06: Removed time-window skipping that disabled audit logging for
        // the first 5 minutes in console. Now only skip for specific safe operations
        // like migrations and seeders where auditing is not needed.
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            // Skip auditing only for specific artisan commands that shouldn't be audited
            $command = request()->server('argv')[1] ?? '';
            $skipCommands = ['migrate', 'db:seed', 'make:', 'key:generate', 'config:', 'route:', 'view:'];
            foreach ($skipCommands as $skipCommand) {
                if (str_starts_with($command, $skipCommand)) {
                    return;
                }
            }
        }

        try {
            $req = app('request');
            $user = auth()->user();

            $branchId = null;
            if (method_exists($this, 'branch') && $this->branch_id) {
                $branchId = $this->branch_id;
            } elseif ($user && ! is_null($user->branch_id)) {
                $branchId = $user->branch_id;
            }

            $moduleKey = null;
            if (method_exists($this, 'module') && $this->module_id) {
                $moduleKey = $this->module?->key;
            }

            // Get key safely - may not be set yet during creation
            $key = null;
            try {
                $key = $this->getKey();
            } catch (\Exception $e) {
                // Key not available yet, will be null
            }

            \App\Models\AuditLog::query()->create([
                'user_id' => $user?->getKey(),
                'branch_id' => $branchId,
                'module_key' => $moduleKey,
                'action' => sprintf('%s:%s', class_basename(static::class), $action),
                'subject_type' => static::class,
                'subject_id' => $key,
                'auditable_type' => static::class,
                'auditable_id' => $key,
                'ip' => $req->ip(),
                'user_agent' => (string) $req->userAgent(),
                'old_values' => $old,
                'new_values' => $new,
            ]);
        } catch (\Throwable $e) {
            if (config('app.debug')) {
                \Log::warning('Audit logging failed: '.$e->getMessage(), [
                    'model' => static::class,
                    'action' => $action,
                ]);
            }
        }
    }
}
