<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Services\TwoFactorAuthService;
use Illuminate\Support\Facades\Auth;
use App\Livewire\BaseComponent as Component;
class TwoFactorChallenge extends Component
{
    public string $code = '';

    public string $recoveryCode = '';

    public bool $useRecovery = false;

    protected TwoFactorAuthService $twoFactorService;

    public function boot(TwoFactorAuthService $twoFactorService): void
    {
        $this->twoFactorService = $twoFactorService;
    }

    public function mount(): void
    {
        if (! Auth::check() || ! Auth::user()->hasTwoFactorEnabled()) {
            $this->redirect(route('dashboard'), navigate: true);

            return;
        }

        if (session('2fa_verified')) {
            $this->redirect(route('dashboard'), navigate: true);
        }
    }

    public function verify(): void
    {
        $user = Auth::user();

        if ($this->useRecovery) {
            $this->validate(['recoveryCode' => 'required|string']);

            if ($this->twoFactorService->verifyRecoveryCode($user, $this->recoveryCode)) {
                session(['2fa_verified' => true]);
                $this->redirect(route('dashboard'), navigate: true);

                return;
            }

            $this->addError('recoveryCode', __('Invalid recovery code'));

            return;
        }

        $this->validate(['code' => 'required|string|size:6']);

        $secret = $this->twoFactorService->getDecryptedSecret($user);

        if ($secret && $this->twoFactorService->verify($secret, $this->code)) {
            session(['2fa_verified' => true]);
            $this->redirect(route('dashboard'), navigate: true);

            return;
        }

        $this->addError('code', __('Invalid authentication code'));
    }

    public function toggleRecovery(): void
    {
        $this->useRecovery = ! $this->useRecovery;
        $this->reset(['code', 'recoveryCode']);
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.auth.two-factor-challenge')
            ->layout('layouts.guest');
    }
}
