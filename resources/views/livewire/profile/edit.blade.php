@php
    $locale = app()->getLocale();
    $dir = $locale === 'ar' ? 'rtl' : 'ltr';
@endphp

<div class="p-6 max-w-4xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-800">{{ __('My Profile') }}</h1>
        <p class="text-slate-500">{{ __('Manage your account settings and change your password') }}</p>
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

    <div class="space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-lg font-semibold text-slate-800 mb-6 flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                {{ __('Profile Information') }}
            </h2>

            <form wire:submit="updateProfile" class="space-y-4">
                <div class="flex items-center gap-6 mb-6">
                    <div class="relative">
                        @if ($currentAvatar)
                            <img src="{{ asset('storage/' . $currentAvatar) }}" alt="{{ $name }}" class="w-24 h-24 rounded-full object-cover border-4 border-white shadow-lg">
                        @else
                            <div class="w-24 h-24 rounded-full bg-emerald-100 flex items-center justify-center border-4 border-white shadow-lg">
                                <span class="text-3xl font-bold text-emerald-600">{{ substr($name, 0, 1) }}</span>
                            </div>
                        @endif
                    </div>
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Profile Photo') }}</label>
                        <livewire:components.media-picker 
                            :file-path="$currentAvatar"
                            accept-mode="image"
                            storage-scope="direct"
                            storage-path="avatars"
                            storage-disk="public"
                            :max-size="2048"
                            field-id="profile-avatar"
                            wire:key="profile-avatar-{{ $currentAvatar ?: 'empty' }}"
                        ></livewire:components.media-picker>
                        @error('avatar') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Full Name') }} *</label>
                        <input type="text" wire:model="name" class="erp-input w-full">
                        @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Email Address') }} *</label>
                        <input type="email" wire:model="email" class="erp-input w-full">
                        @error('email') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Phone Number') }}</label>
                        <input type="text" wire:model="phone" class="erp-input w-full">
                        @error('phone') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="pt-4">
                    <button type="submit" class="erp-btn-primary">
                        <span wire:loading.remove wire:target="updateProfile">{{ __('Save Changes') }}</span>
                        <span wire:loading wire:target="updateProfile">{{ __('Saving...') }}</span>
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-lg font-semibold text-slate-800 mb-6 flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                {{ __('Change Password') }}
            </h2>

            <form wire:submit="updatePassword" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Current Password') }} *</label>
                        <input type="password" wire:model="current_password" class="erp-input w-full">
                        @error('current_password') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('New Password') }} *</label>
                        <input type="password" wire:model="password" class="erp-input w-full">
                        @error('password') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Confirm Password') }} *</label>
                        <input type="password" wire:model="password_confirmation" class="erp-input w-full">
                    </div>
                </div>

                <div class="pt-4">
                    <button type="submit" class="erp-btn-primary">
                        <span wire:loading.remove wire:target="updatePassword">{{ __('Update Password') }}</span>
                        <span wire:loading wire:target="updatePassword">{{ __('Updating...') }}</span>
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-lg font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ __('Account Information') }}
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div class="p-4 bg-slate-50 rounded-xl">
                    <span class="text-slate-500 block mb-1">{{ __('Role') }}</span>
                    <span class="font-medium text-slate-800">{{ auth()->user()->getRoleNames()->implode(', ') ?: __('No Role') }}</span>
                </div>
                <div class="p-4 bg-slate-50 rounded-xl">
                    <span class="text-slate-500 block mb-1">{{ __('Branch') }}</span>
                    <span class="font-medium text-slate-800">{{ auth()->user()->branch?->name ?? __('All Branches') }}</span>
                </div>
                <div class="p-4 bg-slate-50 rounded-xl">
                    <span class="text-slate-500 block mb-1">{{ __('Member Since') }}</span>
                    <span class="font-medium text-slate-800">{{ auth()->user()->created_at->format('Y-m-d') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
