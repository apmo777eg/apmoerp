<div class="lw-component">
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-800 dark:text-white">{{ __('Translation Manager') }}</h2>
                <p class="text-gray-600 dark:text-gray-400">{{ __('Manage translations for Arabic and English languages') }}</p>
            </div>
            <a href="{{ route('admin.translations.create') }}" 
               class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition inline-flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                {{ __('Add Translation') }}
            </a>
        </div>
    </div>
    
    @if(session('success'))
        <div class="mb-4 p-4 bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-200 rounded-lg">
            {{ session('success') }}
        </div>
    @endif
    
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
                    </svg>
                </div>
                <div class="ml-4 rtl:mr-4 rtl:ml-0">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Total Keys') }}</p>
                    <p class="text-xl font-semibold text-gray-800 dark:text-white">{{ count($translations) }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 dark:bg-green-900">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4 rtl:mr-4 rtl:ml-0">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Groups') }}</p>
                    <p class="text-xl font-semibold text-gray-800 dark:text-white">{{ count($groups) }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 dark:bg-yellow-900">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <div class="ml-4 rtl:mr-4 rtl:ml-0">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Missing') }}</p>
                    <p class="text-xl font-semibold text-gray-800 dark:text-white">{{ $missingCount }}</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filters and Actions -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex flex-col md:flex-row gap-4 flex-1">
                    <!-- Search -->
                    <div class="relative flex-1 max-w-md">
                        <input type="text" wire:model.live.debounce.300ms="search" 
                               class="w-full pl-10 rtl:pl-4 rtl:pr-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                               placeholder="{{ __('Search translations...') }}">
                        <div class="absolute inset-y-0 left-0 rtl:left-auto rtl:right-0 pl-3 rtl:pl-0 rtl:pr-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                    </div>
                    
                    <!-- Group Filter -->
                    <select wire:model.live="selectedGroup" 
                            class="border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                        <option value="">{{ __('All Groups') }}</option>
                        @foreach($groups as $group)
                            <option value="{{ $group }}">{{ ucfirst($group) }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="flex gap-2">
                    <button wire:click="clearCache" 
                            wire:loading.attr="disabled"
                            class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition inline-flex items-center gap-2 disabled:opacity-50">
                        <svg wire:loading.class="animate-spin" wire:target="clearCache" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <span wire:loading.remove wire:target="clearCache">{{ __('Clear Cache') }}</span>
                        <span wire:loading wire:target="clearCache">{{ __('Clearing...') }}</span>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Translations Table -->
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left rtl:text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('Key') }}
                        </th>
                        <th class="px-4 py-3 text-left rtl:text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('Group') }}
                        </th>
                        <th class="px-4 py-3 text-left rtl:text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('English') }}
                        </th>
                        <th class="px-4 py-3 text-left rtl:text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('Arabic') }}
                        </th>
                        <th class="px-4 py-3 text-left rtl:text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('Actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($translations as $translation)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white font-mono">
                                {{ $translation['key'] }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                                    {{ $translation['group'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300 max-w-xs truncate">
                                {{ $translation['en'] ?: '-' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300 max-w-xs truncate" dir="rtl">
                                {{ $translation['ar'] ?: '-' }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.translations.edit', ['key' => urlencode($translation['key']), 'group' => $translation['group']]) }}" 
                                       class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                       title="{{ __('Edit') }}">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </a>
                                    <button wire:click="deleteTranslation('{{ e($translation['key']) }}', '{{ e($translation['group']) }}')"
                                            wire:confirm="{{ __('Are you sure you want to delete this translation?') }}"
                                            class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                            title="{{ __('Delete') }}">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                {{ __('No translations found.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
