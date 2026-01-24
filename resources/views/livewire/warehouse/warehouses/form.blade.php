<div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                {{ $warehouseId ? __('Edit Warehouse') : __('New Warehouse') }}
            </h1>
            <p class="text-gray-600 mt-1">{{ __('Manage warehouse details') }}</p>
        </div>
    </div>

    @if(session('success'))
        <div class="p-4 bg-emerald-100 border border-emerald-300 text-emerald-700 rounded-xl">
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit.prevent="save" class="space-y-6 max-w-2xl">
        <div class="bg-white rounded-2xl shadow-lg p-6 space-y-4">
            <h3 class="font-medium text-gray-800 border-b pb-2">{{ __('Warehouse Information') }}</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Name') }} *</label>
                    <input type="text" wire:model.live="name" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500">
                    @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('Code') }}
                        <span class="text-xs text-gray-400 font-normal">{{ __('(auto-generated)') }}</span>
                    </label>
                    <div class="flex items-center gap-2">
                        <input type="text" wire:model="code" 
                               class="flex-1 px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 {{ !$overrideCode && !$warehouseId ? 'bg-gray-50' : '' }}"
                               {{ !$overrideCode && !$warehouseId ? 'readonly' : '' }}>
                        @if(!$warehouseId)
                        <button type="button" wire:click="$toggle('overrideCode')" 
                                class="px-2 py-2 text-xs text-gray-500 hover:text-gray-700 border border-gray-300 rounded-lg"
                                title="{{ __('Edit code manually') }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                            </svg>
                        </button>
                        @endif
                    </div>
                    @error('code') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Type') }}</label>
                    <select wire:model="type" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500">
                        <option value="main">{{ __('Main') }}</option>
                        <option value="transit">{{ __('Transit') }}</option>
                        <option value="returns">{{ __('Returns') }}</option>
                        <option value="damaged">{{ __('Damaged') }}</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Status') }} *</label>
                    <select wire:model="status" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500">
                        <option value="active">{{ __('Active') }}</option>
                        <option value="inactive">{{ __('Inactive') }}</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Address') }}</label>
                <textarea wire:model="address" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500"></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Notes') }}</label>
                <textarea wire:model="notes" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500"></textarea>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('app.warehouse.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 transition">
                {{ __('Cancel') }}
            </a>
            <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition">
                {{ $warehouseId ? __('Update') : __('Create') }}
            </button>
        </div>
    </form>
</div>
