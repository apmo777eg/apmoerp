<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Services\AuthService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guest')]
class ResetPassword extends Component
{
    public string $token = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $resetSuccess = false;

    protected AuthService $authService;

    public function boot(AuthService $authService): void
    {
        $this->authService = $authService;
    }

    protected function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    protected function messages(): array
    {
        return [
            'password.required' => __('Please enter a new password.'),
            'password.min' => __('Password must be at least 8 characters.'),
            'password.confirmed' => __('Password confirmation does not match.'),
        ];
    }

    public function mount(string $token, ?string $email = null): void
    {
        $this->token = $token;
        $this->email = $email ?? request()->query('email', '');
    }

    public function resetPassword(): void
    {
        $this->validate();

        $result = $this->authService->resetPassword($this->email, $this->token, $this->password);

        if (! $result['success']) {
            match ($result['error']) {
                'invalid_token' => $this->addError('email', __('Invalid reset token. Please request a new reset link.')),
                'expired_token' => $this->addError('email', __('This reset link has expired. Please request a new one.')),
                'user_not_found' => $this->addError('email', __('No account found with this email address.')),
                'reset_failed' => $this->addError('email', __('Password reset failed. Please try again.')),
                default => $this->addError('email', __('An error occurred. Please try again.')),
            };

            return;
        }

        session()->flash('success', __('Your password has been reset. Please log in.'));

        $this->redirectRoute('login', navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.reset-password');
    }
}
