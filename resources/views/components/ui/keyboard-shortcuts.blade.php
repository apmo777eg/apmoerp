<div 
    x-data="{ 
        open: false,
        shortcuts: [
            { 
	                category: @js(__('Navigation')),
                items: [
	                    { keys: ['Ctrl', 'K'], description: @js(__('Search')) },
	                    { keys: ['Ctrl', 'B'], description: @js(__('Toggle Sidebar')) },
	                    { keys: ['Ctrl', 'H'], description: @js(__('Go to Dashboard')) },
	                    { keys: ['Ctrl', 'N'], description: @js(__('New Item')) },
                ]
            },
            { 
	                category: @js(__('Actions')),
                items: [
	                    { keys: ['Ctrl', 'S'], description: @js(__('Save')) },
	                    { keys: ['Ctrl', 'E'], description: @js(__('Edit')) },
	                    { keys: ['Ctrl', 'D'], description: @js(__('Delete')) },
	                    { keys: ['Esc'], description: @js(__('Close Modal')) },
                ]
            },
            { 
	                category: @js(__('POS')),
                items: [
	                    { keys: ['F1'], description: @js(__('Open POS')) },
	                    { keys: ['F2'], description: @js(__('Hold Sale')) },
	                    { keys: ['F3'], description: @js(__('Recall Hold')) },
	                    { keys: ['F4'], description: @js(__('Complete Sale')) },
                ]
            },
            { 
	                category: @js(__('List Operations')),
                items: [
	                    { keys: ['Ctrl', 'A'], description: @js(__('Select All')) },
	                    { keys: ['Ctrl', 'F'], description: @js(__('Filter')) },
	                    { keys: ['Ctrl', 'P'], description: @js(__('Print')) },
                ]
            },
        ]
    }"
    @keydown.escape.window="open = false"
    @show-shortcuts.window="open = true"
    @keydown.ctrl.slash.window.prevent="open = !open"
>
    <!-- Keyboard Shortcut Hint (Bottom Right) -->
    <button type="button" 
        @click="open = true"
        class="fixed bottom-4 right-4 z-40 px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-sm rounded-lg shadow-lg hover:bg-gray-800 dark:hover:bg-gray-600 transition-all"
        title="{{ __('Keyboard Shortcuts') }} (Ctrl + /)"
    >
        <span class="flex items-center space-x-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
            </svg>
            <span class="hidden sm:inline">{{ __('Shortcuts') }}</span>
        </span>
    </button>

    <!-- Modal -->
    <div 
        x-show="open"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
        style="background: rgba(0, 0, 0, 0.75);"
    >
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div 
                @click.away="open = false"
                class="bg-white dark:bg-gray-800 rounded-lg shadow-2xl max-w-4xl w-full overflow-hidden"
            >
                <!-- Header -->
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-900 dark:to-gray-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                                ⌨️ {{ __('Keyboard Shortcuts') }}
                            </h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                {{ __('Use these shortcuts to navigate and work faster') }}
                            </p>
                        </div>
                        <button type="button" 
                            @click="open = false"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Content -->
                <div class="px-6 py-6 max-h-[600px] overflow-y-auto">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <template x-for="(group, index) in shortcuts" :key="index">
                            <div>
                                <h3 
                                    class="text-lg font-semibold text-gray-900 dark:text-white mb-4 pb-2 border-b border-gray-200 dark:border-gray-700"
                                    x-text="group.category"
                                ></h3>
                                <div class="space-y-3">
                                    <template x-for="(shortcut, idx) in group.items" :key="idx">
                                        <div class="flex items-center justify-between">
                                            <span 
                                                class="text-sm text-gray-700 dark:text-gray-300"
                                                x-text="shortcut.description"
                                            ></span>
                                            <div class="flex items-center space-x-1 rtl:space-x-reverse">
                                                <template x-for="(key, keyIdx) in shortcut.keys" :key="keyIdx">
                                                    <span>
                                                        <kbd 
                                                            class="px-2 py-1 text-xs font-semibold text-gray-800 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded shadow-sm"
                                                            x-text="key"
                                                        ></kbd>
                                                        <span 
                                                            x-show="keyIdx < shortcut.keys.length - 1"
                                                            class="text-gray-400 mx-1"
                                                        >+</span>
                                                    </span>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between text-sm">
                        <div class="text-gray-600 dark:text-gray-400">
                            <span>{{ __('Press') }}</span>
                            <kbd class="mx-1 px-2 py-1 text-xs font-semibold text-gray-800 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded">Ctrl</kbd>
                            <span>+</span>
                            <kbd class="mx-1 px-2 py-1 text-xs font-semibold text-gray-800 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded">/</kbd>
                            <span>{{ __('to toggle this dialog') }}</span>
                        </div>
                        <button type="button" 
                            @click="open = false"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            {{ __('Got it!') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Global keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl + / to toggle shortcuts modal
    if ((e.ctrlKey || e.metaKey) && e.key === '/') {
        e.preventDefault();
        window.dispatchEvent(new CustomEvent('show-shortcuts'));
    }
    
    // Ctrl + H for dashboard
    if ((e.ctrlKey || e.metaKey) && e.key === 'h') {
        e.preventDefault();
        window.location.href = '{{ route('dashboard') }}';
    }
    
    // F1 for POS
    if (e.key === 'F1') {
        e.preventDefault();
        @if(auth()->check() && auth()->user()->can('pos.use'))
        window.location.href = '{{ route('pos.terminal') }}';
        @endif
    }
    
    // Ctrl + S to prevent default and trigger save event
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        window.dispatchEvent(new Event('trigger-save'));
    }
});
</script>

<style>
[x-cloak] { display: none !important; }
</style>
