<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">{{ $editMode ? __('Edit Expense') : __('Add Expense') }}</h1>
        </div>
        <a href="{{ route('app.expenses.index') }}" class="erp-btn erp-btn-secondary">{{ __('Back') }}</a>
    </div>

    <form wire:submit="save" class="erp-card p-6 space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <div class="flex items-center justify-between">
                    <label class="erp-label">{{ __('Category') }}</label>
                    <x-quick-add-link
                        :route="route('app.expenses.categories.create')"
                        label="{{ __('Add Category') }}"
                        permission="expenses.manage" />
                </div>
                <select wire:model="category_id" class="erp-input">
                    <option value="">{{ __('Select Category') }}</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->localized_name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="erp-label">{{ __('Date') }} <span class="text-red-500">*</span></label>
                <input type="date" wire:model="expense_date" class="erp-input @error('expense_date') border-red-500 @enderror">
                @error('expense_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="erp-label">{{ __('Amount') }} <span class="text-red-500">*</span></label>
                <input type="number" wire:model="amount" step="0.01" class="erp-input @error('amount') border-red-500 @enderror">
                @error('amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="erp-label">{{ __('Payment Method') }}</label>
                <select wire:model="payment_method" class="erp-input">
                    <option value="cash">{{ __('Cash') }}</option>
                    <option value="bank_transfer">{{ __('Bank Transfer') }}</option>
                    <option value="card">{{ __('Card') }}</option>
                    <option value="cheque">{{ __('Cheque') }}</option>
                </select>
            </div>

            <div>
                <label class="erp-label">{{ __('Reference Number') }}</label>
                <input type="text" wire:model="reference_number" class="erp-input">
            </div>

            <div>
                <label class="erp-label">{{ __('Attachment') }}</label>
                <livewire:components.media-picker 
                    :file-path="$attachment"
                    accept-mode="mixed"
                    storage-scope="direct"
                    storage-path="expenses"
                    storage-disk="local"
                    :max-size="5120"
                    field-id="expense-attachment"
                    wire:key="expense-attachment-{{ $attachment ?: 'empty' }}"
                ></livewire:components.media-picker>
                @error('attachment') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2">
                <label class="erp-label">{{ __('Description') }}</label>
                <textarea wire:model="description" rows="3" class="erp-input"></textarea>
            </div>

            <div class="flex items-center gap-4">
                <label class="flex items-center gap-2">
                    <input type="checkbox" wire:model="is_recurring" class="rounded border-slate-300 text-emerald-600">
                    <span class="text-sm">{{ __('Recurring Expense') }}</span>
                </label>
            </div>

            @if($is_recurring)
            <div>
                <label class="erp-label">{{ __('Recurrence Interval') }}</label>
                <select wire:model="recurrence_interval" class="erp-input">
                    <option value="daily">{{ __('Daily') }}</option>
                    <option value="weekly">{{ __('Weekly') }}</option>
                    <option value="monthly">{{ __('Monthly') }}</option>
                    <option value="yearly">{{ __('Yearly') }}</option>
                </select>
            </div>
            @endif
        </div>

        <div class="flex justify-end gap-3 pt-4 border-t">
            <a href="{{ route('app.expenses.index') }}" class="erp-btn erp-btn-secondary">{{ __('Cancel') }}</a>
            <button type="submit" class="erp-btn erp-btn-primary">{{ $editMode ? __('Update') : __('Save') }}</button>
        </div>
    </form>
</div>
