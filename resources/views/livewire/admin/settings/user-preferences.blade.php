<div class="p-6"
    x-data="{
        showKeyboardHelp() {
            if (window.erpKeyboardShortcuts) {
                window.erpKeyboardShortcuts.showHelp();
            }
        }
    }"
>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('User Preferences') }}</h1>
            <p class="text-gray-600 dark:text-gray-400">{{ __('Customize your experience') }}</p>
        </div>
        <button type="button" @click="showKeyboardHelp()" class="flex items-center gap-2 px-3 py-2 text-sm bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>{{ __('Keyboard Shortcuts') }}</span>
            <kbd class="px-1.5 py-0.5 bg-gray-200 dark:bg-gray-600 rounded text-xs">F1</kbd>
        </button>
    </div>

    @if(session()->has('success'))
        <div class="mb-4 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
            <p class="text-green-800 dark:text-green-200">{{ session('success') }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Appearance -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="p-2 bg-purple-100 dark:bg-purple-900 rounded-lg">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                    </svg>
                </div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Appearance') }}</h2>
            </div>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">{{ __('Theme') }}</label>
                    <div class="grid grid-cols-3 gap-3">
                        <label class="relative cursor-pointer">
                            <input type="radio" wire:model="theme" value="light" class="sr-only peer">
                            <div class="p-4 border-2 rounded-lg text-center peer-checked:border-emerald-500 peer-checked:bg-emerald-50 dark:peer-checked:bg-emerald-900/20 border-gray-200 dark:border-gray-600 hover:border-gray-300 transition">
                                <svg class="w-8 h-8 mx-auto mb-2 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('Light') }}</span>
                            </div>
                        </label>
                        <label class="relative cursor-pointer">
                            <input type="radio" wire:model="theme" value="dark" class="sr-only peer">
                            <div class="p-4 border-2 rounded-lg text-center peer-checked:border-emerald-500 peer-checked:bg-emerald-50 dark:peer-checked:bg-emerald-900/20 border-gray-200 dark:border-gray-600 hover:border-gray-300 transition">
                                <svg class="w-8 h-8 mx-auto mb-2 text-indigo-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/>
                                </svg>
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('Dark') }}</span>
                            </div>
                        </label>
                        <label class="relative cursor-pointer">
                            <input type="radio" wire:model="theme" value="system" class="sr-only peer">
                            <div class="p-4 border-2 rounded-lg text-center peer-checked:border-emerald-500 peer-checked:bg-emerald-50 dark:peer-checked:bg-emerald-900/20 border-gray-200 dark:border-gray-600 hover:border-gray-300 transition">
                                <svg class="w-8 h-8 mx-auto mb-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('System') }}</span>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Session Settings -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Session Settings') }}</h2>
            </div>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Session Timeout (minutes)') }}</label>
                    <input type="number" wire:model="session_timeout" min="5" max="480" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <p class="mt-1 text-xs text-gray-500">{{ __('Time before automatic logout (5-480 minutes)') }}</p>
                </div>
                <div>
                    <label class="flex items-center p-3 rounded-lg border border-gray-200 dark:border-gray-600 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <input type="checkbox" wire:model="auto_logout" class="rounded border-gray-300 text-emerald-600">
                        <span class="ml-3">
                            <span class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Auto-logout on inactivity') }}</span>
                            <span class="block text-xs text-gray-500">{{ __('Automatically log out when session times out') }}</span>
                        </span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Dashboard Widgets -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                    </svg>
                </div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Dashboard Widgets') }}</h2>
            </div>
            
            <div class="grid grid-cols-2 gap-3">
                @foreach($availableWidgets as $key => $label)
                <label class="flex items-center p-3 rounded-lg border border-gray-200 dark:border-gray-600 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition {{ ($dashboard_widgets[$key] ?? false) ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20' : '' }}">
                    <input type="checkbox" wire:click="toggleWidget('{{ $key }}')" 
                        @checked($dashboard_widgets[$key] ?? false) 
                        class="rounded border-gray-300 text-emerald-600">
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $label }}</span>
                </label>
                @endforeach
            </div>
        </div>

        <!-- POS Keyboard Shortcuts -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="p-2 bg-orange-100 dark:bg-orange-900 rounded-lg">
                    <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                    </svg>
                </div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('POS Keyboard Shortcuts') }}</h2>
            </div>
            
            <div class="space-y-3 max-h-64 overflow-y-auto pr-2">
                @foreach(['F1', 'F2', 'F3', 'F4', 'F5', 'F6', 'F7', 'F8', 'F9', 'F10', 'F11', 'F12'] as $key)
                <div class="flex items-center gap-3">
                    <kbd class="w-12 px-2 py-1.5 bg-gray-100 dark:bg-gray-700 rounded text-center text-sm font-mono border border-gray-200 dark:border-gray-600">{{ $key }}</kbd>
                    <select wire:model="pos_shortcuts.{{ $key }}" class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                        <option value="">{{ __('Not assigned') }}</option>
                        @foreach($availableActions as $actionKey => $actionLabel)
                        <option value="{{ $actionKey }}">{{ $actionLabel }}</option>
                        @endforeach
                    </select>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Notifications -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="p-2 bg-red-100 dark:bg-red-900 rounded-lg">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                </div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Notifications') }}</h2>
            </div>
            
            <div class="space-y-3">
                <label class="flex items-center p-3 rounded-lg border border-gray-200 dark:border-gray-600 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition {{ ($notification_settings['low_stock'] ?? false) ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20' : '' }}">
                    <input type="checkbox" wire:click="toggleNotification('low_stock')" 
                        @checked($notification_settings['low_stock'] ?? false) 
                        class="rounded border-gray-300 text-emerald-600">
                    <span class="ml-3">
                        <span class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Low Stock Alerts') }}</span>
                        <span class="block text-xs text-gray-500">{{ __('Get notified when stock is low') }}</span>
                    </span>
                </label>
                <label class="flex items-center p-3 rounded-lg border border-gray-200 dark:border-gray-600 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition {{ ($notification_settings['new_orders'] ?? false) ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20' : '' }}">
                    <input type="checkbox" wire:click="toggleNotification('new_orders')" 
                        @checked($notification_settings['new_orders'] ?? false) 
                        class="rounded border-gray-300 text-emerald-600">
                    <span class="ml-3">
                        <span class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('New Orders') }}</span>
                        <span class="block text-xs text-gray-500">{{ __('Get notified about new orders') }}</span>
                    </span>
                </label>
                <label class="flex items-center p-3 rounded-lg border border-gray-200 dark:border-gray-600 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition {{ ($notification_settings['payment_due'] ?? false) ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20' : '' }}">
                    <input type="checkbox" wire:click="toggleNotification('payment_due')" 
                        @checked($notification_settings['payment_due'] ?? false) 
                        class="rounded border-gray-300 text-emerald-600">
                    <span class="ml-3">
                        <span class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Payment Reminders') }}</span>
                        <span class="block text-xs text-gray-500">{{ __('Get reminded about due payments') }}</span>
                    </span>
                </label>
            </div>
        </div>

        <!-- Printing -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="p-2 bg-cyan-100 dark:bg-cyan-900 rounded-lg">
                    <svg class="w-5 h-5 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                </div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Printing') }}</h2>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Default Printer') }}</label>
                <input type="text" wire:model="default_printer" placeholder="{{ __('e.g., POS-Printer-1') }}" 
                    class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                <p class="mt-1 text-xs text-gray-500">{{ __('Enter the printer name for thermal printing') }}</p>
            </div>
        </div>
    </div>

    <!-- Global Keyboard Shortcuts Info -->
    <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <h3 class="font-medium text-blue-800 dark:text-blue-200">{{ __('Global Keyboard Shortcuts') }}</h3>
                <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">
                    {{ __('Press F1 anytime to see available shortcuts. Use Ctrl+S to save, Ctrl+F to search, and Ctrl+N to create new items.') }}
                </p>
            </div>
        </div>
    </div>

    <div class="mt-6 flex justify-between">
        <button type="button" wire:click="resetToDefaults" class="flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            {{ __('Reset to Defaults') }}
        </button>
        <button type="button" wire:click="save" class="flex items-center gap-2 px-6 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            {{ __('Save Preferences') }}
            <kbd class="px-1.5 py-0.5 bg-emerald-700 rounded text-xs">Ctrl+S</kbd>
        </button>
    </div>
</div>
