<div class="space-y-4" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold text-slate-800">{{ __('Time Logs') }}</h3>
        <button type="button" wire:click="createLog" class="erp-btn erp-btn-sm erp-btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            {{ __('Log Time') }}
        </button>
    </div>

    {{-- Time Logs Table --}}
    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-start text-xs font-medium text-slate-500 uppercase">{{ __('Date') }}</th>
                    <th class="px-4 py-3 text-start text-xs font-medium text-slate-500 uppercase">{{ __('User') }}</th>
                    <th class="px-4 py-3 text-start text-xs font-medium text-slate-500 uppercase">{{ __('Hours') }}</th>
                    <th class="px-4 py-3 text-start text-xs font-medium text-slate-500 uppercase">{{ __('Description') }}</th>
                    <th class="px-4 py-3 text-end text-xs font-medium text-slate-500 uppercase">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-200">
                @forelse($timeLogs as $log)
                <tr>
                    <td class="px-4 py-3 text-sm">{{ $log->log_date?->format('Y-m-d') }}</td>
                    <td class="px-4 py-3 text-sm">{{ $log->employee?->name ?? $log->user?->name }}</td>
                    <td class="px-4 py-3 text-sm font-medium">{{ number_format($log->hours, 2) }}h</td>
                    <td class="px-4 py-3 text-sm text-slate-600">{{ $log->description }}</td>
                    <td class="px-4 py-3 text-sm text-end">
                        <button type="button" wire:click="deleteLog({{ $log->id }})" wire:confirm="{{ __('Are you sure?') }}" 
                                class="text-red-600 hover:text-red-800">
                            {{ __('Delete') }}
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-slate-500">
                        {{ __('No time logs yet') }}
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Total Hours --}}
    <div class="bg-slate-50 rounded-lg p-4">
        <p class="text-sm text-slate-600">{{ __('Total Hours Logged') }}: 
            <span class="font-bold text-slate-900">{{ number_format($totalHours, 2) }}h</span>
        </p>
    </div>

    {{-- Add Modal --}}
    @if($showModal)
    <div class="z-modal fixed inset-0 bg-slate-900/50 flex items-center justify-center" wire:click.self="closeModal">
        <div class="bg-white rounded-xl p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">{{ __('Log Time') }}</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Date') }}</label>
                    <input type="date" wire:model="date" class="erp-input">
                    @error('date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Hours') }}</label>
                    <input type="number" step="0.25" wire:model="hours" class="erp-input">
                    @error('hours') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Description') }}</label>
                    <textarea wire:model="description" rows="3" class="erp-input"></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="button" wire:click="closeModal" class="erp-btn erp-btn-secondary flex-1">{{ __('Cancel') }}</button>
                    <button type="button" wire:click="save" class="erp-btn erp-btn-primary flex-1">{{ __('Save') }}</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
