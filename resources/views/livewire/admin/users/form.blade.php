<div class="space-y-4">
    <div class="flex items-center justify-between gap-2">
        <div>
            <h1 class="text-lg font-semibold text-slate-800 dark:text-slate-200">
                {{ $userId ? __('Edit User') : __('Create User') }}
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                {{ __('User information, branch assignment, and roles.') }}
            </p>
        </div>
    </div>

    <form wire:submit.prevent="save" class="space-y-6">
        {{-- Basic Information --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
            <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-3">{{ __('Basic Information') }}</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="space-y-1">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                        {{ __('Name') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" wire:model="form.name" class="erp-input">
                    @error('form.name')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-1">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                        {{ __('Email') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="email" wire:model="form.email" class="erp-input">
                    @error('form.email')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-1">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                        {{ __('Phone') }}
                    </label>
                    <input type="text" wire:model="form.phone" class="erp-input">
                    @error('form.phone')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-1">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                        {{ __('Username') }}
                    </label>
                    <input type="text" wire:model="form.username" class="erp-input">
                    @error('form.username')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Branch & Modules --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
            <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-3">{{ __('Branch Assignment') }}</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="space-y-1">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                        {{ __('Branch') }} <span class="text-red-500">*</span>
                    </label>
                    <select wire:model.live="form.branch_id" class="erp-input">
                        <option value="">{{ __('Select branch') }}</option>
                        @if(is_array($branches) || is_object($branches))
                            @foreach($branches as $branch)
                                @if(is_object($branch))
                                    <option value="{{ $branch->id ?? '' }}">{{ $branch->name ?? '' }}</option>
                                @elseif(is_array($branch))
                                    <option value="{{ $branch['id'] ?? '' }}">{{ $branch['name'] ?? '' }}</option>
                                @endif
                            @endforeach
                        @endif
                    </select>
                    @error('form.branch_id')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-1">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300 mt-6">
                        <input type="checkbox" wire:model="form.is_active"
                               class="rounded border-slate-300 text-emerald-600 shadow-sm focus:ring-emerald-500 dark:bg-slate-700 dark:border-slate-600">
                        <span>{{ __('Active') }}</span>
                    </label>
                    @error('form.is_active')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Show branch enabled modules --}}
            @if(!empty($branchModules))
                <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                    <p class="text-xs text-blue-700 dark:text-blue-300 mb-2">
                        <svg class="inline w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        {{ __('This branch has access to the following modules:') }}
                    </p>
                    <div class="flex flex-wrap gap-2">
                        @if(is_array($branchModules))
                            @foreach($branchModules as $moduleKey)
                                <span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded dark:bg-blue-800 dark:text-blue-200">
                                    {{ ucfirst($moduleKey ?? '') }}
                                </span>
                            @endforeach
                        @endif
                    </div>
                </div>
            @elseif($form['branch_id'])
                <div class="mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800">
                    <p class="text-xs text-yellow-700 dark:text-yellow-300">
                        <svg class="inline w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        {{ __('No modules are enabled for this branch. Please configure branch modules first.') }}
                    </p>
                </div>
            @endif
        </div>

        {{-- Localization --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
            <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-3">{{ __('Localization') }}</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="space-y-1">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                        {{ __('Language') }}
                    </label>
                    <select wire:model="form.locale" class="erp-input">
                        <option value="ar">{{ __('Arabic') }}</option>
                        <option value="en">{{ __('English') }}</option>
                    </select>
                    @error('form.locale')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-1">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                        {{ __('Timezone') }}
                    </label>
                    <select wire:model="form.timezone" class="erp-input">
                        <option value="Africa/Cairo">{{ __('Africa/Cairo (EET, UTC+2)') }}</option>
                        <option value="Asia/Riyadh">{{ __('Asia/Riyadh (AST, UTC+3)') }}</option>
                        <option value="Asia/Dubai">{{ __('Asia/Dubai (GST, UTC+4)') }}</option>
                        <option value="Asia/Kuwait">{{ __('Asia/Kuwait (AST, UTC+3)') }}</option>
                        <option value="Asia/Bahrain">{{ __('Asia/Bahrain (AST, UTC+3)') }}</option>
                        <option value="Asia/Qatar">{{ __('Asia/Qatar (AST, UTC+3)') }}</option>
                        <option value="Asia/Amman">{{ __('Asia/Amman (EET, UTC+2)') }}</option>
                        <option value="Asia/Beirut">{{ __('Asia/Beirut (EET, UTC+2)') }}</option>
                        <option value="Asia/Damascus">{{ __('Asia/Damascus (EET, UTC+2)') }}</option>
                        <option value="Asia/Jerusalem">{{ __('Asia/Jerusalem (IST, UTC+2)') }}</option>
                        <option value="Europe/London">{{ __('Europe/London (GMT, UTC+0)') }}</option>
                        <option value="Europe/Paris">{{ __('Europe/Paris (CET, UTC+1)') }}</option>
                        <option value="America/New_York">{{ __('America/New_York (EST, UTC-5)') }}</option>
                        <option value="America/Los_Angeles">{{ __('America/Los_Angeles (PST, UTC-8)') }}</option>
                        <option value="UTC">{{ __('UTC (UTC+0)') }}</option>
                    </select>
                    @error('form.timezone')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Password --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
            <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-3">{{ __('Security') }}</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="space-y-1">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                        {{ $userId ? __('New Password (optional)') : __('Password') }}
                        @if(!$userId)<span class="text-red-500">*</span>@endif
                    </label>
                    <input type="password" wire:model="form.password" class="erp-input">
                    @error('form.password')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-1">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                        {{ __('Confirm Password') }}
                    </label>
                    <input type="password" wire:model="form.password_confirmation" class="erp-input">
                </div>
            </div>
        </div>


        {{-- Roles (web guard) --}}
        @if (!empty($availableRoles))
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    {{ __('Roles & Permissions') }}
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">
                    {{ __('Select one or more roles for this user. Roles define what actions the user can perform.') }}
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                    @if(is_array($availableRoles))
                        @foreach ($availableRoles as $role)
                            <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300 p-2 rounded hover:bg-slate-50 dark:hover:bg-slate-700 cursor-pointer">
                                <input type="checkbox"
                                       class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:bg-slate-700 dark:border-slate-600"
                                       value="{{ $role['id'] ?? '' }}"
                                       wire:model="selectedRoles">
                                <span>{{ $role['name'] ?? '' }}</span>
                        </label>
                    @endforeach
                @endif
                </div>
            </div>
        @endif


        <div class="flex justify-end gap-2">
            <a href="{{ route('admin.users.index') }}"
               class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:bg-slate-700 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-600">
                {{ __('Cancel') }}
            </a>
            <button type="submit" class="erp-btn-primary">
                {{ $userId ? __('Save changes') : __('Create user') }}
            </button>
        </div>
    </form>
</div>
