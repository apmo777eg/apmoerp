<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">
                {{ $journalEntryId ? __('Edit Journal Entry') : __('New Journal Entry') }}
            </h1>
            <p class="text-sm text-slate-500">
                {{ __('Create double-entry journal entries') }}
            </p>
        </div>
    </div>

    <form wire:submit.prevent="save" class="space-y-6">
        <div class="erp-card p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        {{ __('Reference Number') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" wire:model="form.reference_number" @disabled($readOnly) class="erp-input" required>
                    @error('form.reference_number')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        {{ __('Entry Date') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="date" wire:model="form.entry_date" @disabled($readOnly) class="erp-input" required>
                    @error('form.entry_date')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        {{ __('Status') }} <span class="text-red-500">*</span>
                    </label>
                    <select wire:model="form.status" @disabled($readOnly) class="erp-input" required>
                        <option value="draft">{{ __('Draft') }}</option>
                        <option value="posted">{{ __('Posted') }}</option>
                    </select>
                    @error('form.status')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-3">
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        {{ __('Description') }}
                    </label>
                    <textarea wire:model="form.description" rows="2" @disabled($readOnly) class="erp-input"></textarea>
                    @error('form.description')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="border-t border-slate-200 pt-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-slate-800">{{ __('Journal Lines') }}</h3>
                    <div class="flex items-center gap-3">
                        <x-quick-add-link 
                            :route="route('app.accounting.accounts.create')" 
                            label="{{ __('Add Account') }}"
                            permission="accounting.create" />
                        <button type="button" wire:click="addLine" class="erp-btn erp-btn-secondary erp-btn-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            {{ __('Add Line') }}
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="erp-table">
                        <thead>
                            <tr>
                                <th class="w-1/3">{{ __('Account') }}</th>
                                <th class="w-1/3">{{ __('Description') }}</th>
                                <th class="w-32">{{ __('Debit') }}</th>
                                <th class="w-32">{{ __('Credit') }}</th>
                                <th class="w-16">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lines as $index => $line)
                                <tr wire:key="line-{{ $index }}">
                                    <td>
                                        <select wire:model="lines.{{ $index }}.account_id" @disabled($readOnly) class="erp-input" required>
                                            <option value="">{{ __('Select Account') }}</option>
                                            @foreach($accounts as $account)
                                                <option value="{{ $account->id }}">{{ $account->account_number }} - {{ $account->name }}</option>
                                            @endforeach
                                        </select>
                                        @error("lines.{$index}.account_id")
                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </td>
                                    <td>
                                        <input type="text" wire:model="lines.{{ $index }}.description" @disabled($readOnly) class="erp-input" placeholder="{{ __('Line description') }}">
                                        @error("lines.{$index}.description")
                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </td>
                                    <td>
                                        <input type="number" wire:model="lines.{{ $index }}.debit" step="0.01" min="0" @disabled($readOnly) class="erp-input">
                                        @error("lines.{$index}.debit")
                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </td>
                                    <td>
                                        <input type="number" wire:model="lines.{{ $index }}.credit" step="0.01" min="0" @disabled($readOnly) class="erp-input">
                                        @error("lines.{$index}.credit")
                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </td>
                                    <td class="text-center">
                                        @if(count($lines) > 2)
                                            <button type="button" wire:click="removeLine({{ $index }})" class="text-red-600 hover:text-red-800">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="font-semibold bg-slate-50">
                                <td colspan="2" class="text-right">{{ __('Total') }}</td>
                                <td class="text-blue-600">{{ number_format($totalDebit, 2) }}</td>
                                <td class="text-emerald-600">{{ number_format($totalCredit, 2) }}</td>
                                <td></td>
                            </tr>
                            @if(abs($totalDebit - $totalCredit) > 0.01)
                                <tr>
                                    <td colspan="5">
                                        <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-amber-800 text-sm">
                                            <div class="flex items-center gap-2">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                                <span>{{ __('Warning: Debits and Credits must be equal (Difference: :amount)', ['amount' => number_format(abs($totalDebit - $totalCredit), 2)]) }}</span>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('app.accounting.index') }}" class="erp-btn erp-btn-secondary">
                {{ __('Cancel') }}
            </a>
            @unless($readOnly)
            <button type="submit" class="erp-btn erp-btn-primary" wire:loading.attr="disabled">
                <span wire:loading.remove>{{ $journalEntryId ? __('Update Entry') : __('Create Entry') }}</span>
                <span wire:loading>{{ __('Saving...') }}</span>
            </button>
            @endunless
        </div>
    </form>
</div>
