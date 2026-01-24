<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Services\AuthService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guest')]
class ForgotPassword extends Component
{
    public string $email = '';

    public bool $emailSent = false;

    protected AuthService $authService;

    public function boot(AuthService $authService): void
    {
        $this->authService = $authService;
    }

    protected function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
        ];
    }

    protected function messages(): array
    {
        return [
            'email.required' => __('Please enter your email address.'),
            'email.email' => __('Please enter a valid email address.'),
        ];
    }

    public function sendResetLink(): void
    {
        $this->validate();

        $result = $this->authService->initiatePasswordReset($this->email);

        if (! $result['success']) {
            match ($result['error']) {
                'user_not_found' => $this->addError('email', __('No account found with this email address.')),
                'account_inactive' => $this->addError('email', __('This account has been deactivated. Please contact support.')),
                'email_failed' => $this->addError('email', __('Failed to send reset email. Please try again later.')),
                default => $this->addError('email', __('An error occurred. Please try again.')),
            };

            return;
        }

        $this->emailSent = true;
    }

    public function render()
    {
        return view('livewire.auth.forgot-password');
    }
}
