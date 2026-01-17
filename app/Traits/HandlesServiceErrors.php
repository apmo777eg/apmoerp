<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Support\Facades\Log;
use Throwable;

trait HandlesServiceErrors
{
    private const NO_DEFAULT = '__NO_DEFAULT_VALUE__';

    /**
     * Execute a callback with comprehensive error handling and logging.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T|mixed
     */
    protected function handleServiceOperation(
        callable $callback,
        string $operation,
        array $context = [],
        mixed $defaultValue = self::NO_DEFAULT
    ): mixed {
        try {
            return $callback();
        } catch (Throwable $e) {
            $this->logServiceError($operation, $e, $context);

            if ($defaultValue !== self::NO_DEFAULT) {
                return $defaultValue;
            }

            throw $e;
        }
    }

    /**
     * Log service errors with comprehensive context.
     */
    protected function logServiceError(string $operation, Throwable $exception, array $context = []): void
    {
        $serviceName = class_basename($this);

        // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
        Log::error("{$serviceName}::{$operation} failed", array_merge([
            'service' => $serviceName,
            'operation' => $operation,
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'user_id' => actual_user_id(),
            'branch_id' => request()->attributes->get('branch_id'),
        ], $context));
    }

    /**
     * Log service warnings.
     */
    protected function logServiceWarning(string $operation, string $message, array $context = []): void
    {
        $serviceName = class_basename($this);

        // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
        Log::warning("{$serviceName}::{$operation} - {$message}", array_merge([
            'service' => $serviceName,
            'operation' => $operation,
            'user_id' => actual_user_id(),
            'branch_id' => request()->attributes->get('branch_id'),
        ], $context));
    }

    /**
     * Log service information.
     */
    protected function logServiceInfo(string $operation, string $message, array $context = []): void
    {
        $serviceName = class_basename($this);

        // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
        Log::info("{$serviceName}::{$operation} - {$message}", array_merge([
            'service' => $serviceName,
            'operation' => $operation,
            'user_id' => actual_user_id(),
            'branch_id' => request()->attributes->get('branch_id'),
        ], $context));
    }
}
