<div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-900 dark:to-slate-800 py-12 px-4">
    <div class="max-w-3xl mx-auto">
        {{-- Header --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-emerald-100 dark:bg-emerald-900/30 mb-4">
                <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-slate-800 dark:text-white">{{ __('ERP Setup Wizard') }}</h1>
            <p class="text-slate-500 dark:text-slate-400 mt-2">{{ __('Configure your ERP system in a few simple steps') }}</p>
        </div>

        @if($setupComplete)
            {{-- Setup Complete Message --}}
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-8 text-center">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-emerald-100 dark:bg-emerald-900/30 mb-6">
                    <svg class="w-10 h-10 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-slate-800 dark:text-white mb-2">{{ __('Setup Complete!') }}</h2>
                <p class="text-slate-500 dark:text-slate-400 mb-6">{{ __('Your ERP system has been configured successfully.') }}</p>
                <a href="{{ route('dashboard') }}" class="erp-btn erp-btn-primary">{{ __('Go to Dashboard') }}</a>
            </div>
        @else
            {{-- Progress Steps --}}
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    @for($i = 1; $i <= $totalSteps; $i++)
                        <div class="flex-1 flex items-center">
                            <button type="button" wire:click="goToStep({{ $i }})"
                                    @if($i > $step + 1) disabled @endif
                                    class="flex items-center justify-center w-10 h-10 rounded-full font-semibold transition-all
                                        {{ $i < $step ? 'bg-emerald-500 text-white' : '' }}
                                        {{ $i == $step ? 'bg-emerald-500 text-white ring-4 ring-emerald-200 dark:ring-emerald-900' : '' }}
                                        {{ $i > $step ? 'bg-slate-200 dark:bg-slate-700 text-slate-500 dark:text-slate-400' : '' }}
                                        {{ $i <= $step + 1 ? 'cursor-pointer hover:scale-105' : 'cursor-not-allowed' }}">
                                @if($i < $step)
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                @else
                                    {{ $i }}
                                @endif
                            </button>
                            @if($i < $totalSteps)
                                <div class="flex-1 h-1 mx-2 rounded {{ $i < $step ? 'bg-emerald-500' : 'bg-slate-200 dark:bg-slate-700' }}"></div>
                            @endif
                        </div>
                    @endfor
                </div>
                <div class="flex justify-between mt-2 text-xs text-slate-500 dark:text-slate-400">
                    <span>{{ __('Company') }}</span>
                    <span>{{ __('Admin') }}</span>
                    <span>{{ __('Branch') }}</span>
                    <span>{{ __('Modules') }}</span>
                    <span>{{ __('Review') }}</span>
                </div>
            </div>

            {{-- Form Card --}}
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl overflow-hidden">
                {{-- Step 1: Company Info --}}
                @if($step === 1)
                    <div class="p-8">
                        <h2 class="text-xl font-bold text-slate-800 dark:text-white mb-6">{{ __('Company Information') }}</h2>
                        <div class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="erp-label">{{ __('Company Name (English)') }} <span class="text-red-500">*</span></label>
                                    <input type="text" wire:model="companyName" class="erp-input @error('companyName') border-red-500 @enderror" placeholder="ABC Company">
                                    @error('companyName') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="erp-label">{{ __('Company Name (Arabic)') }}</label>
                                    <input type="text" wire:model="companyNameAr" class="erp-input" dir="rtl" placeholder="شركة أي بي سي">
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="erp-label">{{ __('Email') }} <span class="text-red-500">*</span></label>
                                    <input type="email" wire:model="companyEmail" class="erp-input @error('companyEmail') border-red-500 @enderror" placeholder="info@company.com">
                                    @error('companyEmail') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="erp-label">{{ __('Phone') }}</label>
                                    <input type="text" wire:model="companyPhone" class="erp-input" placeholder="+20 123 456 7890">
                                </div>
                            </div>
                            <div>
                                <label class="erp-label">{{ __('Address') }}</label>
                                <textarea wire:model="companyAddress" class="erp-input" rows="2" placeholder="123 Main St, Cairo, Egypt"></textarea>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="erp-label">{{ __('Timezone') }} <span class="text-red-500">*</span></label>
                                    <select wire:model="timezone" class="erp-input">
                                        @foreach($timezones as $tz => $label)
                                            <option value="{{ $tz }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="erp-label">{{ __('Currency') }} <span class="text-red-500">*</span></label>
                                    <select wire:model="currency" class="erp-input">
                                        @foreach($currencies as $code => $label)
                                            <option value="{{ $code }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="erp-label">{{ __('Language') }} <span class="text-red-500">*</span></label>
                                    <select wire:model="locale" class="erp-input">
                                        <option value="ar">{{ __('Arabic') }}</option>
                                        <option value="en">{{ __('English') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Step 2: Admin User --}}
                @if($step === 2)
                    <div class="p-8">
                        <h2 class="text-xl font-bold text-slate-800 dark:text-white mb-6">{{ __('Administrator Account') }}</h2>
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 mb-6">
                            <div class="flex gap-3">
                                <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p class="text-sm text-blue-700 dark:text-blue-300">{{ __('This will be your main administrator account with full access to all system features.') }}</p>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="erp-label">{{ __('Full Name') }} <span class="text-red-500">*</span></label>
                                <input type="text" wire:model="adminName" class="erp-input @error('adminName') border-red-500 @enderror" placeholder="John Doe">
                                @error('adminName') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="erp-label">{{ __('Email') }} <span class="text-red-500">*</span></label>
                                <input type="email" wire:model="adminEmail" class="erp-input @error('adminEmail') border-red-500 @enderror" placeholder="admin@company.com">
                                @error('adminEmail') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="erp-label">{{ __('Password') }} <span class="text-red-500">*</span></label>
                                    <input type="password" wire:model="adminPassword" class="erp-input @error('adminPassword') border-red-500 @enderror" placeholder="••••••••">
                                    @error('adminPassword') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="erp-label">{{ __('Confirm Password') }} <span class="text-red-500">*</span></label>
                                    <input type="password" wire:model="adminPasswordConfirmation" class="erp-input" placeholder="••••••••">
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Step 3: Main Branch --}}
                @if($step === 3)
                    <div class="p-8">
                        <h2 class="text-xl font-bold text-slate-800 dark:text-white mb-6">{{ __('Main Branch Setup') }}</h2>
                        <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-4 mb-6">
                            <div class="flex gap-3">
                                <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                                <p class="text-sm text-amber-700 dark:text-amber-300">{{ __('This is your main headquarters. You can add more branches later.') }}</p>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="erp-label">{{ __('Branch Name') }} <span class="text-red-500">*</span></label>
                                    <input type="text" wire:model="branchName" class="erp-input @error('branchName') border-red-500 @enderror" placeholder="Main Branch">
                                    @error('branchName') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="erp-label">{{ __('Branch Code') }} <span class="text-red-500">*</span></label>
                                    <input type="text" wire:model="branchCode" class="erp-input @error('branchCode') border-red-500 @enderror" placeholder="HQ001">
                                    @error('branchCode') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <div>
                                <label class="erp-label">{{ __('Phone') }}</label>
                                <input type="text" wire:model="branchPhone" class="erp-input" placeholder="+20 123 456 7890">
                            </div>
                            <div>
                                <label class="erp-label">{{ __('Address') }}</label>
                                <textarea wire:model="branchAddress" class="erp-input" rows="2" placeholder="123 Main St, Cairo, Egypt"></textarea>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Step 4: Select Modules --}}
                @if($step === 4)
                    <div class="p-8">
                        <h2 class="text-xl font-bold text-slate-800 dark:text-white mb-6">{{ __('Select Modules') }}</h2>
                        <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-lg p-4 mb-6">
                            <div class="flex gap-3">
                                <svg class="w-5 h-5 text-emerald-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p class="text-sm text-emerald-700 dark:text-emerald-300">{{ __('Choose which modules to enable for your business. Core modules are required and pre-selected.') }}</p>
                            </div>
                        </div>
                        @error('selectedModules') 
                            <div class="bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 rounded-lg p-3 mb-4">
                                {{ $message }}
                            </div>
                        @enderror
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($modules as $module)
                                <label class="relative flex items-start p-4 rounded-xl border-2 cursor-pointer transition-all
                                    {{ in_array((string)$module->id, $selectedModules) ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20' : 'border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600' }}
                                    {{ $module->is_core ? 'opacity-75' : '' }}">
                                    <input type="checkbox" wire:model="selectedModules" value="{{ $module->id }}" 
                                           class="sr-only"
                                           @if($module->is_core) disabled checked @endif>
                                    <div class="flex-shrink-0 w-10 h-10 rounded-lg flex items-center justify-center text-xl" style="background-color: {{ $module->color ?? '#10b981' }}20">
                                        {{ $module->display_icon }}
                                    </div>
                                    <div class="ml-3 flex-1">
                                        <p class="font-medium text-slate-800 dark:text-white">{{ $module->localizedName }}</p>
                                        @if($module->localizedDescription)
                                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ Str::limit($module->localizedDescription, 50) }}</p>
                                        @endif
                                        @if($module->is_core)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-700 mt-1">
                                                {{ __('Core') }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="absolute top-4 right-4">
                                        @if(in_array((string)$module->id, $selectedModules))
                                            <svg class="w-5 h-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                        @else
                                            <div class="w-5 h-5 rounded-full border-2 border-slate-300 dark:border-slate-600"></div>
                                        @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Step 5: Review --}}
                @if($step === 5)
                    <div class="p-8">
                        <h2 class="text-xl font-bold text-slate-800 dark:text-white mb-6">{{ __('Review & Complete') }}</h2>
                        <div class="space-y-6">
                            {{-- Company Summary --}}
                            <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl p-4">
                                <h3 class="font-semibold text-slate-800 dark:text-white mb-3 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                    {{ __('Company') }}
                                </h3>
                                <dl class="grid grid-cols-2 gap-2 text-sm">
                                    <dt class="text-slate-500 dark:text-slate-400">{{ __('Name') }}</dt>
                                    <dd class="text-slate-800 dark:text-white">{{ $companyName }}</dd>
                                    <dt class="text-slate-500 dark:text-slate-400">{{ __('Email') }}</dt>
                                    <dd class="text-slate-800 dark:text-white">{{ $companyEmail }}</dd>
                                    <dt class="text-slate-500 dark:text-slate-400">{{ __('Currency') }}</dt>
                                    <dd class="text-slate-800 dark:text-white">{{ $currency }}</dd>
                                </dl>
                            </div>

                            {{-- Admin Summary --}}
                            <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl p-4">
                                <h3 class="font-semibold text-slate-800 dark:text-white mb-3 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    {{ __('Administrator') }}
                                </h3>
                                <dl class="grid grid-cols-2 gap-2 text-sm">
                                    <dt class="text-slate-500 dark:text-slate-400">{{ __('Name') }}</dt>
                                    <dd class="text-slate-800 dark:text-white">{{ $adminName }}</dd>
                                    <dt class="text-slate-500 dark:text-slate-400">{{ __('Email') }}</dt>
                                    <dd class="text-slate-800 dark:text-white">{{ $adminEmail }}</dd>
                                </dl>
                            </div>

                            {{-- Branch Summary --}}
                            <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl p-4">
                                <h3 class="font-semibold text-slate-800 dark:text-white mb-3 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    {{ __('Main Branch') }}
                                </h3>
                                <dl class="grid grid-cols-2 gap-2 text-sm">
                                    <dt class="text-slate-500 dark:text-slate-400">{{ __('Name') }}</dt>
                                    <dd class="text-slate-800 dark:text-white">{{ $branchName }}</dd>
                                    <dt class="text-slate-500 dark:text-slate-400">{{ __('Code') }}</dt>
                                    <dd class="text-slate-800 dark:text-white">{{ $branchCode }}</dd>
                                </dl>
                            </div>

                            {{-- Modules Summary --}}
                            <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl p-4">
                                <h3 class="font-semibold text-slate-800 dark:text-white mb-3 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                                    </svg>
                                    {{ __('Selected Modules') }} ({{ count($selectedModules) }})
                                </h3>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($modules as $module)
                                        @if(in_array((string)$module->id, $selectedModules))
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                                                {{ $module->display_icon }} {{ $module->localizedName }}
                                            </span>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Footer Actions --}}
                <div class="bg-slate-50 dark:bg-slate-700/50 px-8 py-4 flex items-center justify-between">
                    <div>
                        @if($step > 1)
                            <button type="button" wire:click="previousStep" class="erp-btn erp-btn-secondary">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                </svg>
                                {{ __('Previous') }}
                            </button>
                        @else
                            <button type="button" wire:click="skipSetup" class="text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300">
                                {{ __('Skip Setup') }}
                            </button>
                        @endif
                    </div>
                    <div>
                        @if($step < $totalSteps)
                            <button type="button" wire:click="nextStep" class="erp-btn erp-btn-primary">
                                {{ __('Next') }}
                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                        @else
                            <button type="button" wire:click="completeSetup" class="erp-btn erp-btn-primary">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                {{ __('Complete Setup') }}
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
