<div class="space-y-4">
    <div class="space-y-1 text-center">
        <h1 class="text-xl font-bold text-slate-800">
            {{ __('Sign in') }}
        </h1>
        <p class="text-sm text-slate-500">
            {{ __('Use your ERP account to continue') }}
        </p>
    </div>

    <form wire:submit.prevent="login" class="space-y-4">
        <div class="space-y-1.5">
            <label for="credential" class="block text-sm font-medium text-slate-700">
                {{ __('Email, Phone or Username') }}
            </label>
            <input id="credential" type="text" wire:model.live="credential" autofocus required
                   autocapitalize="none" spellcheck="false"
                   autocomplete="username"
                   placeholder="{{ __('Enter email, phone or username') }}"
                   class="erp-input @error('credential') !border-red-500 !ring-red-500/20 @enderror">
            @error('credential')
                <div class="mt-2 p-2.5 bg-red-50 rounded-lg border border-red-200">
                    <p class="text-xs text-red-700 flex items-center gap-2">
                        <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <span>{{ $message }}</span>
                    </p>
                </div>
            @enderror
        </div>

        <div class="space-y-1.5">
            <label for="password" class="block text-sm font-medium text-slate-700">
                {{ __('Password') }}
            </label>
            <div class="relative" x-data="{ showPassword: false }">
                <input id="password"
                       x-ref="passwordInput"
                       :type="showPassword ? 'text' : 'password'"
                       wire:model.live="password"
                       required
                       autocomplete="current-password"
                       placeholder="{{ __('Enter your password') }}"
                       class="erp-input ltr:pr-10 rtl:pl-10 @error('password') !border-red-500 !ring-red-500/20 @enderror">
                <button type="button" 
                        @click="showPassword = !showPassword; $nextTick(() => $refs.passwordInput.focus())"
                       class="absolute inset-y-0 ltr:right-0 rtl:left-0 flex items-center px-3 text-slate-400 hover:text-slate-600 transition-colors">
                    <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <svg x-show="showPassword" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                    </svg>
                </button>
            </div>
            @error('password')
                <div class="mt-2 p-2.5 bg-red-50 rounded-lg border border-red-200">
                    <p class="text-xs text-red-700 flex items-center gap-2">
                        <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <span>{{ $message }}</span>
                    </p>
                    @if($message === __('The password you entered is incorrect.'))
                        <div class="mt-2 pt-2 border-t border-red-200">
                            <a href="{{ route('password.request') }}" 
                               class="text-xs text-red-600 hover:text-red-800 font-medium hover:underline flex items-center gap-1"
                               wire:navigate>
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                </svg>
                                {{ __('Forgot your password? Reset it now') }}
                            </a>
                        </div>
                    @endif
                </div>
            @enderror
        </div>

        <div class="flex items-center justify-between">
            <label class="inline-flex items-center gap-2 text-sm text-slate-600 cursor-pointer">
                <input type="checkbox" wire:model="remember"
                       class="w-4 h-4 rounded border-slate-300 text-emerald-600 shadow-sm focus:ring-emerald-500 focus:ring-offset-0">
                <span>{{ __('Remember me') }}</span>
            </label>

            <a href="{{ route('password.request') }}" 
               class="text-sm text-emerald-600 hover:text-emerald-700 font-medium hover:underline transition-colors"
               wire:navigate>
                {{ __('Forgot password?') }}
            </a>
        </div>

        <div>
            <button type="submit" 
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-75 cursor-not-allowed"
                       class="erp-btn-primary w-full justify-center text-base py-3">
                <span wire:loading.remove wire:target="login">
                    {{ __('Sign in') }}
                </span>
                <span wire:loading wire:target="login" class="flex items-center justify-center gap-2">
                    <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ __('Signing in...') }}
                </span>
            </button>
        </div>
    </form>

    {{-- Info box about login options --}}
    <div class="mt-4 p-3 bg-emerald-50 rounded-xl border border-emerald-200">
        <p class="text-sm text-emerald-700 text-center flex items-center justify-center gap-2">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>{{ __('You can login using your email, phone number, or username') }}</span>
        </p>
    </div>
</div>
