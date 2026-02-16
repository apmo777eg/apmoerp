<div class="space-y-4" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold text-slate-800">{{ __('Project Expenses') }}</h3>
        <button type="button" wire:click="openModal" class="erp-btn erp-btn-sm erp-btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            {{ __('Add Expense') }}
        </button>
    </div>

    {{-- Expenses Table --}}
    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-start text-xs font-medium text-slate-500 uppercase">{{ __('Date') }}</th>
                    <th class="px-4 py-3 text-start text-xs font-medium text-slate-500 uppercase">{{ __('Description') }}</th>
                    <th class="px-4 py-3 text-start text-xs font-medium text-slate-500 uppercase">{{ __('Category') }}</th>
                    <th class="px-4 py-3 text-end text-xs font-medium text-slate-500 uppercase">{{ __('Amount') }}</th>
                    <th class="px-4 py-3 text-end text-xs font-medium text-slate-500 uppercase">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-200">
                @forelse($expenses as $expense)
                <tr>
                    <td class="px-4 py-3 text-sm">{{ optional($expense->expense_date)->format('Y-m-d') }}</td>
                    <td class="px-4 py-3 text-sm">{{ $expense->description }}</td>
                    <td class="px-4 py-3 text-sm">
                        <span class="px-2 py-1 text-xs rounded-full bg-slate-100 text-slate-800">
                            {{ $expense->category }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm text-end font-medium">{{ number_format($expense->amount, 2) }}</td>
                    <td class="px-4 py-3 text-sm text-end">
                        <button type="button" wire:click="edit({{ $expense->id }})" class="text-blue-600 hover:text-blue-800 me-2">
                            {{ __('Edit') }}
                        </button>
                        <button type="button" wire:click="delete({{ $expense->id }})" wire:confirm="{{ __('Are you sure?') }}" 
                                class="text-red-600 hover:text-red-800">
                            {{ __('Delete') }}
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-slate-500">
                        {{ __('No expenses yet') }}
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Total Expenses --}}
    <div class="bg-slate-50 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <p class="text-sm text-slate-600">{{ __('Total Expenses') }}:</p>
            <p class="text-xl font-bold text-slate-900">{{ number_format($totalExpenses ?? 0, 2) }}</p>
        </div>
    </div>

    {{-- Add/Edit Modal --}}
    @if($showModal)
    <div class="z-modal fixed inset-0 bg-slate-900/50 flex items-center justify-center" wire:click.self="closeModal">
        <div class="bg-white rounded-xl p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">
                {{ $editingExpenseId ? __('Edit Expense') : __('Add Expense') }}
            </h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Date') }}</label>
                    <input type="date" wire:model="form.date" class="erp-input">
                    @error('form.date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Description') }}</label>
                    <input type="text" wire:model="form.description" class="erp-input">
                    @error('form.description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Category') }}</label>
                    <select wire:model="form.category" class="erp-input">
                        <option value="">{{ __('Select category') }}</option>
                        <option value="materials">{{ __('Materials') }}</option>
                        <option value="labor">{{ __('Labor') }}</option>
                        <option value="equipment">{{ __('Equipment') }}</option>
                        <option value="other">{{ __('Other') }}</option>
                    </select>
                    @error('form.category') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Amount') }}</label>
                    <input type="number" step="0.01" wire:model="form.amount" class="erp-input">
                    @error('form.amount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
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
