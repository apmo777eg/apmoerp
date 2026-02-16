<div class="space-y-6">
    <div class="flex flex-col lg:flex-row gap-6">
        <!-- Vehicle Models Section -->
        <div class="lg:w-1/2">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Vehicle Models') }}</h3>
                    <button type="button" wire:click="openVehicleForm" class="px-3 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-sm">
                        <x-icon name="plus" class="w-4 h-4 inline-block" /> {{ __('Add Vehicle') }}
                    </button>
                </div>
                
                <!-- Filters -->
                <div class="p-4 border-b border-gray-200 dark:border-gray-700 space-y-3">
                    <div class="flex flex-col sm:flex-row gap-3">
                        <div class="flex-1">
                            <input type="text" wire:model.live.debounce.300ms="search" 
                                   placeholder="{{ __('Search brand or model...') }}"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white text-sm">
                        </div>
                        <div class="w-full sm:w-48">
                            <select wire:model.live="brandFilter" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white text-sm">
                                <option value="">{{ __('All Brands') }}</option>
                                @foreach($brands as $brand)
                                    <option value="{{ $brand }}">{{ $brand }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Vehicle List -->
                <div class="divide-y divide-gray-200 dark:divide-gray-700 max-h-96 overflow-y-auto">
                    @forelse($vehicleModels as $vehicle)
                        <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center justify-between">
                            <div class="flex-1">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $vehicle->brand }} {{ $vehicle->model }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    @if($vehicle->year_from && $vehicle->year_to)
                                        {{ $vehicle->year_from }} - {{ $vehicle->year_to }}
                                    @elseif($vehicle->year_from)
                                        {{ $vehicle->year_from }}+
                                    @endif
                                    @if($vehicle->engine_type)
                                        | {{ $vehicle->engine_type }}
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                @if($product)
                                    @php
                                        $isCompatible = $compatibilities->where('vehicle_model_id', $vehicle->id)->first();
                                    @endphp
                                    @if($isCompatible)
                                        <button type="button" wire:click="removeCompatibility({{ $vehicle->id }})" 
                                                class="px-2 py-1 bg-red-100 text-red-600 rounded text-xs hover:bg-red-200">
                                            <x-icon name="check" class="w-4 h-4 inline-block" /> {{ __('Added') }}
                                        </button>
                                    @else
                                        <button type="button" wire:click="quickAddCompatibility({{ $vehicle->id }})" 
                                                class="px-2 py-1 bg-emerald-100 text-emerald-600 rounded text-xs hover:bg-emerald-200">
                                            <x-icon name="plus" class="w-4 h-4 inline-block" /> {{ __('Add') }}
                                        </button>
                                    @endif
                                @endif
                                <button type="button" wire:click="editVehicle({{ $vehicle->id }})" class="text-gray-400 hover:text-blue-500">
                                    <x-icon name="pencil" class="w-4 h-4" />
                                </button>
                                <button type="button" wire:click="deleteVehicle({{ $vehicle->id }})" 
                                        wire:confirm="{{ __('Are you sure you want to delete this vehicle model?') }}"
                                        class="text-gray-400 hover:text-red-500">
                                    <x-icon name="trash" class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                            {{ __('No vehicle models found. Add your first vehicle model to get started.') }}
                        </div>
                    @endforelse
                </div>

                <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                    {{ $vehicleModels->links() }}
                </div>
            </div>
        </div>

        <!-- Product Compatibilities Section -->
        <div class="lg:w-1/2">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ __('Compatible Vehicles') }}
                        @if($product)
                            <span class="text-sm font-normal text-gray-500">- {{ $product->name }}</span>
                        @endif
                    </h3>
                    @if($product)
                        <button type="button" wire:click="openCompatibilityForm" class="px-3 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-sm">
                            <x-icon name="plus" class="w-4 h-4 inline-block" /> {{ __('Add Details') }}
                        </button>
                    @endif
                </div>

                @if($product)
                    <div class="divide-y divide-gray-200 dark:divide-gray-700 max-h-96 overflow-y-auto">
                        @forelse($compatibilities as $compat)
                            <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="font-medium text-gray-900 dark:text-white">
                                            {{ $compat->vehicleModel->brand }} {{ $compat->vehicleModel->model }}
                                            @if($compat->is_verified)
                                                <span class="ml-2 px-2 py-0.5 bg-green-100 text-green-800 text-xs rounded-full">{{ __('Verified') }}</span>
                                            @endif
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            @if($compat->vehicleModel->year_from)
                                                {{ $compat->vehicleModel->year_from }}
                                                @if($compat->vehicleModel->year_to)
                                                    - {{ $compat->vehicleModel->year_to }}
                                                @else
                                                    +
                                                @endif
                                            @endif
                                        </div>
                                        @if($compat->oem_number)
                                            <div class="text-xs text-gray-400 mt-1">
                                                {{ __('OEM') }}: {{ $compat->oem_number }}
                                            </div>
                                        @endif
                                        @if($compat->position)
                                            <div class="text-xs text-gray-400">
                                                {{ __('Position') }}: {{ $compat->position }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button type="button" wire:click="toggleVerified({{ $compat->id }})" 
                                                class="text-gray-400 hover:text-green-500" title="{{ __('Toggle Verified') }}">
                                            <x-icon name="{{ $compat->is_verified ? 'check-badge' : 'shield-check' }}" class="w-4 h-4" />
                                        </button>
                                        <button type="button" wire:click="removeCompatibility({{ $compat->vehicle_model_id }})" 
                                                class="text-gray-400 hover:text-red-500">
                                            <x-icon name="trash" class="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                                {{ __('No compatible vehicles assigned. Click "Add" on vehicle models to add compatibility.') }}
                            </div>
                        @endforelse
                    </div>
                @else
                    <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                        {{ __('Select a product to manage its vehicle compatibility.') }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Vehicle Model Form -->
    @if($showVehicleForm)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ $editingId ? __('Edit Vehicle Model') : __('Add Vehicle Model') }}
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Fill in the vehicle details below.') }}</p>
                </div>
                <button type="button" wire:click="closeVehicleForm" class="text-gray-400 hover:text-red-500">
                    <x-icon name="x-mark" class="w-5 h-5" />
                </button>
            </div>

            <div class="p-6">
                <form wire:submit="saveVehicle" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Brand') }} *</label>
                            <input type="text" wire:model="newBrand" list="brands-list"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                            <datalist id="brands-list">
                                @foreach($brands as $brand)
                                    <option value="{{ $brand }}">
                                @endforeach
                            </datalist>
                            @error('newBrand') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Model') }} *</label>
                            <input type="text" wire:model="newModel"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                            @error('newModel') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Year From') }}</label>
                            <input type="number" wire:model="newYearFrom" min="1900" max="2100"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Year To')}}</label>
                            <input type="number" wire:model="newYearTo" min="1900" max="2100"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Category') }}</label>
                            <select wire:model="newCategory" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                                <option value="">{{ __('Select...') }}</option>
                                <option value="sedan">{{ __('Sedan') }}</option>
                                <option value="suv">{{ __('SUV') }}</option>
                                <option value="truck">{{ __('Truck') }}</option>
                                <option value="van">{{ __('Van') }}</option>
                                <option value="motorcycle">{{ __('Motorcycle') }}</option>
                                <option value="bus">{{ __('Bus') }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Engine Type') }}</label>
                            <select wire:model="newEngineType" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                                <option value="">{{ __('Select...') }}</option>
                                <option value="gasoline">{{ __('Gasoline') }}</option>
                                <option value="diesel">{{ __('Diesel') }}</option>
                                <option value="electric">{{ __('Electric') }}</option>
                                <option value="hybrid">{{ __('Hybrid') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" wire:click="closeVehicleForm"
                                class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">
                            {{ $editingId ? __('Update') : __('Create') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Compatibility Details Form -->
    @if($showCompatibilityForm)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Add Compatibility with Details') }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Select a vehicle and provide optional details.') }}</p>
                </div>
                <button type="button" wire:click="closeCompatibilityForm" class="text-gray-400 hover:text-red-500">
                    <x-icon name="x-mark" class="w-5 h-5" />
                </button>
            </div>

            <div class="p-6">
                <form wire:submit="addCompatibility" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Vehicle Model') }} *</label>
                        <select wire:model="selectedVehicleId" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                            <option value="">{{ __('Select Vehicle...') }}</option>
                            @foreach(App\Models\VehicleModel::active()->orderBy('brand')->orderBy('model')->get() as $vehicle)
                                <option value="{{ $vehicle->id }}">{{ $vehicle->display_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('OEM Number') }}</label>
                        <input type="text" wire:model="oemNumber" placeholder="{{ __('e.g., 11427953129') }}"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Position') }}</label>
                        <select wire:model="position" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                            <option value="">{{ __('Select...') }}</option>
                            <option value="front">{{ __('Front') }}</option>
                            <option value="rear">{{ __('Rear') }}</option>
                            <option value="left">{{ __('Left') }}</option>
                            <option value="right">{{ __('Right') }}</option>
                            <option value="front_left">{{ __('Front Left') }}</option>
                            <option value="front_right">{{ __('Front Right') }}</option>
                            <option value="rear_left">{{ __('Rear Left') }}</option>
                            <option value="rear_right">{{ __('Rear Right') }}</option>
                            <option value="engine">{{ __('Engine') }}</option>
                            <option value="transmission">{{ __('Transmission') }}</option>
                            <option value="interior">{{ __('Interior') }}</option>
                            <option value="exterior">{{ __('Exterior') }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Notes') }}</label>
                        <textarea wire:model="notes" rows="2"
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"></textarea>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" wire:model="isVerified" id="is_verified"
                               class="w-4 h-4 text-emerald-600 border-gray-300 rounded focus:ring-emerald-500">
                        <label for="is_verified" class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ __('Mark as Verified') }}</label>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" wire:click="closeCompatibilityForm"
                                class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">
                            {{ __('Add Compatibility') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
