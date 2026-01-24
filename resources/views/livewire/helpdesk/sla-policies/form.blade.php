<div class="space-y-4">
    <div class="flex items-center justify-between gap-2">
        <div>
            <h1 class="text-lg font-semibold text-slate-800 dark:text-slate-100">
                {{ $policyId ? __('Edit SLA Policy') : __('New SLA Policy') }}
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                {{ __('Configure Service Level Agreement policy settings.') }}
            </p>
        </div>
    </div>

    @if(session()->has('success'))
        <div class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-lg flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit.prevent="save" class="space-y-6 max-w-2xl">
        <div class="erp-card p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">{{ __('Name') }} <span class="text-red-500">*</span></label>
                <input type="text" wire:model="name" class="erp-input w-full" required>
                @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">{{ __('Description') }}</label>
                <textarea wire:model="description" rows="3" class="erp-input w-full"></textarea>
                @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">{{ __('Response Time (minutes)') }} <span class="text-red-500">*</span></label>
                    <input type="number" wire:model="response_time_minutes" class="erp-input w-full" min="1" required>
                    @error('response_time_minutes') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">{{ __('Resolution Time (minutes)') }} <span class="text-red-500">*</span></label>
                    <input type="number" wire:model="resolution_time_minutes" class="erp-input w-full" min="1" required>
                    @error('resolution_time_minutes') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="flex items-center">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" wire:model.live="business_hours_only" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <span class="text-sm text-slate-700 dark:text-slate-300">{{ __('Business Hours Only') }}</span>
                </label>
            </div>

            @if($business_hours_only)
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 bg-slate-50 dark:bg-slate-800 rounded-lg">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">{{ __('Business Hours Start') }}</label>
                        <input type="time" wire:model="business_hours_start" class="erp-input w-full">
                        @error('business_hours_start') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">{{ __('Business Hours End') }}</label>
                        <input type="time" wire:model="business_hours_end" class="erp-input w-full">
                        @error('business_hours_end') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">{{ __('Working Days') }}</label>
                        <div class="flex flex-wrap gap-2">
                            @foreach($daysOfWeek as $dayNum => $dayName)
                                <label class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white dark:bg-slate-700 rounded-lg cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-600 transition {{ in_array($dayNum, $working_days) ? 'ring-2 ring-emerald-500' : '' }}">
                                    <input type="checkbox" wire:model="working_days" value="{{ $dayNum }}" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                    <span class="text-sm text-slate-700 dark:text-slate-300">{{ __($dayName) }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('working_days') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>
            @endif

            <div class="flex items-center">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" wire:model="is_active" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <span class="text-sm text-slate-700 dark:text-slate-300">{{ __('Active') }}</span>
                </label>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('app.helpdesk.sla-policies.index') }}"
               class="inline-flex items-center rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-2 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm hover:bg-slate-50 dark:hover:bg-slate-800">
                {{ __('Cancel') }}
            </a>
            <button type="submit" class="erp-btn erp-btn-primary">
                {{ $policyId ? __('Update') : __('Create') }}
            </button>
        </div>
    </form>
</div>
