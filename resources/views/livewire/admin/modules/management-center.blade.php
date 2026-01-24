<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white flex items-center gap-2">
                <span class="text-3xl">🧩</span>
                {{ __('Module Management Center') }}
            </h1>
            <p class="text-sm text-slate-500 mt-1">
                {{ __('Comprehensive module administration, settings, and branch configuration') }}
            </p>
        </div>
        <button wire:click="syncNavigation" class="erp-btn-secondary">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            {{ __('Sync Navigation') }}
        </button>
    </div>

    {{-- Flash Messages --}}
    @if(session()->has('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-700 flex items-center gap-2">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    @if(session()->has('error'))
        <div class="p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 flex items-center gap-2">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            {{ session('error') }}
        </div>
    @endif

    {{-- Main Content Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Module List --}}
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                <h2 class="text-lg font-semibold text-slate-800 dark:text-white mb-4 flex items-center gap-2">
                    <span>📋</span>
                    {{ __('Modules') }} ({{ count($modules) }})
                </h2>
                
                <div class="space-y-2 max-h-[600px] overflow-y-auto">
                    @forelse($modules as $module)
                        <button 
                            wire:click="selectModule({{ $module['id'] }})"
                            class="w-full flex items-center gap-3 p-3 rounded-lg border transition-all duration-200 {{ $selectedModuleId === $module['id'] ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20' : 'border-slate-200 dark:border-slate-700 hover:border-emerald-300 hover:bg-slate-50 dark:hover:bg-slate-700' }}"
                        >
                            <span class="text-2xl">{{ $module['icon'] }}</span>
                            <div class="flex-1 text-left">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium text-slate-800 dark:text-white">
                                        {{ $module['name'] }}
                                    </span>
                                    @if($module['is_core'])
                                        <span class="px-2 py-0.5 text-xs font-medium bg-blue-100 text-blue-700 rounded">
                                            {{ __('Core') }}
                                        </span>
                                    @endif
                                </div>
                                <span class="text-xs text-slate-500">{{ ucfirst($module['module_type'] ?? 'functional') }}</span>
                            </div>
                            @if($module['is_active'])
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            @else
                                <span class="w-2 h-2 rounded-full bg-slate-300"></span>
                            @endif
                        </button>
                    @empty
                        <p class="text-center text-slate-500 py-4">{{ __('No modules found') }}</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Module Details & Configuration --}}
        <div class="lg:col-span-2 space-y-6">
            @if($selectedModule)
                {{-- Module Overview --}}
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center gap-4">
                            <span class="text-5xl">{{ $selectedModule['icon'] }}</span>
                            <div>
                                <h2 class="text-2xl font-bold text-slate-800 dark:text-white">
                                    {{ $selectedModule['name'] }}
                                </h2>
                                <p class="text-sm text-slate-500 mt-1">{{ $selectedModule['description'] }}</p>
                                <div class="flex items-center gap-2 mt-2">
                                    <span class="px-2 py-1 text-xs font-medium rounded {{ $selectedModule['is_active'] ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700' }}">
                                        {{ $selectedModule['is_active'] ? __('Active') : __('Inactive') }}
                                    </span>
                                    @if($selectedModule['is_core'])
                                        <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-700 rounded">
                                            {{ __('Core Module') }}
                                        </span>
                                    @endif
                                    <span class="px-2 py-1 text-xs font-medium bg-purple-100 text-purple-700 rounded">
                                        {{ ucfirst($selectedModule['module_type']) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <button wire:click="toggleModuleActive" class="erp-btn-secondary">
                            {{ $selectedModule['is_active'] ? __('Deactivate') : __('Activate') }}
                        </button>
                    </div>

                    {{-- Module Components Stats --}}
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mt-6">
                        <div class="p-4 bg-slate-50 dark:bg-slate-900 rounded-lg">
                            <div class="text-2xl font-bold text-emerald-600">{{ $selectedModule['navigation_count'] }}</div>
                            <div class="text-xs text-slate-600 dark:text-slate-400">{{ __('Navigation Items') }}</div>
                        </div>
                        <div class="p-4 bg-slate-50 dark:bg-slate-900 rounded-lg">
                            <div class="text-2xl font-bold text-blue-600">{{ $selectedModule['custom_fields_count'] }}</div>
                            <div class="text-xs text-slate-600 dark:text-slate-400">{{ __('Custom Fields') }}</div>
                        </div>
                        <div class="p-4 bg-slate-50 dark:bg-slate-900 rounded-lg">
                            <div class="text-2xl font-bold text-purple-600">{{ $selectedModule['settings_count'] }}</div>
                            <div class="text-xs text-slate-600 dark:text-slate-400">{{ __('Settings') }}</div>
                        </div>
                        <div class="p-4 bg-slate-50 dark:bg-slate-900 rounded-lg">
                            <div class="text-2xl font-bold text-orange-600">{{ $selectedModule['operations_count'] }}</div>
                            <div class="text-xs text-slate-600 dark:text-slate-400">{{ __('Operations') }}</div>
                        </div>
                        <div class="p-4 bg-slate-50 dark:bg-slate-900 rounded-lg">
                            <div class="text-2xl font-bold text-red-600">{{ $selectedModule['policies_count'] }}</div>
                            <div class="text-xs text-slate-600 dark:text-slate-400">{{ __('Policies') }}</div>
                        </div>
                        <div class="p-4 bg-slate-50 dark:bg-slate-900 rounded-lg">
                            <div class="text-2xl font-bold text-cyan-600">{{ $selectedModule['reports_count'] }}</div>
                            <div class="text-xs text-slate-600 dark:text-slate-400">{{ __('Reports') }}</div>
                        </div>
                    </div>

                    {{-- Quick Actions --}}
                    <div class="flex items-center gap-2 mt-6">
                        <a href="{{ route('admin.modules.fields', $selectedModule['id']) }}" class="erp-btn-secondary text-sm">
                            {{ __('Manage Fields') }}
                        </a>
                        @if(Route::has('admin.modules.settings'))
                        <a href="{{ route('admin.modules.settings', $selectedModule['id']) }}" class="erp-btn-secondary text-sm">
                            {{ __('Module Settings') }}
                        </a>
                        @endif
                        @if(Route::has('admin.modules.permissions'))
                        <a href="{{ route('admin.modules.permissions', $selectedModule['id']) }}" class="erp-btn-secondary text-sm">
                            {{ __('Permissions') }}
                        </a>
                        @endif
                    </div>
                </div>

                {{-- Branch Configuration --}}
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6">
                    <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4 flex items-center gap-2">
                        <span>🏢</span>
                        {{ __('Branch Configuration') }}
                    </h3>

                    {{-- Branch Selector --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            {{ __('Select Branch') }}
                        </label>
                        <select wire:model.live="selectedBranchId" class="erp-input">
                            <option value="">{{ __('Select a branch...') }}</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch['id'] }}">
                                    {{ $branch['name'] }} {{ $branch['is_main'] ? '(' . __('Main') . ')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Branch Module Settings --}}
                    @if($branchSettings)
                        <div class="space-y-4">
                            <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-900 rounded-lg">
                                <div>
                                    <div class="font-medium text-slate-800 dark:text-white">
                                        {{ __('Module Status for Branch') }}
                                    </div>
                                    @if($branchSettings['activated_at'])
                                        <div class="text-xs text-slate-500 mt-1">
                                            {{ __('Activated at') }}: {{ $branchSettings['activated_at'] }}
                                        </div>
                                    @endif
                                </div>
                                <button 
                                    wire:click="toggleModuleForBranch"
                                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $branchSettings['enabled'] ? 'bg-emerald-500' : 'bg-slate-300' }}"
                                >
                                    <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $branchSettings['enabled'] ? 'translate-x-6' : 'translate-x-1' }}"></span>
                                </button>
                            </div>

                            @if($branchSettings['enabled'])
                                <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-lg">
                                    <div class="flex items-center gap-2 text-emerald-700 mb-2">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        <span class="font-medium">{{ __('Module is enabled for this branch') }}</span>
                                    </div>
                                    <p class="text-sm text-emerald-600">
                                        {{ __('Users in this branch can access this module based on their permissions.') }}
                                    </p>
                                </div>
                            @else
                                <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg">
                                    <div class="flex items-center gap-2 text-amber-700 mb-2">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                        </svg>
                                        <span class="font-medium">{{ __('Module is disabled for this branch') }}</span>
                                    </div>
                                    <p class="text-sm text-amber-600">
                                        {{ __('Enable this module to make it available for users in this branch.') }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="text-center text-slate-500 py-8">
                            <svg class="w-12 h-12 mx-auto mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            <p class="text-sm">{{ __('Select a branch to view and configure module settings') }}</p>
                        </div>
                    @endif
                </div>
            @else
                {{-- No Module Selected --}}
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-12">
                    <div class="text-center text-slate-500">
                        <svg class="w-20 h-20 mx-auto mb-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                        <h3 class="text-lg font-medium text-slate-700 dark:text-slate-300 mb-2">
                            {{ __('No Module Selected') }}
                        </h3>
                        <p class="text-sm">
                            {{ __('Select a module from the list to view details and configure settings') }}
                        </p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
