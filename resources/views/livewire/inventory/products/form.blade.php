{{-- resources/views/livewire/inventory/products/form.blade.php --}}
<div class="space-y-4">
    <div class="flex items-center justify-between gap-2">
        <div>
            <h1 class="text-lg font-semibold text-slate-800">
                {{ $productId ? __('Edit Product') : __('Create Product') }}
            </h1>
            <p class="text-sm text-slate-500">
                {{ __('Basic product information for inventory.') }}
            </p>
        </div>
    </div>

    <form wire:submit.prevent="save" class="space-y-6">
        {{-- Module Selection - Required for New Products --}}
        <div class="rounded-2xl border border-emerald-200 bg-gradient-to-br from-emerald-50/50 to-white p-4 shadow-sm">
            <h2 class="text-sm font-semibold text-emerald-800 mb-3 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
                {{ __('Module Selection') }}
                @if(!$productId)
                    <span class="text-red-500 text-xs">*{{ __('Required') }}</span>
                @endif
            </h2>
            <div class="space-y-1">
                <label class="block text-sm font-medium text-slate-700">
                    {{ __('Product Module') }}
                    @if(!$productId)
                        <span class="text-red-500">*</span>
                    @endif
                </label>
                <select wire:model.live="selectedModuleId" class="erp-input" {{ !$productId ? 'required' : '' }}>
                    <option value="">{{ __('Select a module that supports items...') }}</option>
                    @foreach($modules as $module)
                        <option value="{{ $module->id }}">
                            {{ $module->name }}
                            @if($module->is_service)
                                ({{ __('Service') }})
                            @elseif($module->is_rental)
                                ({{ __('Rental') }})
                            @else
                                ({{ __('Stock Item') }})
                            @endif
                        </option>
                    @endforeach
                </select>
                @error('form.module_id')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
                
                @if(!$productId)
                    <div class="mt-2 p-2 bg-blue-50 border border-blue-200 rounded-md">
                        <p class="text-xs text-blue-800">
                            <strong>{{ __('Note') }}:</strong> {{ __('Only modules that support items/products are shown. The selected module will determine available custom fields and behavior.') }}
                        </p>
                    </div>
                @else
                    <p class="text-xs text-slate-500 mt-1">
                        {{ __('Module-specific custom fields are loaded based on selection') }}
                    </p>
                @endif
            </div>
        </div>

        {{-- Basic Product Information --}}
        <div class="rounded-2xl border border-slate-200 bg-white/80 p-4 shadow-sm shadow-emerald-500/10">
            <h2 class="text-sm font-semibold text-slate-800 mb-3 flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
                {{ __('Basic Information') }}
            </h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div class="space-y-1">
                    <label class="block text-sm font-medium text-slate-700">
                        {{ __('Name') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" wire:model="form.name" class="erp-input" placeholder="{{ __('Product name') }}">
                    @error('form.name')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-1">
                    <label class="block text-sm font-medium text-slate-700">
                        {{ __('SKU') }}
                    </label>
                    <input type="text" wire:model="form.sku" class="erp-input" placeholder="{{ __('Stock Keeping Unit') }}">
                    @error('form.sku')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-1">
                    <label class="block text-sm font-medium text-slate-700">
                        {{ __('Barcode') }}
                    </label>
                    <input type="text" wire:model="form.barcode" class="erp-input" placeholder="{{ __('EAN/UPC Barcode') }}">
                    @error('form.barcode')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Category and Unit Section --}}
        <div class="rounded-2xl border border-slate-200 bg-white/80 p-4 shadow-sm shadow-emerald-500/10">
            <h2 class="text-sm font-semibold text-slate-800 mb-3 flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                </svg>
                {{ __('Classification') }}
            </h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="space-y-1">
                    <div class="flex items-center justify-between">
                        <label class="block text-sm font-medium text-slate-700">
                            {{ __('Category') }}
                        </label>
                        <x-quick-add-link 
                            :route="route('app.inventory.categories.index')" 
                            label="{{ __('Add Category') }}"
                            permission="inventory.categories.view" />
                    </div>
                    <select wire:model="form.category_id" class="erp-input">
                        <option value="">{{ __('Select Category') }}</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('form.category_id')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-1">
                    <div class="flex items-center justify-between">
                        <label class="block text-sm font-medium text-slate-700">
                            {{ __('Unit of Measure') }}
                        </label>
                        <x-quick-add-link 
                            :route="route('app.inventory.units.index')" 
                            label="{{ __('Add Unit') }}"
                            permission="inventory.units.view" />
                    </div>
                    <select wire:model="form.unit_id" class="erp-input">
                        <option value="">{{ __('Select Unit') }}</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->symbol }})</option>
                        @endforeach
                    </select>
                    @error('form.unit_id')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Pricing Section --}}
        <div class="rounded-2xl border border-slate-200 bg-white/80 p-4 shadow-sm shadow-emerald-500/10">
            <h2 class="text-sm font-semibold text-slate-800 mb-3 flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                {{ __('Pricing') }}
            </h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="space-y-1">
                    <label class="block text-sm font-medium text-slate-700">
                        {{ __('Sale price') }} <span class="text-red-500">*</span>
                    </label>
                    <div class="flex gap-2">
                        <div class="relative flex-1">
                            <input type="number" step="0.01" min="0" wire:model="form.price" class="erp-input">
                        </div>
                        <select wire:model="form.price_currency" class="erp-input w-24">
                            @if(is_array($currencies))
                                @foreach($currencies as $currency)
                                    <option value="{{ $currency['code'] ?? '' }}">{{ $currency['code'] ?? '' }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    @error('form.price')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-1">
                    <label class="block text-sm font-medium text-slate-700">
                        {{ __('Cost price') }}
                    </label>
                    <div class="flex gap-2">
                        <div class="relative flex-1">
                            <input type="number" step="0.01" min="0" wire:model="form.cost" class="erp-input" {{ $form['type'] === 'service' ? 'disabled' : '' }}>
                        </div>
                        <select wire:model="form.cost_currency" class="erp-input w-24" {{ $form['type'] === 'service' ? 'disabled' : '' }}>
                            @if(is_array($currencies))
                                @foreach($currencies as $currency)
                                    <option value="{{ $currency['code'] ?? '' }}">{{ $currency['code'] ?? '' }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    @error('form.cost')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                    @if($form['type'] === 'service')
                        <p class="text-xs text-amber-600">{{ __('Cost is not applicable for services') }}</p>
                    @endif
                </div>

                <div class="space-y-1">
                    <label class="block text-sm font-medium text-slate-700">
                        {{ __('Type') }}
                    </label>
                    <select wire:model.live="form.type" class="erp-input">
                        <option value="stock">{{ __('Stock') }}</option>
                        <option value="service">{{ __('Service') }}</option>
                    </select>
                    @error('form.type')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-1">
                    <label class="block text-sm font-medium text-slate-700">
                        {{ __('Status') }}
                    </label>
                    <select wire:model="form.status" class="erp-input">
                        <option value="active">{{ __('Active') }}</option>
                        <option value="inactive">{{ __('Inactive') }}</option>
                    </select>
                    @error('form.status')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Product Image Section --}}
        <div class="rounded-2xl border border-slate-200 bg-white/80 p-4 shadow-sm shadow-emerald-500/10">
            <h2 class="text-sm font-semibold text-slate-800 mb-3 flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                {{ __('Product Image') }}
            </h2>
            <div class="space-y-3">
                <div class="space-y-1">
                    <label class="block text-sm font-medium text-slate-700">
                        {{ __('Thumbnail') }}
                    </label>
                    <p class="text-xs text-slate-500 mb-2">{{ __('Select an image from the Media Library') }}</p>
                    <livewire:components.media-picker 
                        :value="$thumbnail_media_id"
                        accept-mode="image"
                        :max-size="2048"
                        field-id="product-thumbnail"
                        wire:key="product-thumbnail-picker-{{ $thumbnail_media_id ?: 'empty' }}"
                    ></livewire:components.media-picker>
                    @error('form.thumbnail')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Module-Specific Dynamic Fields --}}
        @if (!empty($dynamicSchema))
            <div class="rounded-2xl border border-purple-200 bg-gradient-to-br from-purple-50/50 to-white p-4 shadow-sm">
                <h2 class="text-sm font-semibold text-purple-800 mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    {{ __('Module-Specific Fields') }}
                    @if($selectedModuleId)
                        <span class="px-2 py-0.5 rounded-full bg-purple-100 text-purple-700 text-xs">
                            {{ $modules->firstWhere('id', $selectedModuleId)?->name }}
                        </span>
                    @endif
                </h2>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @php
                        $groupedFields = collect($dynamicSchema)->groupBy('group');
                    @endphp
                    
                    @foreach($groupedFields as $groupName => $fields)
                        @if($groupName && count($groupedFields) > 1)
                            <div class="col-span-full">
                                <h3 class="text-xs font-semibold text-slate-600 uppercase tracking-wide border-b border-slate-200 pb-1 mb-2">
                                    {{ __($groupName) }}
                                </h3>
                            </div>
                        @endif
                        
                        @foreach($fields as $field)
                            <div class="space-y-1">
                                <label class="block text-sm font-medium text-slate-700">
                                    {{ $field['label'] }}
                                    @if($field['required'])
                                        <span class="text-red-500">*</span>
                                    @endif
                                </label>
                                
                                @switch($field['type'])
                                    @case('textarea')
                                        <textarea 
                                            wire:model="dynamicData.{{ $field['key'] }}"
                                            class="erp-input min-h-[80px]"
                                            placeholder="{{ $field['placeholder'] ?? '' }}"
                                        ></textarea>
                                        @break
                                    
                                    @case('select')
                                        <select wire:model="dynamicData.{{ $field['key'] }}" class="erp-input">
                                            <option value="">{{ __('Select') }}...</option>
                                            @foreach($field['options'] ?? [] as $optKey => $optVal)
                                                <option value="{{ is_numeric($optKey) ? $optVal : $optKey }}">
                                                    {{ is_numeric($optKey) ? $optVal : $optVal }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @break
                                    
                                    @case('checkbox')
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input 
                                                type="checkbox"
                                                wire:model="dynamicData.{{ $field['key'] }}"
                                                class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                            >
                                            <span class="text-sm text-slate-600">{{ $field['placeholder'] ?? __('Yes') }}</span>
                                        </label>
                                        @break
                                    
                                    @case('radio')
                                        <div class="flex flex-wrap gap-3">
                                            @foreach($field['options'] ?? [] as $optKey => $optVal)
                                                <label class="flex items-center gap-1.5 cursor-pointer">
                                                    <input 
                                                        type="radio"
                                                        wire:model="dynamicData.{{ $field['key'] }}"
                                                        value="{{ is_numeric($optKey) ? $optVal : $optKey }}"
                                                        class="border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                                    >
                                                    <span class="text-sm text-slate-600">{{ is_numeric($optKey) ? $optVal : $optVal }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                        @break
                                    
                                    @case('number')
                                        <input 
                                            type="number"
                                            step="any"
                                            wire:model="dynamicData.{{ $field['key'] }}"
                                            class="erp-input"
                                            placeholder="{{ $field['placeholder'] ?? '' }}"
                                        >
                                        @break
                                    
                                    @case('date')
                                        <input 
                                            type="date"
                                            wire:model="dynamicData.{{ $field['key'] }}"
                                            class="erp-input"
                                        >
                                        @break
                                    
                                    @case('color')
                                        <div class="flex items-center gap-2">
                                            <input 
                                                type="color"
                                                wire:model="dynamicData.{{ $field['key'] }}"
                                                class="h-10 w-14 rounded border border-slate-300 cursor-pointer"
                                            >
                                            <input 
                                                type="text"
                                                wire:model="dynamicData.{{ $field['key'] }}"
                                                class="erp-input flex-1"
                                                placeholder="#000000"
                                            >
                                        </div>
                                        @break
                                    
                                    @default
                                        <input 
                                            type="{{ $field['type'] }}"
                                            wire:model="dynamicData.{{ $field['key'] }}"
                                            class="erp-input"
                                            placeholder="{{ $field['placeholder'] ?? '' }}"
                                        >
                                @endswitch
                                
                                @error("dynamicData.{$field['key']}")
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @endforeach
                    @endforeach
                </div>
            </div>
        @elseif($selectedModuleId)
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6 text-center">
                <svg class="w-10 h-10 text-slate-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                <p class="text-sm text-slate-500">{{ __('No custom fields defined for this module') }}</p>
                <p class="text-xs text-slate-400 mt-1">{{ __('You can add custom fields in Admin > Modules > Custom Fields') }}</p>
            </div>
        @endif

        {{-- Form Actions --}}
        <div class="flex justify-end gap-2 pt-4 border-t border-slate-200">
            <a href="{{ route('app.inventory.products.index') }}"
               class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition-colors">
                {{ __('Cancel') }}
            </a>
            <button type="submit" class="erp-btn-primary" wire:loading.attr="disabled">
                <span wire:loading.remove>
                    {{ $productId ? __('Save changes') : __('Create product') }}
                </span>
                <span wire:loading class="flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ __('Saving...') }}
                </span>
            </button>
        </div>
    </form>
</div>
