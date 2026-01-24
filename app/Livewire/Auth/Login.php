<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Services\AuthService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guest')]
class Login extends Component
{
    public string $credential = '';

    public string $password = '';

    public bool $remember = true;

    protected AuthService $authService;

    public function boot(AuthService $authService): void
    {
        $this->authService = $authService;
    }

    protected function rules(): array
    {
        return [
            'credential' => ['required', 'string', 'min:3'],
            'password' => ['required', 'string', 'min:6'],
        ];
    }

    protected function messages(): array
    {
        return [
            'credential.required' => __('Please enter your email, phone, or username to login.'),
            'credential.min' => __('The credential must be at least 3 characters.'),
            'password.required' => __('Please enter your password to continue.'),
            'password.min' => __('Password must be at least 6 characters. Please try again.'),
        ];
    }

    public function mount(): void
    {
        if (Auth::check()) {
            $this->redirectIntended($this->getDefaultRedirectDestination(), navigate: true);
        }
    }

    public function login(): void
    {
        $this->validate();

        $throttleKey = Str::transliterate(Str::lower($this->credential).'|'.request()->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->addError('credential', __('Too many login attempts. Please try again in :seconds seconds.', ['seconds' => $seconds]));

            return;
        }

        $result = $this->authService->attemptMultiFieldLogin(
            $this->credential,
            $this->password,
            $this->remember
        );

        if (! $result['success']) {
            RateLimiter::hit($throttleKey);

            match ($result['error']) {
                'user_not_found' => $this->addError('credential', __('No account found with this email, phone, or username.')),
                'account_inactive' => $this->addError('credential', __('Your account has been deactivated. Please contact support.')),
                'invalid_password' => $this->addError('password', __('The password you entered is incorrect.')),
                'password_not_set' => $this->addError('password', __('This account has no password set. Please reset your password.')),
                default => $this->addError('credential', __('These credentials do not match our records.')),
            };

            return;
        }

        RateLimiter::clear($throttleKey);

        if ($result['user']) {
            $result['user']->update(['last_login_at' => now()]);
        }

        session()->regenerate();

        $this->redirectIntended($this->getDefaultRedirectDestination(), navigate: true);
    }

    protected function getDefaultRedirectDestination(): string
    {
        return route(first_accessible_route_for_user(Auth::user()));
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
