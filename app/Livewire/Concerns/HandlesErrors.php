<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

trait HandlesErrors
{
    /**
     * Handle an operation with error handling and optional transaction wrapping.
     *
     * HIGH-001 FIX: Wrap multi-write operations in DB::transaction for atomicity.
     * This ensures that all database writes within the operation either succeed
     * together or are rolled back on failure.
     */
    protected function handleOperation(callable $operation, string $successMessage = '', ?string $redirectRoute = null, bool $useTransaction = true): mixed
    {
        try {
            // HIGH-001 FIX: Wrap operation in transaction for data integrity
            if ($useTransaction) {
                DB::transaction(function () use ($operation) {
                    $operation();
                });
            } else {
                $operation();
            }

            if ($successMessage) {
                session()->flash('success', $successMessage);
            }

            if ($redirectRoute) {
                $this->redirectRoute($redirectRoute, navigate: true);
            }
        } catch (ValidationException $e) {
            // Re-throw validation exceptions so Livewire can handle them properly
            // This ensures validation errors are displayed to the user
            throw $e;
        } catch (QueryException $e) {
            // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
            Log::error('Database error in Livewire component', [
                'component' => static::class,
                'error' => $e->getMessage(),
                'user_id' => actual_user_id(),
            ]);

            // Dispatch browser event to hide loading states
            $this->dispatch('operation-failed');
            session()->flash('error', __('A database error occurred. Please try again.'));
        } catch (Throwable $e) {
            // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
            Log::error('Error in Livewire component', [
                'component' => static::class,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => actual_user_id(),
            ]);

            // Dispatch browser event to hide loading states
            $this->dispatch('operation-failed');
            session()->flash('error', __('An unexpected error occurred. Please try again.'));
        }
    }

    /**
     * Handle a delete operation with error handling and optional transaction wrapping.
     *
     * HIGH-001 FIX: Wrap multi-write delete operations in DB::transaction for atomicity.
     */
    protected function handleDelete(callable $operation, string $successMessage = '', ?string $redirectRoute = null, bool $useTransaction = true): mixed
    {
        try {
            // HIGH-001 FIX: Wrap operation in transaction for data integrity
            if ($useTransaction) {
                DB::transaction(function () use ($operation) {
                    $operation();
                });
            } else {
                $operation();
            }

            if ($successMessage) {
                session()->flash('success', $successMessage);
            }

            if ($redirectRoute) {
                $this->redirectRoute($redirectRoute, navigate: true);
            }
        } catch (QueryException $e) {
            if (str_contains($e->getMessage(), 'foreign key')) {
                session()->flash('error', __('Cannot delete this record as it has related data.'));
            } else {
                // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
                Log::error('Database error during delete', [
                    'component' => static::class,
                    'error' => $e->getMessage(),
                    'user_id' => actual_user_id(),
                ]);
                session()->flash('error', __('A database error occurred. Please try again.'));
            }
        } catch (Throwable $e) {
            // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
            Log::error('Error during delete', [
                'component' => static::class,
                'error' => $e->getMessage(),
                'user_id' => actual_user_id(),
            ]);

            session()->flash('error', __('An unexpected error occurred. Please try again.'));
        }
    }

    protected function safeExecute(callable $callback, mixed $default = null): mixed
    {
        try {
            return $callback();
        } catch (Throwable $e) {
            // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
            Log::error('Error in Livewire component', [
                'component' => static::class,
                'error' => $e->getMessage(),
                'user_id' => actual_user_id(),
            ]);

            return $default;
        }
    }
}
