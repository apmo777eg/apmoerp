<div class="space-y-6">
    <div>
        <h2 class="text-2xl font-bold text-slate-800">{{ __('Purchase Settings') }}</h2>
        <p class="text-slate-500">{{ __('Configure purchase order and procurement settings') }}</p>
    </div>

    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit.prevent="save" class="space-y-6">
        <div class="erp-card p-6 space-y-6">
            <h3 class="text-lg font-semibold text-slate-700 border-b pb-2">{{ __('Invoice Settings') }}</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Purchase Order Prefix') }}</label>
                    <input type="text" wire:model="purchase_order_prefix" class="erp-input" maxlength="10">
                    <p class="text-xs text-slate-500 mt-1">{{ __('Example: PO-, PINV-') }}</p>
                    @error('purchase_order_prefix') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Starting Number') }}</label>
                    <input type="number" wire:model="purchase_invoice_starting_number" class="erp-input" min="1">
                    @error('purchase_invoice_starting_number') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Payment Terms (Days)') }}</label>
                    <input type="number" wire:model="purchase_payment_terms_days" class="erp-input" min="0" max="365">
                    @error('purchase_payment_terms_days') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        <div class="erp-card p-6 space-y-6">
            <h3 class="text-lg font-semibold text-slate-700 border-b pb-2">{{ __('Approval & Workflow') }}</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="flex items-center justify-between p-4 bg-slate-50 rounded-lg">
                    <div>
                        <label class="font-medium text-slate-700">{{ __('Require Purchase Approval') }}</label>
                        <p class="text-sm text-slate-500">{{ __('Require approval for purchase orders above threshold') }}</p>
                    </div>
                    <input type="checkbox" wire:model="require_purchase_approval" class="w-5 h-5 text-emerald-600 rounded focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Approval Threshold') }}</label>
                    <input type="number" wire:model="purchase_approval_threshold" class="erp-input" min="0" step="0.01">
                    <p class="text-xs text-slate-500 mt-1">{{ __('Orders above this amount require approval') }}</p>
                    @error('purchase_approval_threshold') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div class="flex items-center justify-between p-4 bg-slate-50 rounded-lg">
                    <div>
                        <label class="font-medium text-slate-700">{{ __('Enable Purchase Requisitions') }}</label>
                        <p class="text-sm text-slate-500">{{ __('Allow purchase requisitions workflow') }}</p>
                    </div>
                    <input type="checkbox" wire:model="enable_purchase_requisitions" class="w-5 h-5 text-emerald-600 rounded focus:ring-emerald-500">
                </div>

                <div class="flex items-center justify-between p-4 bg-slate-50 rounded-lg">
                    <div>
                        <label class="font-medium text-slate-700">{{ __('Auto Receive on Purchase') }}</label>
                        <p class="text-sm text-slate-500">{{ __('Automatically receive goods when purchase is posted') }}</p>
                    </div>
                    <input type="checkbox" wire:model="auto_receive_on_purchase" class="w-5 h-5 text-emerald-600 rounded focus:ring-emerald-500">
                </div>
            </div>
        </div>

        <div class="erp-card p-6 space-y-6">
            <h3 class="text-lg font-semibold text-slate-700 border-b pb-2">{{ __('Goods Receipt Note (GRN)') }}</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="flex items-center justify-between p-4 bg-slate-50 rounded-lg">
                    <div>
                        <label class="font-medium text-slate-700">{{ __('Enable GRN') }}</label>
                        <p class="text-sm text-slate-500">{{ __('Use Goods Receipt Notes for receiving') }}</p>
                    </div>
                    <input type="checkbox" wire:model="enable_grn" class="w-5 h-5 text-emerald-600 rounded focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('GRN Validity (Days)') }}</label>
                    <input type="number" wire:model="grn_validity_days" class="erp-input" min="1" max="90">
                    @error('grn_validity_days') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div class="flex items-center justify-between p-4 bg-slate-50 rounded-lg">
                    <div>
                        <label class="font-medium text-slate-700">{{ __('Enable 3-Way Matching') }}</label>
                        <p class="text-sm text-slate-500">{{ __('Match PO, GRN, and Invoice before payment') }}</p>
                    </div>
                    <input type="checkbox" wire:model="enable_3way_matching" class="w-5 h-5 text-emerald-600 rounded focus:ring-emerald-500">
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
