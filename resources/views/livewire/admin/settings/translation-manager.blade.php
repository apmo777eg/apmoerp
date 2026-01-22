{{-- resources/views/livewire/admin/settings/translation-manager.blade.php --}}
<div class="space-y-4">
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-bold text-slate-800 dark:text-white flex items-center gap-2">
                <span class="text-2xl">🌍</span>
                {{ __('Translation Manager') }}
            </h1>
            <p class="text-sm text-slate-500">{{ __('Manage system translations for Arabic and English') }}</p>
        </div>
        <button wire:click="openAddModal" class="erp-btn-primary">
            <svg class="w-5 h-5 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            {{ __('Add Translation') }}
        </button>
    </div>

    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-700 flex items-center gap-2">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
        <div class="relative w-full sm:w-80">
            <input type="text" 
                   wire:model.live.debounce.300ms="search" 
                   placeholder="{{ __('Search translations...') }}"
                   class="erp-input ltr:pl-10 rtl:pr-10">
            <svg class="absolute ltr:left-3 rtl:right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>
        <div class="text-sm text-slate-500">
            {{ __('Total') }}: <span class="font-semibold text-emerald-600">{{ $totalCount }}</span> {{ __('translations') }}
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700">
        <table class="erp-table">
            <thead>
                <tr>
                    <th class="w-1/4">{{ __('Key') }}</th>
                    <th class="w-1/3">{{ __('Arabic') }} 🇪🇬</th>
                    <th class="w-1/3">{{ __('English') }} 🇬🇧</th>
                    <th class="w-24 text-center">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($filteredTranslations as $key => $values)
                    <tr wire:key="trans-{{ md5($key) }}">
                        <td class="font-mono text-xs text-slate-600 dark:text-slate-400 break-all">
                            {{ $key }}
                        </td>
                        <td dir="rtl" class="text-right">
                            {{ $values['ar'] ?: '-' }}
                        </td>
                        <td dir="ltr" class="text-left">
                            {{ $values['en'] ?: '-' }}
                        </td>
                        <td class="text-center">
                            <div class="flex items-center justify-center gap-1">
                                <button wire:click="openEditModal('{{ addslashes($key) }}')" 
                                        class="erp-btn-icon" 
                                        title="{{ __('Edit') }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                <button wire:click="deleteTranslation('{{ addslashes($key) }}')" 
                                        wire:confirm="{{ __('Are you sure you want to delete this translation?') }}"
                                        class="erp-btn-icon text-red-500 hover:text-red-700 hover:bg-red-50"
                                        title="{{ __('Delete') }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center py-8 text-slate-500">
                            <svg class="w-12 h-12 mx-auto mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                            </svg>
                            {{ __('No translations found') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($totalCount > $perPage)
        <div class="mt-4 flex items-center justify-between border-t border-slate-200 dark:border-slate-700 pt-4">
            <div class="text-sm text-slate-600 dark:text-slate-400">
                {{ __('Showing') }} {{ ($currentPage - 1) * $perPage + 1 }} - {{ min($currentPage * $perPage, $totalCount) }} {{ __('of') }} {{ $totalCount }}
            </div>
            <div class="flex gap-2">
                @if($hasPrevious)
                    <button wire:click="previousPage" class="erp-btn-secondary">
                        {{ __('Previous') }}
                    </button>
                @endif
                @if($hasMore)
                    <button wire:click="nextPage" class="erp-btn-secondary">
                        {{ __('Next') }}
                    </button>
                @endif
            </div>
        </div>
    @endif

    {{-- Add Modal --}}
    @if($showAddModal)
        <div class="z-modal fixed inset-0 flex items-center justify-center bg-black/50 backdrop-blur-sm" wire:click.self="closeAddModal">
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl w-full max-w-lg mx-4 overflow-hidden">
                <div class="px-6 py-4 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white">
                    <h3 class="text-lg font-semibold flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        {{ __('Add New Translation') }}
                    </h3>
                </div>
                <form wire:submit.prevent="addTranslation" class="p-6 space-y-4">
                    <div>
                        <label class="erp-label">{{ __('What do you want to translate?') }}</label>
                        <input type="text" wire:model="newKey" class="erp-input mt-1" placeholder="{{ __('e.g., Welcome to our system') }}">
                        <p class="text-xs text-slate-500 mt-1">{{ __('Type the phrase you want to translate') }}</p>
                        @error('newKey') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="erp-label">{{ __('Arabic Translation') }} 🇪🇬</label>
                        <input type="text" wire:model="newValueAr" class="erp-input mt-1" dir="rtl" placeholder="{{ __('e.g., مرحباً بك في نظامنا') }}">
                        @error('newValueAr') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="erp-label">{{ __('English Translation') }} 🇬🇧</label>
                        <input type="text" wire:model="newValueEn" class="erp-input mt-1" dir="ltr" placeholder="{{ __('e.g., Welcome to our system') }}">
                        @error('newValueEn') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
                        <button type="button" wire:click="closeAddModal" class="erp-btn-secondary">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit" class="erp-btn-primary">
                            {{ __('Add Translation') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Edit Modal --}}
    @if($showEditModal)
        <div class="z-modal fixed inset-0 flex items-center justify-center bg-black/50 backdrop-blur-sm" wire:click.self="closeEditModal">
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl w-full max-w-lg mx-4 overflow-hidden">
                <div class="px-6 py-4 bg-gradient-to-r from-blue-500 to-blue-600 text-white">
                    <h3 class="text-lg font-semibold flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        {{ __('Edit Translation') }}
                    </h3>
                </div>
                <form wire:submit.prevent="updateTranslation" class="p-6 space-y-4">
                    <div>
                        <label class="erp-label">{{ __('Translation Key') }}</label>
                        <div class="mt-1 p-3 bg-slate-100 dark:bg-slate-700 rounded-lg font-mono text-sm text-slate-600 dark:text-slate-300">
                            {{ $editKey }}
                        </div>
                    </div>
                    <div>
                        <label class="erp-label">{{ __('Arabic Translation') }} 🇪🇬</label>
                        <input type="text" wire:model="editValueAr" class="erp-input mt-1" dir="rtl">
                        @error('editValueAr') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="erp-label">{{ __('English Translation') }} 🇬🇧</label>
                        <input type="text" wire:model="editValueEn" class="erp-input mt-1" dir="ltr">
                        @error('editValueEn') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
                        <button type="button" wire:click="closeEditModal" class="erp-btn-secondary">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit" class="erp-btn-primary">
                            {{ __('Save Changes') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
