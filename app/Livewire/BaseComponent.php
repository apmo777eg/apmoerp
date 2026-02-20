<?php

declare(strict_types=1);

namespace App\Livewire;

use Livewire\Component as LivewireComponent;

/**
 * BaseComponent
 *
 * A shared Livewire base component to centralize common UX behaviors:
 * - Convert session flash messages (success/error/status/...) to Livewire toast events
 *   for *non-redirect* actions (AJAX requests) so users always get feedback.
 * - Track redirects to avoid consuming flash messages intended for the next page.
 */
abstract class BaseComponent extends LivewireComponent
{
    /**
     * When a component triggers a redirect, we should not consume session flash
     * (it is usually intended for the next page).
     */
    protected bool $didRedirect = false;

    /**
     * Override redirect() to mark that the current Livewire response will navigate.
     * Livewire v4 supports the named parameter: navigate: true
     */
    public function redirect($url, $navigate = false)
    {
        $this->didRedirect = true;

        return parent::redirect($url, $navigate);
    }

    /**
     * Livewire lifecycle hook executed after each request.
     */
    public function dehydrate(): void
    {
        $this->dispatchFlashedNotifications();
    }

    /**
     * Dispatch flashed session messages as toast notifications for Livewire (AJAX) actions.
     */
    protected function dispatchFlashedNotifications(): void
    {
        if ($this->didRedirect) {
            return;
        }

        /**
         * Map session keys to notification types.
         * - status is commonly used in Laravel for "success" messages.
         */
        $map = [
            'success' => 'success',
            'status' => 'success',
            'error' => 'error',
            'warning' => 'warning',
            'info' => 'info',
        ];

        foreach ($map as $key => $type) {
            if (! session()->has($key)) {
                continue;
            }

            $message = session()->get($key);

            // Normalize message to a string.
            if (is_array($message)) {
                $message = implode("\n", array_filter(array_map('strval', $message)));
            }

            $message = is_string($message) ? trim($message) : (string) $message;

            if ($message !== '') {
                $this->dispatch('notify', type: $type, message: $message);
            }

            // Prevent stale messages on subsequent page loads.
            session()->forget($key);
        }
    }

    /** Convenience wrappers */
    protected function notifySuccess(string $message): void
    {
        $this->dispatch('notify', type: 'success', message: $message);
    }

    protected function notifyError(string $message): void
    {
        $this->dispatch('notify', type: 'error', message: $message);
    }

    protected function notifyWarning(string $message): void
    {
        $this->dispatch('notify', type: 'warning', message: $message);
    }

    protected function notifyInfo(string $message): void
    {
        $this->dispatch('notify', type: 'info', message: $message);
    }
}
