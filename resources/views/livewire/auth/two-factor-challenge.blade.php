<div class="text-center">
    <div class="mb-6">
        <div class="w-16 h-16 mx-auto bg-emerald-100 rounded-full flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
        </div>
        <h2 class="text-xl font-bold text-slate-800">{{ __('Two-Factor Authentication') }}</h2>
        <p class="text-slate-500 text-sm mt-1">
            @if ($useRecovery)
                {{ __('Enter one of your recovery codes') }}
            @else
                {{ __('Enter the code from your authenticator app') }}
            @endif
        </p>
    </div>

    <form wire:submit="verify" class="space-y-4">
        @if ($useRecovery)
            <div>
                <input type="text" wire:model="recoveryCode" 
                    class="erp-input w-full text-center font-mono tracking-widest"
                    placeholder="XXXXX-XXXXX"
                    autofocus>
                @error('recoveryCode')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
        @else
            <div>
                <input type="text" wire:model="code" 
                    class="erp-input w-full text-center text-2xl tracking-widest"
                    placeholder="000000"
                    maxlength="6"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    autofocus>
                @error('code')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
        @endif

        <button type="submit" class="erp-btn-primary w-full">
            <span wire:loading.remove>{{ __('Verify') }}</span>
            <span wire:loading>{{ __('Verifying...') }}</span>
        </button>
    </form>

    <div class="mt-4">
        <button wire:click="toggleRecovery" class="text-sm text-emerald-600 hover:text-emerald-700">
            @if ($useRecovery)
                {{ __('Use authentication code instead') }}
            @else
                {{ __('Use a recovery code') }}
            @endif
        </button>
    </div>

    <div class="mt-6 pt-4 border-t border-slate-200">
        <a href="{{ route('logout') }}" class="text-sm text-slate-500 hover:text-slate-700"
            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
            {{ __('Sign out') }}
        </a>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
            @csrf
        </form>
    </div>
</div>
