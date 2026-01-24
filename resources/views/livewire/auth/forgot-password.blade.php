<div class="space-y-4">
    <div class="space-y-1 text-center">
        <h1 class="text-lg font-semibold text-slate-800 dark:text-white">
            {{ __('Forgot Password') }}
        </h1>
        <p class="text-xs text-slate-500 dark:text-slate-400">
            {{ __('Enter your email to receive a password reset link') }}
        </p>
    </div>

    @if($emailSent)
        <div class="p-4 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded-lg">
            <div class="flex items-center gap-3">
                <div class="flex-shrink-0">
                    <svg class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-emerald-800 dark:text-emerald-200">
                        {{ __('Email Sent!') }}
                    </h3>
                    <p class="mt-1 text-xs text-emerald-700 dark:text-emerald-300">
                        {{ __('We\'ve sent a password reset link to your email. Please check your inbox and spam folder.') }}
                    </p>
                </div>
            </div>
        </div>

        <div class="text-center">
            <a href="{{ route('login') }}" 
               class="text-sm text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 hover:underline"
               wire:navigate>
                {{ __('Back to Login') }}
            </a>
        </div>
    @else
        <form wire:submit.prevent="sendResetLink" class="space-y-4">
            <div class="space-y-1">
                <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                    {{ __('Email Address') }}
                </label>
                <input id="email" type="email" wire:model="email" required autofocus
                       autocomplete="email"
                       placeholder="{{ __('Enter your email address') }}"
                       class="erp-input @error('email') border-red-500 ring-red-500 @enderror">
                @error('email')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400 flex items-center gap-1">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <button type="submit" 
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-75 cursor-not-allowed"
                        class="erp-btn-primary w-full justify-center relative">
                    <span wire:loading.remove wire:target="sendResetLink">
                        {{ __('Send Reset Link') }}
                    </span>
                    <span wire:loading wire:target="sendResetLink" class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ __('Sending...') }}
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
