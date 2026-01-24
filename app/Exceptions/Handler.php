<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function render($request, Throwable $e)
    {
        if (app()->environment('testing')) {
            throw $e;
        }

        return parent::render($request, $e);
    }

    public function register(): void
    {
        $this->renderable(function (Throwable $e, $request) {
            // Handle CSRF token mismatch (419) silently - refresh the page instead of showing error
            if ($e instanceof \Illuminate\Session\TokenMismatchException) {
                // For AJAX/Livewire requests, return JSON to trigger page refresh
                if ($request->wantsJson() || $request->expectsJson() || $request->is('livewire/*')) {
                    return response()->json([
                        'message' => 'Session expired. Refreshing...',
                        'redirect' => $request->url(),
                    ], 419);
                }

                // For regular requests, silently refresh the page
                return redirect($request->url())
                    ->with('info', __('Your session was refreshed. Please try again.'));
            }

            // Handle 403 Authorization errors for Livewire requests
            // This prevents the "replaceWith" error when Livewire receives HTML instead of JSON
            // Note: abort(403) creates HttpException, while Gate/Policy denials create AuthorizationException
            if ($e instanceof \Illuminate\Auth\Access\AuthorizationException 
                || ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException && $e->getStatusCode() === 403)) {
                if ($request->is('livewire/*') || $request->hasHeader('X-Livewire')) {
                    return response()->json([
                        'message' => __('You do not have permission to perform this action.'),
                        'redirect' => route('dashboard'),
                    ], 403);
                }
            }

            if ($request->is('api/*') || $request->wantsJson()) {
                return $this->renderBusinessException($e, $request);
            }
        });
    }

    /**
     * Render business exceptions with unified response format
     */
    protected function renderBusinessException(Throwable $e, $request)
    {
        $isBusinessException = $e instanceof BusinessException;

        $message = $isBusinessException
            ? $e->getMessage()
            : (config('app.debug') ? $e->getMessage() : __('Something went wrong.'));

        $meta = config('app.debug')
            ? [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]
            : [];

        $statusCode = $this->getStatusCode($e);

        return response()->json([
            'success' => false,
            'message' => $message,
            'meta' => $meta,
        ], $statusCode);
    }

    /**
     * Get HTTP status code from exception
     */
    protected function getStatusCode(Throwable $e): int
    {
        if (method_exists($e, 'getStatusCode')) {
            return $e->getStatusCode();
        }

        if ($e instanceof \Illuminate\Validation\ValidationException) {
            return 422;
        }

        if ($e instanceof \Illuminate\Auth\AuthenticationException) {
            return 401;
        }

        if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
            return 403;
        }

        if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
            return $e->getStatusCode();
        }

        return 500;
    }
}
