<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Services\TwoFactorAuthService;
use Illuminate\Support\Facades\Auth;
use App\Livewire\BaseComponent as Component;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class TwoFactorSetup extends Component
{
    public string $secret = '';

    public string $qrCodeSvg = '';

    public string $code = '';

    public array $recoveryCodes = [];

    public bool $enabled = false;

    public bool $showRecoveryCodes = false;

    protected TwoFactorAuthService $twoFactorService;

    public function boot(TwoFactorAuthService $twoFactorService): void
    {
        $this->twoFactorService = $twoFactorService;
    }

    public function mount(): void
    {
        $user = Auth::user();
        $this->enabled = $user->hasTwoFactorEnabled();

        if ($this->enabled) {
            $this->recoveryCodes = $this->twoFactorService->getRecoveryCodes($user);
        } else {
            $this->generateNewSecret();
        }
    }

    public function generateNewSecret(): void
    {
        $this->secret = $this->twoFactorService->generateSecretKey();
        $this->generateQrCode();
    }

    protected function generateQrCode(): void
    {
        $user = Auth::user();
        $qrCodeUrl = $this->twoFactorService->getQRCodeUrl($user, $this->secret);

        try {
            if (class_exists(QrCode::class)) {
                $this->qrCodeSvg = QrCode::size(200)->generate($qrCodeUrl)->toHtml();
            } else {
                // A.63/XSS FIX: Escape secret in fallback HTML output
                $escapedSecret = htmlspecialchars($this->secret, ENT_QUOTES, 'UTF-8');
                $this->qrCodeSvg = '<div class="p-4 bg-slate-100 rounded text-center text-sm text-slate-600">'.
                    __('QR Code: Use the secret key below').'<br><code class="text-xs">'.$escapedSecret.'</code></div>';
            }
        } catch (\Exception $e) {
            // A.63/XSS FIX: Escape secret in fallback HTML output
            $escapedSecret = htmlspecialchars($this->secret, ENT_QUOTES, 'UTF-8');
            $this->qrCodeSvg = '<div class="p-4 bg-slate-100 rounded text-center text-sm text-slate-600">'.
                __('Secret Key').': <code class="text-xs">'.$escapedSecret.'</code></div>';
        }
    }

    public function enable(): void
    {
        $this->validate(['code' => 'required|string|size:6']);

        if (! $this->twoFactorService->verify($this->secret, $this->code)) {
            $this->addError('code', __('Invalid authentication code'));

            return;
        }

        $user = Auth::user();
        $this->twoFactorService->enableTwoFactor($user, $this->secret);

        $this->enabled = true;
        $this->recoveryCodes = $this->twoFactorService->getRecoveryCodes($user);
        $this->showRecoveryCodes = true;
        $this->reset(['code', 'secret']);

        session(['2fa_verified' => true]);
        session()->flash('success', __('Two-factor authentication has been enabled'));
    }

    public function disable(): void
    {
        $user = Auth::user();
        $this->twoFactorService->disableTwoFactor($user);

        $this->enabled = false;
        $this->showRecoveryCodes = false;
        $this->recoveryCodes = [];

        session()->forget('2fa_verified');
        session()->flash('success', __('Two-factor authentication has been disabled'));

        $this->generateNewSecret();
    }

    public function regenerateRecoveryCodes(): void
    {
        $user = Auth::user();
        $this->recoveryCodes = $this->twoFactorService->regenerateRecoveryCodes($user);
        $this->showRecoveryCodes = true;

        session()->flash('success', __('Recovery codes have been regenerated'));
    }

    public function render()
    {
        return view('livewire.auth.two-factor-setup')
            ->layout('layouts.app');
    }
}
