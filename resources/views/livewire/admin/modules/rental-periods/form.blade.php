<div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                {{ $periodId ? __('Edit Rental Period') : __('New Rental Period') }}
            </h1>
            <p class="text-gray-600 mt-1">
                {{ __('Configure rental period for module: :module', ['module' => $module->localized_name]) }}
            </p>
        </div>
    </div>

    @if(session('success'))
        <div class="p-4 bg-emerald-100 border border-emerald-300 text-emerald-700 rounded-xl">
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit.prevent="save" class="space-y-6 max-w-2xl">
        <div class="bg-white rounded-2xl shadow-lg p-6 space-y-4">
            <h3 class="font-medium text-gray-800 border-b pb-2">{{ __('Period Information') }}</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Period Key') }} *</label>
                    <input type="text" wire:model="period_key" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500" placeholder="e.g. monthly_1" {{ $periodId ? 'disabled' : '' }}>
                    <p class="text-xs text-gray-500 mt-1">{{ __('Lowercase letters and underscores only') }}</p>
                    @error('period_key') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Period Type') }} *</label>
                    <select wire:model="period_type" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500">
                        @foreach($periodTypes as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Name (English)') }} *</label>
                    <input type="text" wire:model="period_name" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500">
                    @error('period_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Name (Arabic)') }}</label>
                    <input type="text" wire:model="period_name_ar" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500" dir="rtl">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg p-6 space-y-4">
            <h3 class="font-medium text-gray-800 border-b pb-2">{{ __('Duration & Pricing') }}</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Duration Value') }} *</label>
                    <input type="number" wire:model="duration_value" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500" min="1">
                    @error('duration_value') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Duration Unit') }} *</label>
                    <select wire:model="duration_unit" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500">
                        @foreach($durationUnits as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Price Multiplier') }} *</label>
                    <input type="number" wire:model="price_multiplier" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500" step="0.01" min="0">
                    <p class="text-xs text-gray-500 mt-1">{{ __('e.g. 1.0 for base price, 0.9 for 10% discount') }}</p>
                    @error('price_multiplier') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Sort Order') }}</label>
                    <input type="number" wire:model="sort_order" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500" min="0">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg p-6 space-y-4">
            <h3 class="font-medium text-gray-800 border-b pb-2">{{ __('Settings') }}</h3>
            
            <div class="flex flex-wrap gap-6">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" wire:model="is_default" class="w-4 h-4 text-emerald-600 rounded focus:ring-emerald-500">
                    <span class="text-sm text-gray-700">{{ __('Default Period') }}</span>
                </label>
                
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" wire:model="is_active" class="w-4 h-4 text-emerald-600 rounded focus:ring-emerald-500">
                    <span class="text-sm text-gray-700">{{ __('Active') }}</span>
                </label>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.modules.rental-periods', ['module' => $module->id]) }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 transition">
                {{ __('Cancel') }}
            </a>
            <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition">
                {{ $periodId ? __('Update') : __('Create') }}
            </button>
        </div>
    </form>
</div>
