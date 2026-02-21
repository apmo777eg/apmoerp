<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">{{ $adjustmentId ? __('Edit Adjustment') : __('New Adjustment') }}</h1>
            <p class="text-sm text-slate-500">{{ __('Create or edit stock adjustment') }}</p>
        </div>
    </div>

    <div class="erp-card p-6">
        <form wire:submit="save">
            <div class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Warehouse') }} *</label>
                        <select wire:model="warehouseId" class="erp-input" required>
                            <option value="">{{ __('Select warehouse') }}</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->name }} ({{ $warehouse->code }})</option>
                            @endforeach
                        </select>
                        @error('warehouseId') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Reason') }} *</label>
                        <input type="text" wire:model="reason" class="erp-input" placeholder="{{ __('e.g., Damaged goods, Expired items') }}" required>
                        @error('reason') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Notes') }}</label>
                    <textarea wire:model="note" rows="2" class="erp-input" placeholder="{{ __('Additional notes') }}"></textarea>
                    @error('note') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <div class="flex items-center justify-between mb-3">
                        <label class="block text-sm font-medium text-slate-700">{{ __('Items') }} *</label>
                        <button type="button" wire:click="addItem" class="text-sm text-blue-600 hover:text-blue-800 flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            {{ __('Add Item') }}
                        </button>
                    </div>

                    @error('items') <span class="text-red-500 text-sm block mb-2">{{ $message }}</span> @enderror

                    <div class="space-y-3">
                        @foreach($items as $index => $item)
                            <div class="flex gap-2 items-start bg-slate-50 p-3 rounded-lg">
                                <div class="flex-1">
                                    <select wire:model="items.{{ $index }}.product_id" class="erp-input" required>
                                        <option value="">{{ __('Select product') }}</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
                                        @endforeach
                                    </select>
                                    @error("items.{$index}.product_id") <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                <div class="w-32">
                                    <input type="number" wire:model="items.{{ $index }}.qty" step="0.01" class="erp-input" placeholder="{{ __('Qty') }}" required>
                                    @error("items.{$index}.qty") <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    <p class="text-xs text-slate-500 mt-1">{{ __('+ for add, - for remove') }}</p>
                                </div>
                                @if(count($items) > 1)
                                    <button type="button" wire:click="removeItem({{ $index }})" class="text-red-600 hover:text-red-800 mt-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <a href="{{ route('app.warehouse.adjustments.index') }}" class="erp-btn erp-btn-secondary">
                    {{ __('Cancel') }}
                </a>
                <button type="submit" class="erp-btn erp-btn-primary">
                    {{ __('Save Adjustment') }}
                </button>
            </div>
        </form>
    </div>
</div>
