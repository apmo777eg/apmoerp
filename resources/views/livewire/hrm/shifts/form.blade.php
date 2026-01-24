<div class="space-y-6 max-w-3xl mx-auto">
    <div class="flex items-center justify-between gap-2">
        <div>
            <h1 class="text-lg font-semibold text-slate-800 dark:text-slate-100">
                {{ $shiftId ? __('Edit Shift') : __('Add Shift') }}
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                {{ __('Configure work shift settings for employees.') }}
            </p>
        </div>

        <a href="{{ route('app.hrm.shifts.index') }}" class="erp-btn-secondary text-sm">
            {{ __('Back to Shifts') }}
        </a>
    </div>

    @if(session()->has('success'))
        <div class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-lg flex items-center gap-2">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit.prevent="save" class="space-y-6 max-w-2xl">
        <div class="erp-card p-6 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Name') }} *</label>
                    <input type="text" wire:model.live="name" class="erp-input w-full" required>
                    @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('Code') }} 
                        <span class="text-xs text-slate-400 font-normal">{{ __('(auto-generated)') }}</span>
                    </label>
                    <div class="flex items-center gap-2">
                        <input type="text" wire:model="code" 
                               class="erp-input flex-1 {{ !$overrideCode && !$shiftId ? 'bg-slate-50' : '' }}" 
                               {{ !$overrideCode && !$shiftId ? 'readonly' : '' }}>
                        @if(!$shiftId)
                        <button type="button" wire:click="$toggle('overrideCode')" 
                                class="px-2 py-2 text-xs text-slate-500 hover:text-slate-700 border border-slate-300 rounded-lg"
                                title="{{ __('Edit code manually') }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                            </svg>
                        </button>
                        @endif
                    </div>
                    @error('code') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Start Time') }} *</label>
                    <input type="time" wire:model="startTime" class="erp-input w-full" required>
                    @error('startTime') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('End Time') }} *</label>
                    <input type="time" wire:model="endTime" class="erp-input w-full" required>
                    @error('endTime') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Grace Period (minutes)') }}</label>
                <input type="number" wire:model="gracePeriodMinutes" class="erp-input w-full sm:w-1/2" min="0" max="120">
                @error('gracePeriodMinutes') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">{{ __('Working Days') }}</label>
                <div class="flex flex-wrap gap-2">
                    @foreach($daysOfWeek as $day => $name)
                        <label for="day-{{ $day }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 dark:bg-slate-700 rounded-lg cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-600 transition {{ in_array($day, $workingDays) ? 'ring-2 ring-emerald-500' : '' }}">
                            <input type="checkbox" id="day-{{ $day }}" wire:model="workingDays" value="{{ $day }}" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            <span class="text-sm text-slate-700 dark:text-slate-300">{{ __($name) }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Description') }}</label>
                <textarea wire:model="description" rows="2" class="erp-input w-full"></textarea>
                @error('description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="flex items-center">
                <label for="shift-active" class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" id="shift-active" wire:model="isActive" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <span class="text-sm text-slate-700 dark:text-slate-300">{{ __('Active') }}</span>
                </label>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('app.hrm.shifts.index') }}"
               class="inline-flex items-center rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-2 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm hover:bg-slate-50 dark:hover:bg-slate-800">
                {{ __('Cancel') }}
            </a>
            <button type="submit" class="erp-btn erp-btn-primary">
                {{ $shiftId ? __('Update') : __('Create') }}
            </button>
        </div>
    </form>
</div>
