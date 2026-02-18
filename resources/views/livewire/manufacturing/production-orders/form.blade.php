<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">
                {{ $editMode ? __('Edit Production Order') : __('Create Production Order') }}
            </h1>
            <p class="text-sm text-slate-500">{{ __('Manufacturing job configuration') }}</p>
        </div>
        <a href="{{ route('app.manufacturing.orders.index') }}" class="erp-btn erp-btn-secondary">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            {{ __('Back to List') }}
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200">
        <form wire:submit="save" class="p-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- BOM Selection --}}
                <div class="md:col-span-2">
                    <label for="bom_id" class="block text-sm font-medium text-slate-700 mb-2">
                        {{ __('Bill of Materials') }} <span class="text-red-500">*</span>
                    </label>
                    <select wire:model="bom_id" id="bom_id" class="erp-input @error('bom_id') border-red-500 @enderror">
                        <option value="">{{ __('Select a BOM') }}</option>
                        @foreach($boms as $bom)
                            <option value="{{ $bom->id }}">{{ $bom->name }} ({{ $bom->product?->name ?? '-' }})</option>
                        @endforeach
                    </select>
                    @error('bom_id') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                </div>

                {{-- Product Selection --}}
                <div>
                    <label for="product_id" class="block text-sm font-medium text-slate-700 mb-2">
                        {{ __('Product to Manufacture') }} <span class="text-red-500">*</span>
                    </label>
                    <select wire:model="product_id" id="product_id" class="erp-input @error('product_id') border-red-500 @enderror">
                        <option value="">{{ __('Select a product') }}</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
                        @endforeach
                    </select>
                    @error('product_id') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                </div>

                {{-- Warehouse Selection --}}
                <div>
                    <label for="warehouse_id" class="block text-sm font-medium text-slate-700 mb-2">
                        {{ __('Target Warehouse') }} <span class="text-red-500">*</span>
                    </label>
                    <select wire:model="warehouse_id" id="warehouse_id" class="erp-input @error('warehouse_id') border-red-500 @enderror">
                        <option value="">{{ __('Select a warehouse') }}</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                    @error('warehouse_id') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                </div>

                {{-- Quantity Planned --}}
                <div>
                    <label for="planned_quantity" class="block text-sm font-medium text-slate-700 mb-2">
                        {{ __('Quantity to Produce') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="number" step="0.01" min="0.01" wire:model="planned_quantity" id="planned_quantity" class="erp-input @error('planned_quantity') border-red-500 @enderror">
                    @error('planned_quantity') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                </div>

                {{-- Priority --}}
                <div>
                    <label for="priority" class="block text-sm font-medium text-slate-700 mb-2">
                        {{ __('Priority') }} <span class="text-red-500">*</span>
                    </label>
                    <select wire:model="priority" id="priority" class="erp-input @error('priority') border-red-500 @enderror">
                        <option value="low">{{ __('Low') }}</option>
                        <option value="normal">{{ __('Normal') }}</option>
                        <option value="high">{{ __('High') }}</option>
                        <option value="urgent">{{ __('Urgent') }}</option>
                    </select>
                    @error('priority') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                </div>

                {{-- Status --}}
                <div>
                    <label for="status" class="block text-sm font-medium text-slate-700 mb-2">
                        {{ __('Status') }} <span class="text-red-500">*</span>
                    </label>
                    <select wire:model="status" id="status" class="erp-input @error('status') border-red-500 @enderror">
                        <option value="draft">{{ __('Draft') }}</option>
                        <option value="pending">{{ __('Pending') }}</option>
                        <option value="in_progress">{{ __('In Progress') }}</option>
                        <option value="completed">{{ __('Completed') }}</option>
                        <option value="cancelled">{{ __('Cancelled') }}</option>
                    </select>
                    @error('status') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                </div>

                {{-- Planned Start Date --}}
                <div>
                    <label for="planned_start_date" class="block text-sm font-medium text-slate-700 mb-2">
                        {{ __('Planned Start Date') }}
                    </label>
                    <input type="date" wire:model="planned_start_date" id="planned_start_date" class="erp-input @error('planned_start_date') border-red-500 @enderror">
                    @error('planned_start_date') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                </div>

                {{-- Planned End Date --}}
                <div>
                    <label for="planned_end_date" class="block text-sm font-medium text-slate-700 mb-2">
                        {{ __('Planned End Date') }}
                    </label>
                    <input type="date" wire:model="planned_end_date" id="planned_end_date" class="erp-input @error('planned_end_date') border-red-500 @enderror">
                    @error('planned_end_date') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                </div>

                {{-- Notes --}}
                <div class="md:col-span-2">
                    <label for="notes" class="block text-sm font-medium text-slate-700 mb-2">
                        {{ __('Notes') }}
                    </label>
                    <textarea wire:model="notes" id="notes" rows="3" class="erp-input @error('notes') border-red-500 @enderror" placeholder="{{ __('Additional production notes or requirements') }}"></textarea>
                    @error('notes') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>

            {{-- Form Actions --}}
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                <a href="{{ route('app.manufacturing.orders.index') }}" class="erp-btn erp-btn-secondary">
                    {{ __('Cancel') }}
                </a>
                <button type="submit" class="erp-btn erp-btn-primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>
                        {{ $editMode ? __('Update Production Order') : __('Create Production Order') }}
                    </span>
                    <span wire:loading>
                        {{ __('Saving...') }}
                    </span>
                </button>
            </div>
        </form>
    </div>

    @if($editMode && $productionOrder)
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
        <p class="text-sm text-blue-800">
            <strong>{{ __('Note') }}:</strong>
            {{ __('After saving the production order, you can manage materials consumption and track progress from the order details page.') }}
        </p>
    </div>
    @endif
</div>
