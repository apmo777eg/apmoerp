<div class="lw-component">
    <div class="mb-6">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.translations.index') }}" 
               class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                <svg class="w-6 h-6 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-gray-800 dark:text-white">
                    {{ $isEdit ? __('Edit Translation') : __('Add New Translation') }}
                </h2>
                <p class="text-gray-600 dark:text-gray-400">
                    {{ $isEdit ? __('Update translation values for this key') : __('Add a new translation key with values') }}
                </p>
            </div>
        </div>
    </div>
    
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <form wire:submit="save" class="p-6 space-y-6">
            <!-- Group -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {{ __('Group') }} <span class="text-red-500">*</span>
                </label>
                <div class="flex gap-2">
                    <select wire:model="group" 
                            @if($isEdit) disabled @endif
                            class="flex-1 border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white @if($isEdit) bg-gray-100 dark:bg-gray-600 cursor-not-allowed @endif">
                        @foreach($groups as $g)
                            <option value="{{ $g }}">{{ ucfirst($g) }}{{ $g === 'app' ? ' (' . __('Default') . ')' : '' }}</option>
                        @endforeach
                    </select>
                    @if(!$isEdit)
                        <input type="text" wire:model="group" 
                               class="flex-1 border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                               placeholder="{{ __('Or enter new group name') }}">
                    @endif
                </div>
                @error('group') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {{ __('Groups organize translations into separate files (e.g., auth, validation, messages)') }}
                </p>
            </div>
            
            <!-- Key -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {{ __('Translation Key') }} <span class="text-red-500">*</span>
                </label>
                <input type="text" wire:model="translationKey" 
                       @if($isEdit) disabled @endif
                       class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white font-mono @if($isEdit) bg-gray-100 dark:bg-gray-600 cursor-not-allowed @endif"
                       placeholder="{{ __('e.g., messages.welcome or button.save') }}">
                @error('translationKey') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {{ __('Use dots for nested keys. Only letters, numbers, underscores, and dots are allowed.') }}
                </p>
            </div>
            
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">{{ __('Translation Values') }}</h3>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- English Value -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <span class="inline-flex items-center gap-2">
                                <span class="w-6 h-4 bg-gradient-to-r from-blue-900 via-white to-red-700 rounded"></span>
                                {{ __('English Value') }} <span class="text-red-500">*</span>
                            </span>
                        </label>
                        <textarea wire:model="valueEn" rows="4"
                                  class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                                  placeholder="{{ __('Enter English translation') }}"></textarea>
                        @error('valueEn') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    
                    <!-- Arabic Value -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <span class="inline-flex items-center gap-2">
                                <span class="w-6 h-4 bg-gradient-to-r from-red-600 via-white to-black rounded"></span>
                                {{ __('Arabic Value') }} <span class="text-red-500">*</span>
                            </span>
                        </label>
                        <textarea wire:model="valueAr" rows="4" dir="rtl"
                                  class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white text-right"
                                  placeholder="{{ __('Enter Arabic translation') }}"></textarea>
                        @error('valueAr') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
            
            <!-- Preview -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">{{ __('Preview') }}</h3>
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 font-mono text-sm">
                    <p class="text-gray-600 dark:text-gray-300">
                        <span class="text-blue-600 dark:text-blue-400">__('{{ $group }}.{{ $translationKey }}')</span>
                    </p>
                    <div class="mt-3 grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <div class="bg-white dark:bg-gray-800 rounded p-3">
                            <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">EN:</span>
                            <span class="text-gray-800 dark:text-white">{{ $valueEn ?: '...' }}</span>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded p-3" dir="rtl">
                            <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">AR:</span>
                            <span class="text-gray-800 dark:text-white">{{ $valueAr ?: '...' }}</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button type="button" wire:click="cancel"
                        class="px-6 py-2.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    {{ __('Cancel') }}
                </button>
                <button type="submit" 
                        wire:loading.attr="disabled"
                        class="px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition inline-flex items-center gap-2 disabled:opacity-50">
                    <span wire:loading.remove wire:target="save">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </span>
                    <svg wire:loading wire:target="save" class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    {{ $isEdit ? __('Update Translation') : __('Add Translation') }}
                </button>
            </div>
        </form>
    </div>
</div>
