<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">{{ $grnId ? __('Edit Goods Received Note') : __('New Goods Received Note') }}</h1>
            <p class="text-sm text-slate-500">{{ __('Create or edit GRN') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('app.purchases.grn.index') }}" class="erp-btn erp-btn-secondary" wire:navigate>
                {{ __('Back') }}
            </a>
        </div>
    </div>

    <div class="erp-card p-6">
        <form wire:submit.prevent="save" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Purchase Order') }} *</label>
                    <select wire:model.live="purchaseId" wire:change="loadPOItems" class="erp-input" required>
                        <option value="">{{ __('Select purchase order') }}</option>
                        @foreach($purchases as $purchase)
                            <option value="{{ $purchase->id }}">
                                {{ $purchase->reference_number }}
                                @if($purchase->supplier)
                                    — {{ $purchase->supplier->name }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @error('purchaseId') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Inspector') }}</label>
                    <select wire:model.live="inspectorId" class="erp-input">
                        <option value="">{{ __('Select inspector') }}</option>
                        @foreach($inspectors as $inspector)
                            <option value="{{ $inspector->id }}">{{ $inspector->name }}</option>
                        @endforeach
                    </select>
                    @error('inspectorId') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Received Date') }} *</label>
                    <input type="date" wire:model.live="receivedDate" class="erp-input" required>
                    @error('receivedDate') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('GRN Reference') }}</label>
                    <input type="text" class="erp-input" value="{{ $grn?->reference_number ?? '' }}" readonly>
                    <p class="text-xs text-slate-500 mt-1">{{ __('Generated automatically on save') }}</p>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Notes') }}</label>
                <textarea wire:model.live="notes" rows="3" class="erp-input"></textarea>
                @error('notes') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div class="border-t pt-6">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-lg font-semibold text-slate-800">{{ __('Items') }}</h2>
                    <span class="text-xs text-slate-500">{{ __('Loaded from purchase order') }}</span>
                </div>

                @error('items') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror

                <div class="overflow-x-auto">
                    <table class="erp-table">
                        <thead>
                            <tr>
                                <th class="text-left">{{ __('Product') }}</th>
                                <th class="text-right">{{ __('Ordered') }}</th>
                                <th class="text-right">{{ __('Received') }}</th>
                                <th class="text-right">{{ __('Damaged') }}</th>
                                <th class="text-right">{{ __('Defective') }}</th>
                                <th class="text-left">{{ __('Quality') }}</th>
                                <th class="text-left">{{ __('Notes') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($items as $i => $item)
                                <tr>
                                    <td>
                                        <div class="font-medium text-slate-800">{{ $item['product_name'] ?? __('Product #') . ($item['product_id'] ?? '') }}</div>
                                    </td>
                                    <td class="text-right">
                                        <span class="tabular-nums">{{ $item['expected_quantity'] ?? 0 }}</span>
                                    </td>
                                    <td class="text-right">
                                        <input type="number" step="0.0001" min="0"
                                               wire:model.live="items.{{ $i }}.received_quantity"
                                               class="erp-input w-28 text-right" />
                                        @error('items.'.$i.'.received_quantity') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </td>
                                    <td class="text-right">
                                        <input type="number" step="0.0001" min="0"
                                               wire:model.live="items.{{ $i }}.quantity_damaged"
                                               class="erp-input w-28 text-right" />
                                        @error('items.'.$i.'.quantity_damaged') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </td>
                                    <td class="text-right">
                                        <input type="number" step="0.0001" min="0"
                                               wire:model.live="items.{{ $i }}.quantity_defective"
                                               class="erp-input w-28 text-right" />
                                        @error('items.'.$i.'.quantity_defective') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </td>
                                    <td>
                                        <select wire:model.live="items.{{ $i }}.item_condition" class="erp-input w-40">
                                            <option value="good">{{ __('Good') }}</option>
                                            <option value="damaged">{{ __('Damaged') }}</option>
                                            <option value="defective">{{ __('Defective') }}</option>
                                        </select>
                                        @error('items.'.$i.'.item_condition') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </td>
                                    <td>
                                        <input type="text" wire:model.live="items.{{ $i }}.inspection_notes" class="erp-input" placeholder="{{ __('Notes...') }}" />
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-10 text-slate-500">
                                        {{ __('Select a purchase order to load items.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row justify-end gap-3">
                <a href="{{ route('app.purchases.grn.index') }}" class="erp-btn erp-btn-secondary" wire:navigate>
                    {{ __('Cancel') }}
                </a>

                <button type="submit" class="erp-btn erp-btn-primary">
                    {{ __('Save Draft') }}
                </button>

                <button type="button" wire:click="submit" class="erp-btn erp-btn-success">
                    {{ __('Submit for Inspection') }}
                </button>
            </div>
        </form>
    </div>
</div>
