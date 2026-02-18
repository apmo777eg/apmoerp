<div class="space-y-6" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-slate-800">
            {{ $projectId ? __('Edit Project') : __('Create Project') }}
        </h1>
    </div>

    <form wire:submit.prevent="save" class="space-y-6">
        {{-- Basic Information --}}
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-slate-800 mb-4">{{ __('Basic Information') }}</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        {{ __('Project Name') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" wire:model.live="name" class="erp-input" required>
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        {{ __('Code') }}
                        <span class="text-xs text-slate-400 font-normal">{{ __('(auto-generated)') }}</span>
                    </label>
                    <div class="flex items-center gap-2">
                        <input type="text" wire:model="code" 
                               class="erp-input flex-1 {{ !$overrideCode && !$projectId ? 'bg-slate-50' : '' }}"
                               {{ !$overrideCode && !$projectId ? 'readonly' : '' }}>
                        @if(!$projectId)
                        <button type="button" wire:click="$toggle('overrideCode')" 
                                class="px-2 py-2 text-xs text-slate-500 hover:text-slate-700 border border-slate-300 rounded-lg"
                                title="{{ __('Edit code manually') }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                            </svg>
                        </button>
                        @endif
                    </div>
                    @error('code') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Description') }}</label>
                    <textarea wire:model="description" rows="3" class="erp-input"></textarea>
                    @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Start Date') }}</label>
                    <input type="date" wire:model="start_date" class="erp-input">
                    @error('start_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('End Date') }}</label>
                    <input type="date" wire:model="end_date" class="erp-input">
                    @error('end_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Budget') }}</label>
                    <input type="number" step="0.01" wire:model="budget_amount" class="erp-input">
                    @error('budget_amount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Status') }}</label>
                    <select wire:model="status" class="erp-input">
                        <option value="planning">{{ __('Planning') }}</option>
                        <option value="active">{{ __('Active') }}</option>
                        <option value="on_hold">{{ __('On Hold') }}</option>
                        <option value="completed">{{ __('Completed') }}</option>
                        <option value="cancelled">{{ __('Cancelled') }}</option>
                    </select>
                    @error('status') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex justify-end gap-3">
            <a href="{{ route('app.projects.index') }}" class="erp-btn erp-btn-secondary">
                {{ __('Cancel') }}
            </a>
            <button type="submit" class="erp-btn erp-btn-primary" wire:loading.attr="disabled">
                <span wire:loading.remove>{{ $projectId ? __('Update') : __('Create') }}</span>
                <span wire:loading>{{ __('Saving...') }}</span>
            </button>
        </div>
    </form>
</div>
