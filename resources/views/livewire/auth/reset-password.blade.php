<div class="space-y-4">
    <div class="space-y-1 text-center">
        <h1 class="text-lg font-semibold text-slate-800 dark:text-white">
            {{ __('Reset Password') }}
        </h1>
        <p class="text-xs text-slate-500 dark:text-slate-400">
            {{ __('Enter your new password below') }}
        </p>
    </div>

    @if($resetSuccess)
        <div class="p-4 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded-lg">
            <div class="flex items-center gap-3">
                <div class="flex-shrink-0">
                    <svg class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-emerald-800 dark:text-emerald-200">
                        {{ __('Password Reset Successful!') }}
                    </h3>
                    <p class="mt-1 text-xs text-emerald-700 dark:text-emerald-300">
                        {{ __('Your password has been reset. You can now login with your new password.') }}
                    </p>
                </div>
            </div>
        </div>

        <div class="text-center">
            <a href="{{ route('login') }}" 
               class="erp-btn-primary inline-flex items-center gap-2"
               wire:navigate>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                </svg>
                {{ __('Go to Login') }}
            </a>
        </div>
    @else
        <form wire:submit.prevent="resetPassword" class="space-y-4">
            <input type="hidden" wire:model="token">
            <input type="hidden" wire:model="email">

            <div class="space-y-1">
                <label for="password" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                    {{ __('New Password') }}
                </label>
                <div class="relative" x-data="{ showPassword: false }">
                    <input id="password" 
                           :type="showPassword ? 'text' : 'password'" 
                           wire:model="password" 
                           required
                           autocomplete="new-password"
                           placeholder="{{ __('Enter new password (min 8 characters)') }}"
                           class="erp-input pr-10 @error('password') border-red-500 ring-red-500 @enderror">
                    <button type="button" 
                            @click="showPassword = !showPassword"
                            class="absolute inset-y-0 ltr:right-0 rtl:left-0 flex items-center px-3 text-slate-400 hover:text-slate-600">
                        <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <svg x-show="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                        </svg>
                    </button>
                </div>
                @error('password')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400 flex items-center gap-1">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div class="space-y-1">
                <label for="password_confirmation" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                    {{ __('Confirm Password') }}
                </label>
                <input id="password_confirmation" type="password" wire:model="password_confirmation" required
                       autocomplete="new-password"
                       placeholder="{{ __('Confirm your new password') }}"
                       class="erp-input">
            </div>

            @error('email')
                <div class="p-3 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg">
                    <p class="text-xs text-red-600 dark:text-red-400 flex items-center gap-1">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        {{ $message }}
                    </p>
                </div>
            @enderror

            <div>
                <button type="submit" 
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-75 cursor-not-allowed"
                        class="erp-btn-primary w-full justify-center relative">
                    <span wire:loading.remove wire:target="resetPassword">
                        {{ __('Reset Password') }}
                    </span>
                    <span wire:loading wire:target="resetPassword" class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ __('Resetting...') }}
                    </span>
                </button>
            </div>
        </form>

        <div class="text-center">
            <a href="{{ route('login') }}" 
               class="text-sm text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 hover:underline inline-flex items-center gap-1"
               wire:navigate>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                {{ __('Back to Login') }}
            </a>
        </div>
    @endif
</div>
