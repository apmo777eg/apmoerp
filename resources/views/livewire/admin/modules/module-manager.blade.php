<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ __('Module Management') }}
                </h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    {{ __('Manage system modules and their navigation') }}
                </p>
            </div>
            <button type="button" 
                wire:click="openRegistrationModal"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
                <span class="flex items-center">
                    <svg class="w-5 h-5 mr-2 rtl:ml-2 rtl:mr-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    {{ __('Register New Module') }}
                </span>
            </button>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6 p-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Search -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('Search') }}
                    </label>
                    <input 
                        type="text" 
                        wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('Search modules...') }}"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                    />
                </div>

                <!-- Category Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('Category') }}
                    </label>
                    <select 
                        wire:model.live="filterCategory"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                    >
                        <option value="">{{ __('All Categories') }}</option>
                        @foreach($this->categories as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('Status') }}
                    </label>
                    <select 
                        wire:model.live="filterStatus"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                    >
                        <option value="">{{ __('All') }}</option>
                        <option value="active">{{ __('Active') }}</option>
                        <option value="inactive">{{ __('Inactive') }}</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Modules Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($this->modules as $module)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
                <!-- Module Header -->
                <div class="p-6" style="background: linear-gradient(135deg, {{ $module->color }}22 0%, {{ $module->color }}11 100%);">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center space-x-3 rtl:space-x-reverse">
                            <span class="text-4xl">{{ $module->display_icon }}</span>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                    {{ $module->localized_name }}
                                </h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $module->module_key }}
                                </p>
                            </div>
                        </div>
                        
                        <!-- Status Badge -->
                        @if($module->is_active)
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                            {{ __('Active') }}
                        </span>
                        @else
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                            {{ __('Inactive') }}
                        </span>
                        @endif
                    </div>

                    @if($module->localized_description)
                    <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                        {{ $module->localized_description }}
                    </p>
                    @endif
                </div>

                <!-- Module Details -->
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <dl class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <dt class="text-gray-600 dark:text-gray-400">{{ __('Category') }}</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">
                                {{ $this->categories[$module->category] ?? $module->category }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-600 dark:text-gray-400">{{ __('Type') }}</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">
                                {{ $module->getModuleTypeLabel() }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-600 dark:text-gray-400">{{ __('Navigation Items') }}</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">
                                {{ $module->navigation->count() }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-600 dark:text-gray-400">{{ __('Core Module') }}</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">
                                {{ $module->is_core ? __('Yes') : __('No') }}
                            </dd>
                        </div>
                    </dl>

                    <!-- Features -->
                    <div class="mt-4 flex flex-wrap gap-2">
                        @if($module->supports_reporting)
                        <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                            📊 {{ __('Reporting') }}
                        </span>
                        @endif
                        @if($module->supports_custom_fields)
                        <span class="px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-800">
                            🔧 {{ __('Custom Fields') }}
                        </span>
                        @endif
                        @if($module->supports_items)
                        <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                            📦 {{ __('Items') }}
                        </span>
                        @endif
                    </div>
                </div>

                <!-- Actions -->
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <button type="button" 
                            wire:click="toggleModuleStatus({{ $module->id }})"
                            class="text-sm font-medium {{ $module->is_active ? 'text-red-600 hover:text-red-700' : 'text-green-600 hover:text-green-700' }}"
                        >
                            {{ $module->is_active ? __('Deactivate') : __('Activate') }}
                        </button>
                        
                        <div class="flex items-center space-x-2 rtl:space-x-reverse">
                            <a 
                                href="{{ route('admin.modules.edit', $module->id) }}"
                                class="text-sm font-medium text-blue-600 hover:text-blue-700"
                            >
                                {{ __('Edit') }}
                            </a>
                            @if(!$module->is_core)
                            <button type="button" 
                                wire:click="deleteModule({{ $module->id }})"
                                wire:confirm="{{ __('Are you sure you want to unregister this module?') }}"
                                class="text-sm font-medium text-red-600 hover:text-red-700"
                            >
                                {{ __('Delete') }}
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $this->modules->links() }}
        </div>
    </div>

    <!-- Registration Modal -->
    @if($showRegistrationModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" style="background: rgba(0, 0, 0, 0.5);">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                        {{ __('Register New Module') }}
                    </h2>
                    <button type="button" 
                        wire:click="closeRegistrationModal"
                        class="text-gray-400 hover:text-gray-600"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form wire:submit="registerModule" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Module Key -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('Module Key') }} *
                            </label>
                            <input 
                                type="text" 
                                wire:model="formKey"
                                placeholder="e.g., my_module"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                            />
                            @error('formKey') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <!-- Icon -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('Icon') }} *
                            </label>
                            <input 
                                type="text" 
                                wire:model="formIcon"
                                placeholder="📦"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                            />
                            @error('formIcon') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <!-- Name (English) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('Name (English)') }} *
                            </label>
                            <input 
                                type="text" 
                                wire:model="formName"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                            />
                            @error('formName') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <!-- Name (Arabic) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('Name (Arabic)') }} *
                            </label>
                            <input 
                                type="text" 
                                wire:model="formNameAr"
                                dir="rtl"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                            />
                            @error('formNameAr') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <!-- Category -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('Category') }} *
                            </label>
                            <select 
                                wire:model="formCategory"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                            >
                                @foreach($this->categories as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('formCategory') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <!-- Color -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('Color') }} *
                            </label>
                            <input 
                                type="color" 
                                wire:model="formColor"
                                class="w-full h-10 px-1 py-1 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500"
                            />
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ __('Description') }}
                        </label>
                        <textarea 
                            wire:model="formDescription"
                            rows="3"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                        ></textarea>
                    </div>

                    <!-- Features -->
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('Features') }}
                        </label>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="checkbox" wire:model="formIsActive" class="rounded" />
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ __('Active') }}</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" wire:model="formSupportsReporting" class="rounded" />
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ __('Supports Reporting') }}</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" wire:model="formSupportsCustomFields" class="rounded" />
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ __('Supports Custom Fields') }}</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" wire:model="formSupportsItems" class="rounded" />
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ __('Supports Items/Products') }}</span>
                            </label>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end space-x-3 rtl:space-x-reverse pt-4">
                        <button 
                            type="button"
                            wire:click="closeRegistrationModal"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
                        >
                            {{ __('Cancel') }}
                        </button>
                        <button 
                            type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                        >
                            {{ __('Register Module') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
