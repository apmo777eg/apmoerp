<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-100">
                {{ $isEdit ? __('Edit Supplier Quotation') : __('New Supplier Quotation') }}
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Create supplier quotations against approved requisitions') }}</p>
        </div>

        <a href="{{ route('app.purchases.quotations.index') }}" class="erp-btn erp-btn-secondary">{{ __('Back') }}</a>
    </div>

    <div class="erp-card p-6">
        <form wire:submit.prevent="save" class="space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div>
                    <label class="erp-label">{{ __('Requisition') }} *</label>
                    <select wire:model="requisition_id" wire:change="loadRequisitionItems" class="erp-input" required>
                        <option value="">{{ __('Select') }}</option>
                        @foreach($requisitions as $r)
                            <option value="{{ $r['id'] }}">{{ $r['code'] }} @if(!empty($r['subject'])) — {{ $r['subject'] }} @endif</option>
                        @endforeach
                    </select>
                    @error('requisition_id') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="erp-label">{{ __('Supplier') }} *</label>
                    <select wire:model.defer="supplier_id" class="erp-input" required>
                        <option value="">{{ __('Select') }}</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s['id'] }}">{{ $s['name'] }} @if(!empty($s['code'])) ({{ $s['code'] }}) @endif</option>
                        @endforeach
                    </select>
                    @error('supplier_id') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="erp-label">{{ __('Quotation Date') }} *</label>
                    <input type="date" wire:model.live="quotation_date" class="erp-input" required>
                    @error('quotation_date') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="erp-label">{{ __('Validity (days)') }} *</label>
                        <input type="number" min="1" max="365" wire:model.live="validity_days" class="erp-input" required>
                        @error('validity_days') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="erp-label">{{ __('Valid Until') }}</label>
                        <input type="date" wire:model.defer="valid_until" class="erp-input" readonly>
                    </div>
                </div>

                <div>
                    <label class="erp-label">{{ __('Delivery Terms') }}</label>
                    <input type="text" wire:model.defer="delivery_terms" class="erp-input">
                    @error('delivery_terms') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="erp-label">{{ __('Delivery Days') }}</label>
                        <input type="number" min="0" max="365" wire:model.defer="delivery_days" class="erp-input">
                        @error('delivery_days') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="erp-label">{{ __('Lead Time (days)') }}</label>
                        <input type="number" min="0" max="365" wire:model.defer="lead_time_days" class="erp-input">
                        @error('lead_time_days') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="lg:col-span-2">
                    <label class="erp-label">{{ __('Payment Terms') }}</label>
                    <textarea rows="2" wire:model.defer="payment_terms" class="erp-input"></textarea>
                    @error('payment_terms') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="lg:col-span-2">
                    <label class="erp-label">{{ __('Terms & Conditions') }}</label>
                    <textarea rows="3" wire:model.defer="terms_conditions" class="erp-input"></textarea>
                    @error('terms_conditions') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="lg:col-span-2">
                    <label class="erp-label">{{ __('Notes') }}</label>
                    <textarea rows="3" wire:model.defer="notes" class="erp-input"></textarea>
                    @error('notes') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="border-t border-slate-200 dark:border-slate-700 pt-6">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-lg font-semibold text-slate-800 dark:text-slate-100">{{ __('Items') }}</h2>
                    <button type="button" wire:click="addItem" class="erp-btn erp-btn-secondary">+ {{ __('Add Item') }}</button>
                </div>

                @error('items') <div class="text-red-600 text-sm mb-2">{{ $message }}</div> @enderror

                <div class="overflow-x-auto">
                    <table class="erp-table">
                        <thead>
                            <tr>
                                <th class="min-w-[240px]">{{ __('Product') }}</th>
                                <th class="w-28">{{ __('Qty') }}</th>
                                <th class="w-40">{{ __('Unit Price') }}</th>
                                <th class="w-28">{{ __('Tax %') }}</th>
                                <th class="min-w-[240px]">{{ __('Notes') }}</th>
                                <th class="w-16"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $index => $item)
                                <tr>
                                    <td>
                                        <select wire:model="items.{{ $index }}.product_id" wire:change="updateProductDetails({{ $index }})" class="erp-input">
                                            <option value="">{{ __('Select') }}</option>
                                            @foreach($products as $p)
                                                <option value="{{ $p['id'] }}">{{ $p['name'] }} @if(!empty($p['sku'])) ({{ $p['sku'] }}) @endif</option>
                                            @endforeach
                                        </select>
                                        @error('items.'.$index.'.product_id') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
                                    </td>
                                    <td>
                                        <input type="number" step="0.0001" min="0" wire:model.defer="items.{{ $index }}.quantity" class="erp-input">
                                        @error('items.'.$index.'.quantity') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
                                    </td>
                                    <td>
                                        <input type="number" step="0.0001" min="0" wire:model.defer="items.{{ $index }}.unit_price" class="erp-input">
                                        @error('items.'.$index.'.unit_price') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" max="100" wire:model.defer="items.{{ $index }}.tax_percentage" class="erp-input">
                                        @error('items.'.$index.'.tax_percentage') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
                                    </td>
                                    <td>
                                        <input type="text" wire:model.defer="items.{{ $index }}.notes" class="erp-input" placeholder="{{ __('Optional') }}">
                                        @error('items.'.$index.'.notes') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
                                    </td>
                                    <td class="text-end">
                                        <button type="button" wire:click="removeItem({{ $index }})" class="text-red-600 hover:text-red-800" title="{{ __('Remove') }}">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('app.purchases.quotations.index') }}" class="erp-btn erp-btn-secondary">{{ __('Cancel') }}</a>
                <button type="submit" class="erp-btn erp-btn-primary">{{ __('Save') }}</button>
            </div>
        </form>
    </div>
</div>
