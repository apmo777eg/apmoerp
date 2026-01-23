<div>
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Backup & Restore') }}</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                {{ __('Create database backups and restore from previous backups. Always create a backup before making major changes.') }}
            </p>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Quick Actions') }}</h2>
            
            <div class="flex flex-wrap gap-4">
                <button
                    wire:click="createBackup"
                    wire:loading.attr="disabled"
                    wire:target="createBackup"
                    class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span wire:loading.remove wire:target="createBackup" class="flex items-center">
                        <x-icon name="arrow-down-tray" class="w-5 h-5 mr-2" />
                        {{ __('Create Backup Now') }}
                    </span>
                    <span wire:loading wire:target="createBackup" class="flex items-center">
                        <svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ __('Creating backup...') }}
                    </span>
                </button>
            </div>

            @if($lastBackupResult === 'success')
                <div class="mt-4 p-4 bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200 rounded-lg">
                    <div class="flex items-center">
                        <x-icon name="check-circle" class="w-5 h-5 mr-2" />
                        {{ __('Backup created successfully!') }}
                    </div>
                </div>
            @endif
        </div>

        <!-- Warning Banner -->
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 mb-6">
            <div class="flex">
                <x-icon name="exclamation-triangle" class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mr-3 flex-shrink-0" />
                <div>
                    <h3 class="font-medium text-yellow-800 dark:text-yellow-200">{{ __('Important') }}</h3>
                    <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                        {{ __('Restoring a backup will replace ALL current data. A pre-restore backup will be created automatically, but please ensure you have a recent backup before proceeding.') }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Backups List -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Available Backups') }}</h2>
            </div>

            @if(count($backups) > 0)
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($backups as $backup)
                        <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                                            <x-icon name="server-stack" class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                                        </div>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">
                                            {{ basename($backup['path']) }}
                                        </p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $this->formatSize($backup['size']) }} • {{ $this->formatDate($backup['modified']) }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <button
                                        wire:click="download('{{ $backup['path'] }}')"
                                        class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors"
                                        title="{{ __('Download') }}"
                                    >
                                        <x-icon name="download" class="w-4 h-4 mr-1" />
                                        {{ __('Download') }}
                                    </button>
                                    <button
                                        wire:click="initiateRestore('{{ $backup['path'] }}')"
                                        class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-blue-700 dark:text-blue-200 bg-blue-100 dark:bg-blue-900/30 hover:bg-blue-200 dark:hover:bg-blue-900/50 rounded-lg transition-colors"
                                        title="{{ __('Restore') }}"
                                    >
                                        <x-icon name="arrow-path" class="w-4 h-4 mr-1" />
                                        {{ __('Restore') }}
                                    </button>
                                    <button
                                        wire:click="deleteBackup('{{ $backup['path'] }}')"
                                        wire:confirm="{{ __('Are you sure you want to delete this backup?') }}"
                                        class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-red-700 dark:text-red-200 bg-red-100 dark:bg-red-900/30 hover:bg-red-200 dark:hover:bg-red-900/50 rounded-lg transition-colors"
                                        title="{{ __('Delete') }}"
                                    >
                                        <x-icon name="trash" class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-12 text-center">
                    <x-icon name="server-stack" class="w-12 h-12 text-gray-400 mx-auto mb-4" />
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">{{ __('No backups found') }}</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-6">
                        {{ __('Create your first backup to protect your data.') }}
                    </p>
                    <button
                        wire:click="createBackup"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors"
                    >
                        <x-icon name="plus" class="w-5 h-5 mr-2" />
                        {{ __('Create First Backup') }}
                    </button>
                </div>
            @endif
        </div>

        <!-- Restore Confirmation Modal -->
        @if($showRestoreConfirm)
            <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75 transition-opacity" wire:click="cancelRestore"></div>

                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

                    <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                        <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/30 sm:mx-0 sm:h-10 sm:w-10">
                                    <x-icon name="exclamation-triangle" class="h-6 w-6 text-red-600 dark:text-red-400" />
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white" id="modal-title">
                                        {{ __('Confirm Database Restore') }}
                                    </h3>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ __('Are you sure you want to restore the database from this backup? This action will:') }}
                                        </p>
                                        <ul class="mt-2 text-sm text-gray-500 dark:text-gray-400 list-disc list-inside space-y-1">
                                            <li>{{ __('Create a pre-restore backup automatically') }}</li>
                                            <li>{{ __('Replace ALL current data with backup data') }}</li>
                                            <li>{{ __('Log out all active users') }}</li>
                                        </ul>
                                        <p class="mt-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                                            {{ __('Backup file:') }} {{ basename($selectedBackup ?? '') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-3">
                            <button
                                wire:click="confirmRestore"
                                wire:loading.attr="disabled"
                                type="button"
                                class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:w-auto sm:text-sm disabled:opacity-50"
                            >
                                <span wire:loading.remove wire:target="confirmRestore">{{ __('Yes, Restore Database') }}</span>
                                <span wire:loading wire:target="confirmRestore" class="flex items-center">
                                    <svg class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ __('Restoring...') }}
                                </span>
                            </button>
                            <button
                                wire:click="cancelRestore"
                                type="button"
                                class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:w-auto sm:text-sm"
                            >
                                {{ __('Cancel') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
