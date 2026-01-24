<div class="space-y-6">
    <div>
        <h2 class="text-2xl font-bold text-slate-800">{{ __('Warehouse Settings') }}</h2>
        <p class="text-slate-500">{{ __('Configure warehouse and inventory tracking settings') }}</p>
    </div>

    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit.prevent="save" class="space-y-6">
        <div class="erp-card p-6 space-y-6">
            <h3 class="text-lg font-semibold text-slate-700 border-b pb-2">{{ __('Location & Tracking') }}</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="flex items-center justify-between p-4 bg-slate-50 rounded-lg">
                    <div>
                        <label class="font-medium text-slate-700">{{ __('Multi-Location Support') }}</label>
                        <p class="text-sm text-slate-500">{{ __('Enable multiple storage locations within warehouses') }}</p>
                    </div>
                    <input type="checkbox" wire:model="enable_multi_location" class="w-5 h-5 text-emerald-600 rounded focus:ring-emerald-500">
                </div>

                <div class="flex items-center justify-between p-4 bg-slate-50 rounded-lg">
                    <div>
                        <label class="font-medium text-slate-700">{{ __('Batch Tracking') }}</label>
                        <p class="text-sm text-slate-500">{{ __('Track products by batch/lot numbers') }}</p>
                    </div>
                    <input type="checkbox" wire:model="enable_batch_tracking" class="w-5 h-5 text-emerald-600 rounded focus:ring-emerald-500">
                </div>

                <div class="flex items-center justify-between p-4 bg-slate-50 rounded-lg">
                    <div>
                        <label class="font-medium text-slate-700">{{ __('Serial Number Tracking') }}</label>
                        <p class="text-sm text-slate-500">{{ __('Track individual items by serial number') }}</p>
                    </div>
                    <input type="checkbox" wire:model="enable_serial_tracking" class="w-5 h-5 text-emerald-600 rounded focus:ring-emerald-500">
                </div>

                <div class="flex items-center justify-between p-4 bg-slate-50 rounded-lg">
                    <div>
                        <label class="font-medium text-slate-700">{{ __('Barcode Scanning') }}</label>
                        <p class="text-sm text-slate-500">{{ __('Enable barcode scanning for faster operations') }}</p>
                    </div>
                    <input type="checkbox" wire:model="enable_barcode_scanning" class="w-5 h-5 text-emerald-600 rounded focus:ring-emerald-500">
                </div>
            </div>
        </div>

        <div class="erp-card p-6 space-y-6">
            <h3 class="text-lg font-semibold text-slate-700 border-b pb-2">{{ __('Stock Management') }}</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Stock Allocation Method') }}</label>
                    <select wire:model="stock_allocation_method" class="erp-input">
                        <option value="FIFO">{{ __('FIFO (First In, First Out)') }}</option>
                        <option value="LIFO">{{ __('LIFO (Last In, First Out)') }}</option>
                        <option value="FEFO">{{ __('FEFO (First Expired, First Out)') }}</option>
                    </select>
                    @error('stock_allocation_method') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Stock Count Frequency (Days)') }}</label>
                    <input type="number" wire:model="stock_count_frequency_days" class="erp-input" min="1" max="365">
                    @error('stock_count_frequency_days') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Default Warehouse Location') }}</label>
                    <input type="text" wire:model="default_warehouse_location" class="erp-input" maxlength="100">
                    @error('default_warehouse_location') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div class="flex items-center justify-between p-4 bg-slate-50 rounded-lg">
                    <div>
                        <label class="font-medium text-slate-700">{{ __('Auto Allocate Stock') }}</label>
                        <p class="text-sm text-slate-500">{{ __('Automatically allocate stock for orders') }}</p>
                    </div>
                    <input type="checkbox" wire:model="auto_allocate_stock" class="w-5 h-5 text-emerald-600 rounded focus:ring-emerald-500">
                </div>

                <div class="flex items-center justify-between p-4 bg-slate-50 rounded-lg">
                    <div>
                        <label class="font-medium text-slate-700">{{ __('Allow Negative Stock') }}</label>
                        <p class="text-sm text-slate-500">{{ __('Allow stock levels to go below zero') }}</p>
                    </div>
                    <input type="checkbox" wire:model="enable_negative_stock" class="w-5 h-5 text-emerald-600 rounded focus:ring-emerald-500">
                </div>

                <div class="flex items-center justify-between p-4 bg-slate-50 rounded-lg">
                    <div>
                        <label class="font-medium text-slate-700">{{ __('Require Approval for Adjustments') }}</label>
                        <p class="text-sm text-slate-500">{{ __('Require manager approval for stock adjustments') }}</p>
                    </div>
                    <input type="checkbox" wire:model="require_approval_for_adjustments" class="w-5 h-5 text-emerald-600 rounded focus:ring-emerald-500">
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="erp-btn-primary">
                {{ __('Save Settings') }}
            </button>
        </div>
    </form>
</div>
