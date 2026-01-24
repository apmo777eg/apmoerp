<div class="p-6">
@php
    $locale = app()->getLocale();
    $dir = $locale === 'ar' ? 'rtl' : 'ltr';
@endphp

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-800">{{ __('Advanced Settings') }}</h1>
        <p class="text-slate-500">{{ __('Configure system settings, SMS providers, security, and backups') }}</p>
    </div>

    @if (session('success'))
        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-xl text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700">
            {{ session('error') }}
        </div>
    @endif

    <div class="flex flex-col lg:flex-row gap-6">
        <div class="lg:w-64 flex-shrink-0">
            <nav class="bg-white rounded-2xl shadow-sm border border-slate-200 p-2 space-y-1">
                <button wire:click="setTab('general')" 
                    class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-{{ $dir === 'rtl' ? 'right' : 'left' }} transition-all {{ $activeTab === 'general' ? 'bg-emerald-50 text-emerald-700 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    {{ __('General') }}
                </button>

                <button wire:click="setTab('sms')" 
                    class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-{{ $dir === 'rtl' ? 'right' : 'left' }} transition-all {{ $activeTab === 'sms' ? 'bg-emerald-50 text-emerald-700 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                    </svg>
                    {{ __('SMS / WhatsApp') }}
                </button>

                <button wire:click="setTab('security')" 
                    class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-{{ $dir === 'rtl' ? 'right' : 'left' }} transition-all {{ $activeTab === 'security' ? 'bg-emerald-50 text-emerald-700 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    {{ __('Security') }}
                </button>

                <button wire:click="setTab('notifications')" 
                    class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-{{ $dir === 'rtl' ? 'right' : 'left' }} transition-all {{ $activeTab === 'notifications' ? 'bg-emerald-50 text-emerald-700 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    {{ __('Notifications') }}
                </button>

                <button wire:click="setTab('firebase')" 
                    class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-{{ $dir === 'rtl' ? 'right' : 'left' }} transition-all {{ $activeTab === 'firebase' ? 'bg-emerald-50 text-emerald-700 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z" />
                    </svg>
                    {{ __('Firebase Push') }}
                </button>

                <button wire:click="setTab('backup')" 
                    class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-{{ $dir === 'rtl' ? 'right' : 'left' }} transition-all {{ $activeTab === 'backup' ? 'bg-emerald-50 text-emerald-700 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                    </svg>
                    {{ __('Backup') }}
                </button>

                {{-- Divider --}}
                <div class="my-2 border-t border-slate-200"></div>
                <div class="px-4 py-2 text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ __('Advanced') }}</div>

                <button wire:click="setTab('performance')" 
                    class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-{{ $dir === 'rtl' ? 'right' : 'left' }} transition-all {{ $activeTab === 'performance' ? 'bg-emerald-50 text-emerald-700 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    {{ __('Performance') }}
                </button>

                <button wire:click="setTab('ui')" 
                    class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-{{ $dir === 'rtl' ? 'right' : 'left' }} transition-all {{ $activeTab === 'ui' ? 'bg-emerald-50 text-emerald-700 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                    </svg>
                    {{ __('UI/UX') }}
                </button>

                <button wire:click="setTab('export')" 
                    class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-{{ $dir === 'rtl' ? 'right' : 'left' }} transition-all {{ $activeTab === 'export' ? 'bg-emerald-50 text-emerald-700 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    {{ __('Export') }}
                </button>
            </nav>
        </div>

        <div class="flex-1">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                {{-- General Tab --}}
                @if ($activeTab === 'general')
                    <h2 class="text-lg font-semibold text-slate-800 mb-6">{{ __('General Settings') }}</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Application Name') }}</label>
                            <input type="text" wire:model="general.app_name" class="erp-input w-full">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Default Currency') }}</label>
                            <select wire:model="general.default_currency" class="erp-input w-full">
                                <option value="EGP">EGP - {{ __('Egyptian Pound') }}</option>
                                <option value="USD">USD - {{ __('US Dollar') }}</option>
                                <option value="EUR">EUR - {{ __('Euro') }}</option>
                                <option value="SAR">SAR - {{ __('Saudi Riyal') }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Default Language') }}</label>
                            <select wire:model="general.default_locale" class="erp-input w-full">
                                <option value="ar">{{ __('Arabic') }}</option>
                                <option value="en">{{ __('English') }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Timezone') }}</label>
                            <select wire:model="general.timezone" class="erp-input w-full">
                                <option value="Africa/Cairo">{{ __('Africa/Cairo (Egypt)') }}</option>
                                <option value="Asia/Riyadh">{{ __('Asia/Riyadh (Saudi Arabia)') }}</option>
                                <option value="Asia/Dubai">{{ __('Asia/Dubai (UAE)') }}</option>
                                <option value="Europe/London">{{ __('Europe/London (UK)') }}</option>
                            </select>
                        </div>
                        <div class="pt-4">
                            <button wire:click="saveGeneral" class="erp-btn-primary">
                                <span wire:loading.remove wire:target="saveGeneral">{{ __('Save') }}</span>
                                <span wire:loading wire:target="saveGeneral">{{ __('Saving...') }}</span>
                            </button>
                        </div>
                    </div>
                @endif

                {{-- SMS Tab --}}
                @if ($activeTab === 'sms')
                    <h2 class="text-lg font-semibold text-slate-800 mb-6">{{ __('SMS / WhatsApp Settings') }}</h2>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Active Provider') }}</label>
                        <select wire:model.live="sms.provider" class="erp-input w-full">
                            @foreach ($this->smsProviders as $key => $provider)
                                <option value="{{ $key }}">{{ $provider['name'] }} - {{ $provider['description'] }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-slate-500 mt-1">{{ __('Choose which provider to use for sending SMS/WhatsApp messages') }}</p>
                    </div>

                    {{-- 3shm Settings --}}
                    <div class="mb-6 p-4 bg-slate-50 rounded-xl border border-slate-200">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-medium text-slate-800">3shm (WhatsApp)</h3>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" wire:model="sms.3shm.enabled" class="w-4 h-4 text-emerald-600 rounded">
                                <span class="text-sm text-slate-600">{{ __('Enabled') }}</span>
                            </label>
                        </div>
                        
                        {{-- Help box for 3shm --}}
                        <div class="mb-4 p-3 bg-blue-50 rounded-lg border border-blue-200">
                            <div class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-blue-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div class="text-xs text-blue-800">
                                    <p class="font-medium">{{ __('How to get your 3shm keys:') }}</p>
                                    <ol class="list-decimal list-inside mt-1 space-y-0.5">
                                        <li>{{ __('Login to your 3shm dashboard') }}</li>
                                        <li>{{ __('Go to Settings > API Keys') }}</li>
                                        <li>{{ __('Copy App Key and Auth Key') }}</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('App Key') }}</label>
                                <input type="password" wire:model="sms.3shm.appkey" class="erp-input w-full" placeholder="{{ __('Paste your App Key here') }}">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Auth Key') }}</label>
                                <input type="password" wire:model="sms.3shm.authkey" class="erp-input w-full" placeholder="{{ __('Paste your Auth Key here') }}">
                            </div>
                        </div>
                        <label class="flex items-center gap-2 mt-3 cursor-pointer">
                            <input type="checkbox" wire:model="sms.3shm.sandbox" class="w-4 h-4 text-emerald-600 rounded">
                            <span class="text-sm text-slate-600">{{ __('Sandbox Mode (Testing)') }}</span>
                        </label>
                        <p class="text-xs text-slate-500 mt-2">{{ __('Supports text messages and file attachments (PDF, images)') }}</p>
                    </div>

                    {{-- SMSMISR Settings --}}
                    <div class="mb-6 p-4 bg-slate-50 rounded-xl border border-slate-200">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-medium text-slate-800">SMSMISR</h3>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" wire:model="sms.smsmisr.enabled" class="w-4 h-4 text-emerald-600 rounded">
                                <span class="text-sm text-slate-600">{{ __('Enabled') }}</span>
                            </label>
                        </div>
                        
                        {{-- Help box for SMSMISR --}}
                        <div class="mb-4 p-3 bg-blue-50 rounded-lg border border-blue-200">
                            <div class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-blue-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div class="text-xs text-blue-800">
                                    <p class="font-medium">{{ __('How to get your SMSMISR credentials:') }}</p>
                                    <ol class="list-decimal list-inside mt-1 space-y-0.5">
                                        <li>{{ __('Login to smsmisr.com') }}</li>
                                        <li>{{ __('Go to Account Settings') }}</li>
                                        <li>{{ __('Find your username and create a password for API access') }}</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Username') }}</label>
                                <input type="text" wire:model="sms.smsmisr.username" class="erp-input w-full">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Password') }}</label>
                                <input type="password" wire:model="sms.smsmisr.password" class="erp-input w-full">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Sender ID') }}</label>
                                <input type="text" wire:model="sms.smsmisr.sender_id" class="erp-input w-full">
                            </div>
                        </div>
                        <label class="flex items-center gap-2 mt-3 cursor-pointer">
                            <input type="checkbox" wire:model="sms.smsmisr.sandbox" class="w-4 h-4 text-emerald-600 rounded">
                            <span class="text-sm text-slate-600">{{ __('Sandbox Mode (Testing)') }}</span>
                        </label>
                        <p class="text-xs text-slate-500 mt-2">{{ __('Supports text messages only (SMS)') }}</p>
                    </div>

                    <div class="flex gap-3">
                        <button wire:click="saveSms" class="erp-btn-primary">
                            <span wire:loading.remove wire:target="saveSms">{{ __('Save') }}</span>
                            <span wire:loading wire:target="saveSms">{{ __('Saving...') }}</span>
                        </button>
                        <button wire:click="testSms" class="erp-btn-secondary">
                            {{ __('Test Connection') }}
                        </button>
                    </div>
                @endif

                {{-- Security Tab --}}
                @if ($activeTab === 'security')
                    <h2 class="text-lg font-semibold text-slate-800 mb-6">{{ __('Security Settings') }}</h2>
                    
                    <div class="space-y-6">
                        {{-- 2FA Section --}}
                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                            <h3 class="font-medium text-slate-800 mb-4">{{ __('Two-Factor Authentication (2FA)') }}</h3>
                            <div class="space-y-3">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" wire:model="security.2fa_enabled" class="w-4 h-4 text-emerald-600 rounded">
                                    <span class="text-sm text-slate-700">{{ __('Enable 2FA for users') }}</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" wire:model="security.2fa_required" class="w-4 h-4 text-emerald-600 rounded">
                                    <span class="text-sm text-slate-700">{{ __('Require 2FA for all users') }}</span>
                                </label>
                            </div>
                        </div>

                        {{-- reCAPTCHA Section --}}
                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                            <h3 class="font-medium text-slate-800 mb-4">{{ __('reCAPTCHA Protection') }}</h3>
                            <label class="flex items-center gap-2 cursor-pointer mb-4">
                                <input type="checkbox" wire:model.live="security.recaptcha_enabled" class="w-4 h-4 text-emerald-600 rounded">
                                <span class="text-sm text-slate-700">{{ __('Enable reCAPTCHA on login') }}</span>
                            </label>
                            @if ($security['recaptcha_enabled'])
                                {{-- Help for reCAPTCHA --}}
                                <div class="mb-4 p-3 bg-blue-50 rounded-lg border border-blue-200">
                                    <div class="flex items-start gap-2">
                                        <svg class="w-4 h-4 text-blue-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <div class="text-xs text-blue-800">
                                            <p class="font-medium">{{ __('How to get reCAPTCHA keys:') }}</p>
                                            <ol class="list-decimal list-inside mt-1 space-y-0.5">
                                                <li>{{ __('Go to') }} <a href="https://www.google.com/recaptcha/admin" target="_blank" class="underline">google.com/recaptcha/admin</a></li>
                                                <li>{{ __('Click "+" to create new site') }}</li>
                                                <li>{{ __('Choose reCAPTCHA v2 "I\'m not a robot"') }}</li>
                                                <li>{{ __('Add your domain and submit') }}</li>
                                                <li>{{ __('Copy Site Key and Secret Key') }}</li>
                                            </ol>
                                        </div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Site Key') }}</label>
                                        <input type="text" wire:model="security.recaptcha_site_key" class="erp-input w-full" placeholder="{{ __('Paste your Site Key here') }}">
                                        <p class="text-xs text-slate-500 mt-1">{{ __('Used on the frontend') }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Secret Key') }}</label>
                                        <input type="password" wire:model="security.recaptcha_secret_key" class="erp-input w-full" placeholder="{{ __('Paste your Secret Key here') }}">
                                        <p class="text-xs text-slate-500 mt-1">{{ __('Keep this private - used on server side') }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Session Management --}}
                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                            <h3 class="font-medium text-slate-800 mb-4">{{ __('Session Management') }}</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Max Active Sessions') }}</label>
                                    <input type="number" wire:model="security.max_sessions" min="1" max="10" class="erp-input w-full">
                                    <p class="text-xs text-slate-500 mt-1">{{ __('Maximum number of simultaneous logins per user') }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Session Lifetime (minutes)') }}</label>
                                    <input type="number" wire:model="security.session_lifetime" min="15" max="10080" class="erp-input w-full">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Password Expiry (days)') }}</label>
                                    <input type="number" wire:model="security.password_expiry_days" min="0" max="365" class="erp-input w-full">
                                    <p class="text-xs text-slate-500 mt-1">{{ __('0 = Never expire') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4">
                        <button wire:click="saveSecurity" class="erp-btn-primary">
                            <span wire:loading.remove wire:target="saveSecurity">{{ __('Save') }}</span>
                            <span wire:loading wire:target="saveSecurity">{{ __('Saving...') }}</span>
                        </button>
                    </div>
                @endif

                {{-- Notifications Tab --}}
                @if ($activeTab === 'notifications')
                    <h2 class="text-lg font-semibold text-slate-800 mb-6">{{ __('Notification Settings') }}</h2>
                    
                    <div class="space-y-6">
                        {{-- Low Stock Alerts --}}
                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                            <h3 class="font-medium text-slate-800 mb-4">{{ __('Low Stock Alerts') }}</h3>
                            <label class="flex items-center gap-2 cursor-pointer mb-4">
                                <input type="checkbox" wire:model.live="notifications.low_stock_enabled" class="w-4 h-4 text-emerald-600 rounded">
                                <span class="text-sm text-slate-700">{{ __('Enable low stock notifications') }}</span>
                            </label>
                            @if ($notifications['low_stock_enabled'])
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Default threshold') }}</label>
                                    <input type="number" wire:model="notifications.low_stock_threshold" min="1" class="erp-input w-48">
                                </div>
                            @endif
                        </div>

                        {{-- Rental Reminders --}}
                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                            <h3 class="font-medium text-slate-800 mb-4">{{ __('Rental Reminders') }}</h3>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Days before due date') }}</label>
                                <input type="number" wire:model="notifications.rental_reminder_days" min="1" max="30" class="erp-input w-48">
                            </div>
                        </div>

                        {{-- Late Payment Penalties --}}
                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                            <h3 class="font-medium text-slate-800 mb-4">{{ __('Late Payment Penalties') }}</h3>
                            <label class="flex items-center gap-2 cursor-pointer mb-4">
                                <input type="checkbox" wire:model.live="notifications.late_payment_enabled" class="w-4 h-4 text-emerald-600 rounded">
                                <span class="text-sm text-slate-700">{{ __('Enable late payment penalties') }}</span>
                            </label>
                            @if ($notifications['late_payment_enabled'])
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Penalty percentage') }}</label>
                                    <div class="flex items-center gap-2">
                                        <input type="number" wire:model="notifications.late_penalty_percent" min="0" max="100" step="0.5" class="erp-input w-32">
                                        <span class="text-slate-600">%</span>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="pt-4">
                        <button wire:click="saveNotifications" class="erp-btn-primary">
                            <span wire:loading.remove wire:target="saveNotifications">{{ __('Save') }}</span>
                            <span wire:loading wire:target="saveNotifications">{{ __('Saving...') }}</span>
                        </button>
                    </div>
                @endif

                {{-- Firebase Tab --}}
                @if ($activeTab === 'firebase')
                    <h2 class="text-lg font-semibold text-slate-800 mb-6">{{ __('Firebase Push Notifications') }}</h2>
                    
                    <div class="space-y-6">
                        {{-- Step-by-step guide --}}
                        <div class="p-4 bg-gradient-to-r from-amber-50 to-orange-50 rounded-xl border border-amber-200">
                            <div class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-amber-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div class="text-sm text-amber-900">
                                    <p class="font-semibold mb-2">{{ __('How to set up Firebase (Step by Step):') }}</p>
                                    <ol class="list-decimal list-inside space-y-1 text-amber-800">
                                        <li>{{ __('Go to') }} <a href="https://console.firebase.google.com" target="_blank" class="underline font-medium">console.firebase.google.com</a></li>
                                        <li>{{ __('Create a new project or select existing one') }}</li>
                                        <li>{{ __('Click on Project Settings (gear icon)') }}</li>
                                        <li>{{ __('Scroll down to "Your apps" and click "Add app" > Web (</> icon)') }}</li>
                                        <li>{{ __('Copy the configuration values below') }}</li>
                                        <li>{{ __('For VAPID key: Go to Cloud Messaging tab > Web Push certificates') }}</li>
                                    </ol>
                                </div>
                            </div>
                        </div>

                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model.live="firebase.enabled" class="w-4 h-4 text-emerald-600 rounded">
                            <span class="text-sm text-slate-700 font-medium">{{ __('Enable Firebase Push Notifications') }}</span>
                        </label>

                        @if ($firebase['enabled'])
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('API Key') }} *</label>
                                    <input type="password" wire:model="firebase.api_key" class="erp-input w-full" placeholder="{{ __('Starts with AIzaSy...') }}">
                                    <p class="text-xs text-slate-500 mt-1">{{ __('Found in Project Settings > General') }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Project ID') }} *</label>
                                    <input type="text" wire:model="firebase.project_id" class="erp-input w-full" placeholder="{{ __('e.g. my-app-12345') }}">
                                    <p class="text-xs text-slate-500 mt-1">{{ __('Your Firebase project identifier') }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Auth Domain') }}</label>
                                    <input type="text" wire:model="firebase.auth_domain" class="erp-input w-full" placeholder="{{ __('e.g. my-app.firebaseapp.com') }}">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Storage Bucket') }}</label>
                                    <input type="text" wire:model="firebase.storage_bucket" class="erp-input w-full" placeholder="{{ __('e.g. my-app.appspot.com') }}">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Messaging Sender ID') }}</label>
                                    <input type="text" wire:model="firebase.messaging_sender_id" class="erp-input w-full" placeholder="{{ __('e.g. 123456789012') }}">
                                    <p class="text-xs text-slate-500 mt-1">{{ __('A 12-digit number') }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('App ID') }}</label>
                                    <input type="text" wire:model="firebase.app_id" class="erp-input w-full" placeholder="{{ __('e.g. 1:123...:web:abc...') }}">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('VAPID Key (for Web Push)') }}</label>
                                    <input type="password" wire:model="firebase.vapid_key" class="erp-input w-full" placeholder="{{ __('Long key starting with B...') }}">
                                    <p class="text-xs text-slate-500 mt-1">{{ __('Project Settings > Cloud Messaging > Web Push certificates > Key pair') }}</p>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="pt-4">
                        <button wire:click="saveFirebase" class="erp-btn-primary">
                            <span wire:loading.remove wire:target="saveFirebase">{{ __('Save') }}</span>
                            <span wire:loading wire:target="saveFirebase">{{ __('Saving...') }}</span>
                        </button>
                    </div>
                @endif

                {{-- Backup Tab --}}
                @if ($activeTab === 'backup')
                    <h2 class="text-lg font-semibold text-slate-800 mb-6">{{ __('Backup Settings') }}</h2>
                    
                    <div class="space-y-6">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model.live="backup.enabled" class="w-4 h-4 text-emerald-600 rounded">
                            <span class="text-sm text-slate-700">{{ __('Enable automatic backups') }}</span>
                        </label>

                        @if ($backup['enabled'])
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Frequency') }}</label>
                                    <select wire:model="backup.frequency" class="erp-input w-full">
                                        <option value="daily">{{ __('Daily') }}</option>
                                        <option value="weekly">{{ __('Weekly') }}</option>
                                        <option value="monthly">{{ __('Monthly') }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Time') }}</label>
                                    <input type="time" wire:model="backup.time" class="erp-input w-full">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Retention (days)') }}</label>
                                    <input type="number" wire:model="backup.retention_days" min="1" max="365" class="erp-input w-full">
                                </div>
                            </div>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" wire:model="backup.include_uploads" class="w-4 h-4 text-emerald-600 rounded">
                                <span class="text-sm text-slate-700">{{ __('Include uploaded files') }}</span>
                            </label>
                        @endif
                    </div>

                    <div class="pt-4">
                        <button wire:click="saveBackup" class="erp-btn-primary">
                            <span wire:loading.remove wire:target="saveBackup">{{ __('Save') }}</span>
                            <span wire:loading wire:target="saveBackup">{{ __('Saving...') }}</span>
                        </button>
                    </div>
                @endif

                {{-- Performance Tab --}}
                @if ($activeTab === 'performance')
                    <h2 class="text-lg font-semibold text-slate-800 mb-6">{{ __('Performance Settings') }}</h2>
                    
                    <div class="space-y-6">
                        {{-- Caching Section --}}
                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                            <h3 class="font-medium text-slate-800 mb-4">{{ __('Caching & Optimization') }}</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Cache TTL (seconds)') }}</label>
                                    <input type="number" wire:model="performance.cache_ttl" min="60" max="86400" class="erp-input w-full">
                                    <p class="text-xs text-slate-500 mt-1">{{ __('How long data stays cached (60-86400)') }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Default Pagination') }}</label>
                                    <select wire:model="performance.pagination_default" class="erp-input w-full">
                                        <option value="10">10 {{ __('items') }}</option>
                                        <option value="15">15 {{ __('items') }}</option>
                                        <option value="25">25 {{ __('items') }}</option>
                                        <option value="50">50 {{ __('items') }}</option>
                                        <option value="100">100 {{ __('items') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- Livewire Settings --}}
                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                            <h3 class="font-medium text-slate-800 mb-4">{{ __('Livewire & SPA Settings') }}</h3>
                            <div class="space-y-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" wire:model="performance.lazy_load_components" class="w-4 h-4 text-emerald-600 rounded">
                                    <span class="text-sm text-slate-700">{{ __('Enable lazy loading for components') }}</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" wire:model="performance.spa_navigation_enabled" class="w-4 h-4 text-emerald-600 rounded">
                                    <span class="text-sm text-slate-700">{{ __('Enable SPA navigation (faster page loads)') }}</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" wire:model="performance.show_progress_bar" class="w-4 h-4 text-emerald-600 rounded">
                                    <span class="text-sm text-slate-700">{{ __('Show progress bar during navigation') }}</span>
                                </label>
                                <div class="flex items-center gap-3">
                                    <label class="text-sm text-slate-700">{{ __('Progress bar color') }}:</label>
                                    <input type="color" wire:model="performance.progress_bar_color" class="h-8 w-12 rounded cursor-pointer">
                                </div>
                            </div>
                        </div>

                        {{-- Query Logging --}}
                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                            <h3 class="font-medium text-slate-800 mb-4">{{ __('Query Monitoring') }}</h3>
                            <div class="space-y-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" wire:model.live="performance.enable_query_logging" class="w-4 h-4 text-emerald-600 rounded">
                                    <span class="text-sm text-slate-700">{{ __('Enable slow query logging') }}</span>
                                </label>
                                @if($performance['enable_query_logging'])
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Slow query threshold (ms)') }}</label>
                                        <input type="number" wire:model="performance.slow_query_threshold" min="10" max="5000" class="erp-input w-full">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Max payload size (KB)') }}</label>
                                        <input type="number" wire:model="performance.max_payload_size" min="512" max="10240" class="erp-input w-full">
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="pt-4">
                        <button wire:click="savePerformance" class="erp-btn-primary">
                            <span wire:loading.remove wire:target="savePerformance">{{ __('Save') }}</span>
                            <span wire:loading wire:target="savePerformance">{{ __('Saving...') }}</span>
                        </button>
                    </div>
                @endif

                {{-- UI/UX Tab --}}
                @if ($activeTab === 'ui')
                    <h2 class="text-lg font-semibold text-slate-800 mb-6">{{ __('UI/UX Settings') }}</h2>
                    
                    <div class="space-y-6">
                        {{-- Layout Section --}}
                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                            <h3 class="font-medium text-slate-800 mb-4">{{ __('Layout & Navigation') }}</h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Sidebar default state') }}</label>
                                    <select wire:model="ui.sidebar_collapsed" class="erp-input w-full">
                                        <option value="auto">{{ __('Auto (based on screen size)') }}</option>
                                        <option value="expanded">{{ __('Always expanded') }}</option>
                                        <option value="collapsed">{{ __('Always collapsed') }}</option>
                                    </select>
                                </div>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" wire:model="ui.show_breadcrumbs" class="w-4 h-4 text-emerald-600 rounded">
                                    <span class="text-sm text-slate-700">{{ __('Show breadcrumbs navigation') }}</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" wire:model="ui.compact_tables" class="w-4 h-4 text-emerald-600 rounded">
                                    <span class="text-sm text-slate-700">{{ __('Use compact tables (smaller row height)') }}</span>
                                </label>
                            </div>
                        </div>

                        {{-- Interaction Section --}}
                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                            <h3 class="font-medium text-slate-800 mb-4">{{ __('Interaction & Feedback') }}</h3>
                            <div class="space-y-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" wire:model="ui.enable_keyboard_shortcuts" class="w-4 h-4 text-emerald-600 rounded">
                                    <span class="text-sm text-slate-700">{{ __('Enable keyboard shortcuts') }}</span>
                                </label>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Toast notification position') }}</label>
                                        <select wire:model="ui.toast_position" class="erp-input w-full">
                                            <option value="top-right">{{ __('Top Right') }}</option>
                                            <option value="top-left">{{ __('Top Left') }}</option>
                                            <option value="bottom-right">{{ __('Bottom Right') }}</option>
                                            <option value="bottom-left">{{ __('Bottom Left') }}</option>
                                            <option value="top-center">{{ __('Top Center') }}</option>
                                            <option value="bottom-center">{{ __('Bottom Center') }}</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Toast duration (seconds)') }}</label>
                                        <input type="number" wire:model="ui.toast_duration" min="2" max="30" class="erp-input w-full">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Auto-Save Section --}}
                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                            <h3 class="font-medium text-slate-800 mb-4">{{ __('Auto-Save') }}</h3>
                            <div class="space-y-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" wire:model.live="ui.auto_save_forms" class="w-4 h-4 text-emerald-600 rounded">
                                    <span class="text-sm text-slate-700">{{ __('Enable auto-save for forms') }}</span>
                                </label>
                                @if($ui['auto_save_forms'])
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Auto-save interval (seconds)') }}</label>
                                    <input type="number" wire:model="ui.auto_save_interval" min="10" max="300" class="erp-input w-48">
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="pt-4">
                        <button wire:click="saveUi" class="erp-btn-primary">
                            <span wire:loading.remove wire:target="saveUi">{{ __('Save') }}</span>
                            <span wire:loading wire:target="saveUi">{{ __('Saving...') }}</span>
                        </button>
                    </div>
                @endif

                {{-- Export Tab --}}
                @if ($activeTab === 'export')
                    <h2 class="text-lg font-semibold text-slate-800 mb-6">{{ __('Export Settings') }}</h2>
                    
                    <div class="space-y-6">
                        {{-- General Export Settings --}}
                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                            <h3 class="font-medium text-slate-800 mb-4">{{ __('Default Export Settings') }}</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Default format') }}</label>
                                    <select wire:model="export.default_format" class="erp-input w-full">
                                        <option value="xlsx">{{ __('Excel (.xlsx)') }}</option>
                                        <option value="csv">{{ __('CSV (.csv)') }}</option>
                                        <option value="pdf">{{ __('PDF (.pdf)') }}</option>
                                        <option value="json">{{ __('JSON (.json)') }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Max rows per export') }}</label>
                                    <input type="number" wire:model="export.max_export_rows" min="100" max="100000" class="erp-input w-full">
                                </div>
                            </div>
                            <div class="mt-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" wire:model="export.include_headers" class="w-4 h-4 text-emerald-600 rounded">
                                    <span class="text-sm text-slate-700">{{ __('Include headers in exports') }}</span>
                                </label>
                            </div>
                        </div>

                        {{-- Performance Settings --}}
                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                            <h3 class="font-medium text-slate-800 mb-4">{{ __('Export Performance') }}</h3>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Chunk size (rows per batch)') }}</label>
                                <input type="number" wire:model="export.chunk_size" min="100" max="10000" class="erp-input w-48">
                                <p class="text-xs text-slate-500 mt-1">{{ __('Larger values = faster export, but more memory usage') }}</p>
                            </div>
                        </div>

                        {{-- PDF Settings --}}
                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                            <h3 class="font-medium text-slate-800 mb-4">{{ __('PDF Export Settings') }}</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Page orientation') }}</label>
                                    <select wire:model="export.pdf_orientation" class="erp-input w-full">
                                        <option value="portrait">{{ __('Portrait') }}</option>
                                        <option value="landscape">{{ __('Landscape') }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Paper size') }}</label>
                                    <select wire:model="export.pdf_paper_size" class="erp-input w-full">
                                        <option value="a4">{{ __('A4') }}</option>
                                        <option value="letter">{{ __('Letter') }}</option>
                                        <option value="legal">{{ __('Legal') }}</option>
                                        <option value="a3">{{ __('A3') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4">
                        <button wire:click="saveExport" class="erp-btn-primary">
                            <span wire:loading.remove wire:target="saveExport">{{ __('Save') }}</span>
                            <span wire:loading wire:target="saveExport">{{ __('Saving...') }}</span>
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>