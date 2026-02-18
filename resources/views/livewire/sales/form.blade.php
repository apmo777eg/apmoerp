<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">{{ $editMode ? __('Edit Sale') : __('New Sale') }}</h1>
            <p class="text-sm text-slate-500">{{ __('Create sales order for customer') }}</p>
        </div>
        <a href="{{ route('app.sales.index') }}" class="erp-btn erp-btn-secondary">{{ __('Back') }}</a>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="erp-card p-6">
            <h3 class="text-lg font-semibold text-slate-700 mb-4">{{ __('Sale Details') }}</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <div class="flex items-center justify-between">
                        <label class="erp-label">{{ __('Customer') }}</label>
                        <x-quick-add-link 
                            :route="route('customers.create')" 
                            label="{{ __('Add Customer') }}"
                            permission="customers.manage" />
                    </div>
                    <select wire:model="customer_id" class="erp-input @error('customer_id') border-red-500 @enderror">
                        <option value="">{{ __('Walk-in Customer') }}</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                        @endforeach
                    </select>
                    @error('customer_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <div class="flex items-center justify-between">
                        <label class="erp-label">{{ __('Warehouse') }}</label>
                        <x-quick-add-link 
                            :route="route('app.warehouse.index')" 
                            label="{{ __('Add Warehouse') }}"
                            permission="warehouse.manage" />
                    </div>
                    <select wire:model="warehouse_id" class="erp-input">
                        <option value="">{{ __('Select Warehouse') }}</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="erp-label">{{ __('Reference Number') }}</label>
                    <input type="text" wire:model="reference_no" class="erp-input" placeholder="{{ __('e.g., SO-12345') }}">
                </div>

                <div>
                    <label class="erp-label">{{ __('Status') }}</label>
                    @php
                        $statusOptions = [
                            'draft' => __('Draft'),
                            'pending' => __('Pending'),
                            'processing' => __('Processing'),
                            'confirmed' => __('Confirmed'),
                            'posted' => __('Posted'),
                            'completed' => __('Completed'),
                            'cancelled' => __('Cancelled'),
                            'refunded' => __('Refunded'),
                        ];
                    @endphp
                    <select wire:model="status" class="erp-input">
                        {{-- If the current status isn't in our standard list (legacy/integration), show it to avoid accidental changes. --}}
                        @if(!empty($status) && !array_key_exists($status, $statusOptions))
                            <option value="{{ $status }}">{{ __(ucfirst(str_replace('_', ' ', $status))) }}</option>
                        @endif

                        @foreach($statusOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="erp-label">{{ __('Currency') }}</label>
                    <select wire:model="currency" class="erp-input">
                        @foreach($currencies as $curr)
                            <option value="{{ $curr->reference_number }}">{{ $curr->reference_number }} - {{ $curr->name }} ({{ $curr->symbol }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-3">
                    <label class="erp-label">{{ __('Notes') }}</label>
                    <textarea wire:model="notes" rows="2" class="erp-input"></textarea>
                </div>
            </div>
        </div>

        <div class="erp-card p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-slate-700">{{ __('Products') }}</h3>
                <x-quick-add-link 
                    :route="route('app.inventory.products.create')" 
                    label="{{ __('Add Product') }}"
                    permission="inventory.products.view" />
            </div>
            
            <div class="mb-4">
                <div class="relative search-dropdown-wrapper">
                    <input type="text" wire:model.live.debounce.300ms="productSearch" placeholder="{{ __('Search products by name or SKU...') }}" class="erp-input">
                    @if(count($searchResults) > 0)
                        <div class="search-dropdown w-full mt-1 bg-white border border-slate-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                            @foreach($searchResults as $product)
                                <button type="button" wire:click="addProduct({{ $product['id'] }})" class="w-full px-4 py-2 text-left hover:bg-emerald-50 flex justify-between items-center">
                                    <span class="font-medium text-slate-800">{{ $product['name'] }}</span>
                                    <span class="text-sm text-slate-500">{{ $product['sku'] }} - {{ number_format($product['default_price'] ?? 0, 2) }}</span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            @error('items') <p class="text-red-500 text-sm mb-4">{{ $message }}</p> @enderror

            @if(count($items) > 0)
                <div class="overflow-x-auto">
                    <table class="erp-table">
                        <thead>
                            <tr>
                                <th class="w-12">#</th>
                                <th>{{ __('Product') }}</th>
                                <th class="w-24">{{ __('SKU') }}</th>
                                <th class="w-24">{{ __('Qty') }}</th>
                                <th class="w-32">{{ __('Unit Price') }}</th>
                                <th class="w-24">{{ __('Discount') }}</th>
                                <th class="w-24">{{ __('Tax %') }}</th>
                                <th class="w-32">{{ __('Total') }}</th>
                                <th class="w-16"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $index => $item)
                                <tr>
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td class="font-medium">{{ $item['product_name'] }}</td>
                                    <td class="text-slate-500">{{ $item['sku'] }}</td>
                                    <td>
                                        <input type="number" wire:model="items.{{ $index }}.qty" step="0.01" min="0.0001" class="erp-input w-full text-center">
                                    </td>
                                    <td>
                                        <input type="number" wire:model="items.{{ $index }}.unit_price" step="0.01" min="0" class="erp-input w-full text-right">
                                    </td>
                                    <td>
                                        <input type="number" wire:model="items.{{ $index }}.discount" step="0.01" min="0" class="erp-input w-full text-right">
                                    </td>
                                    <td>
                                        <input type="number" wire:model="items.{{ $index }}.tax_rate" step="0.01" min="0" max="100" class="erp-input w-full text-right">
                                    </td>
                                    <td class="text-right font-medium">
                                        @php
                                            $lineTotal = ($item['qty'] * $item['unit_price']) - ($item['discount'] ?? 0);
                                            $lineTotal += $lineTotal * (($item['tax_rate'] ?? 0) / 100);
                                        @endphp
                                        {{ number_format($lineTotal, 2) }}
                                    </td>
                                    <td class="text-center">
                                        <button type="button" wire:click="removeItem({{ $index }})" class="text-red-500 hover:text-red-700">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-8 text-slate-400">
                    <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    <p>{{ __('No products added yet. Search and add products above.') }}</p>
                </div>
            @endif
        </div>

        <div class="erp-card p-6">
            <h3 class="text-lg font-semibold text-slate-700 mb-4">{{ __('Payment') }}</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="erp-label">{{ __('Payment Method') }} <span class="text-red-500">*</span></label>
                    <select wire:model="payment_method" class="erp-input @error('payment_method') border-red-500 @enderror">
                        <option value="cash">{{ __('Cash') }}</option>
                        <option value="card">{{ __('Card') }}</option>
                        <option value="bank_transfer">{{ __('Bank Transfer') }}</option>
                        <option value="cheque">{{ __('Cheque') }}</option>
                    </select>
                    @error('payment_method') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="erp-label">{{ __('Payment Amount') }} <span class="text-red-500">*</span></label>
                    <input type="number" wire:model.live="payment_amount" step="0.01" min="0" class="erp-input @error('payment_amount') border-red-500 @enderror">
                    @error('payment_amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div></div>
            </div>
        </div>

        <div class="erp-card p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="erp-label">{{ __('Discount') }}</label>
                            <input type="number" wire:model.live="discount_total" step="0.01" min="0" class="erp-input">
                        </div>
                        <div>
                            <label class="erp-label">{{ __('Shipping') }}</label>
                            <input type="number" wire:model.live="shipping_total" step="0.01" min="0" class="erp-input">
                        </div>
                    </div>
                </div>
                <div class="bg-slate-50 rounded-lg p-4">
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-slate-600">{{ __('Subtotal') }}</span>
                            <span class="font-medium">{{ number_format($subTotal, 2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-600">{{ __('Tax') }}</span>
                            <span class="font-medium">{{ number_format($taxTotal, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-red-600">
                            <span>{{ __('Discount') }}</span>
                            <span class="font-medium">-{{ number_format($discount_total, 2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-600">{{ __('Shipping') }}</span>
                            <span class="font-medium">{{ number_format($shipping_total, 2) }}</span>
                        </div>
                        <div class="border-t pt-2 flex justify-between text-lg">
                            <span class="font-bold text-slate-800">{{ __('Grand Total') }}</span>
                            <span class="font-bold text-emerald-600">{{ number_format($grandTotal, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-blue-600">
                            <span>{{ __('Payment') }}</span>
                            <span class="font-medium">{{ number_format($payment_amount, 2) }}</span>
                        </div>
                        <div class="flex justify-between {{ ($grandTotal - $payment_amount) > 0 ? 'text-orange-600' : 'text-green-600' }}">
                            <span class="font-semibold">{{ __('Balance') }}</span>
                            <span class="font-semibold">{{ number_format($grandTotal - $payment_amount, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('app.sales.index') }}" class="erp-btn erp-btn-secondary">{{ __('Cancel') }}</a>
            <button type="submit" class="erp-btn erp-btn-primary" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">{{ $editMode ? __('Update Sale') : __('Create Sale') }}</span>
                <span wire:loading wire:target="save">{{ __('Processing...') }}</span>
            </button>
        </div>
    </form>
</div>
