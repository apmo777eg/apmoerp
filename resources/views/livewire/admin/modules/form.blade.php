<div class="max-w-4xl mx-auto space-y-6" x-data="{ showAdvanced: false }">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-100">{{ $editMode ? __('Edit Module') : __('Add Module') }}</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Configure module name, appearance, and custom fields') }}</p>
        </div>
        <a href="{{ route('admin.modules.index') }}" class="erp-btn erp-btn-secondary">{{ __('Back') }}</a>
    </div>

    {{-- Help tip for non-technical users --}}
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <div class="flex gap-3">
            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="text-sm text-blue-700 dark:text-blue-300">
                <p class="font-medium">{{ __('What is a Module?') }}</p>
                <p class="mt-1">{{ __('Modules are major sections of the ERP system (like Sales, Inventory, HR). Each module can have its own custom fields that appear in forms throughout that section.') }}</p>
            </div>
        </div>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{-- Basic Information - Simplified --}}
        <div class="erp-card p-6">
            <h2 class="text-lg font-semibold text-slate-800 dark:text-slate-100 mb-4">{{ __('Basic Information') }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="erp-label">{{ __('Module Name') }} <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="name" class="erp-input @error('name') border-red-500 @enderror" placeholder="{{ __('e.g., Human Resources, Inventory') }}">
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ __('The display name for this module') }}</p>
                    @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="erp-label">{{ __('Module Name (Arabic)') }}</label>
                    <input type="text" wire:model="name_ar" dir="rtl" class="erp-input" placeholder="{{ __('e.g., الموارد البشرية') }}">
                </div>

                <div class="md:col-span-2">
                    <label class="erp-label">{{ __('Description') }}</label>
                    <textarea wire:model="description" rows="2" class="erp-input" placeholder="{{ __('Brief description of what this module does...') }}"></textarea>
                </div>

                <div class="md:col-span-2">
                    <label class="erp-label">{{ __('Description (Arabic)') }}</label>
                    <textarea wire:model="description_ar" rows="2" dir="rtl" class="erp-input"></textarea>
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" wire:model="is_active" id="is_active" class="rounded border-slate-300 dark:border-slate-600 text-emerald-600 dark:bg-slate-700">
                    <label for="is_active" class="text-sm text-slate-700 dark:text-slate-300">{{ __('Active') }}</label>
                    <span class="text-xs text-slate-500 dark:text-slate-400">({{ __('Inactive modules are hidden from users') }})</span>
                </div>
            </div>
        </div>

        {{-- Appearance - Simplified --}}
        <div class="erp-card p-6">
            <h2 class="text-lg font-semibold text-slate-800 dark:text-slate-100 mb-4">{{ __('Appearance') }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="erp-label">{{ __('Icon') }}</label>
                    <div x-data="{ showIconPicker: false, searchIcon: '' }" class="relative">
                        <div class="flex gap-3 items-center">
                            <button type="button" @click="showIconPicker = !showIconPicker" 
                                    class="erp-input w-20 text-center text-2xl cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-700"
                                    title="{{ __('Click to select icon') }}"
                                    aria-describedby="icon-help-text">
                                {{ $icon }}
                            </button>
                            <span id="icon-help-text" class="text-sm text-slate-500 dark:text-slate-400">{{ __('Click to change icon') }}</span>
                        </div>
                        
                        {{-- Icon Picker Dropdown --}}
                        <div x-show="showIconPicker" 
                             x-transition
                             @click.away="showIconPicker = false"
                             class="absolute z-[9999] mt-2 w-full max-w-md bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg shadow-lg p-4">
                            
                            {{-- Search --}}
                            <input type="text" x-model="searchIcon" 
                                   placeholder="{{ __('Search icons...') }}"
                                   class="erp-input text-sm mb-3 w-full">
                            
                            {{-- Icon Categories --}}
                            <div class="max-h-64 overflow-y-auto space-y-3">
                                {{-- Business & Commerce --}}
                                <div x-show="searchIcon === '' || '{{ __('Business Commerce Sales Money Finance') }}'.toLowerCase().includes(searchIcon.toLowerCase())">
                                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">{{ __('Business & Commerce') }}</p>
                                    <div class="flex flex-wrap gap-1">
                                        @foreach(['💰', '💵', '💳', '🏦', '📈', '📉', '💹', '🛒', '🛍️', '🏪', '🏬', '💼', '📊', '💲', '🏧'] as $emoji)
                                            <button type="button" 
                                                    wire:click="$set('icon', '{{ $emoji }}')" 
                                                    @click="showIconPicker = false"
                                                    class="text-xl p-1.5 rounded hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                                                {{ $emoji }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                                
                                {{-- People & Employees --}}
                                <div x-show="searchIcon === '' || '{{ __('People Employees Users HR Team') }}'.toLowerCase().includes(searchIcon.toLowerCase())">
                                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">{{ __('People & HR') }}</p>
                                    <div class="flex flex-wrap gap-1">
                                        @foreach(['👥', '👤', '👨‍💼', '👩‍💼', '🧑‍💼', '👷', '👨‍🔧', '👩‍🔧', '👨‍🏭', '👩‍🏭', '🤝', '👨‍👩‍👦', '📋'] as $emoji)
                                            <button type="button" 
                                                    wire:click="$set('icon', '{{ $emoji }}')" 
                                                    @click="showIconPicker = false"
                                                    class="text-xl p-1.5 rounded hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                                                {{ $emoji }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                                
                                {{-- Real Estate & Property --}}
                                <div x-show="searchIcon === '' || '{{ __('Property Real Estate Buildings Rental Home') }}'.toLowerCase().includes(searchIcon.toLowerCase())">
                                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">{{ __('Real Estate & Property') }}</p>
                                    <div class="flex flex-wrap gap-1">
                                        @foreach(['🏠', '🏡', '🏢', '🏣', '🏤', '🏥', '🏨', '🏩', '🏪', '🏫', '🏬', '🗝️', '🔑', '🛖'] as $emoji)
                                            <button type="button" 
                                                    wire:click="$set('icon', '{{ $emoji }}')" 
                                                    @click="showIconPicker = false"
                                                    class="text-xl p-1.5 rounded hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                                                {{ $emoji }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                                
                                {{-- Vehicles & Transport --}}
                                <div x-show="searchIcon === '' || '{{ __('Vehicles Transport Cars Trucks Delivery') }}'.toLowerCase().includes(searchIcon.toLowerCase())">
                                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">{{ __('Vehicles & Transport') }}</p>
                                    <div class="flex flex-wrap gap-1">
                                        @foreach(['🚗', '🚙', '🚕', '🚐', '🚚', '🚛', '🚜', '🏎️', '🛵', '🏍️', '🚲', '🚁', '✈️', '🚢'] as $emoji)
                                            <button type="button" 
                                                    wire:click="$set('icon', '{{ $emoji }}')" 
                                                    @click="showIconPicker = false"
                                                    class="text-xl p-1.5 rounded hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                                                {{ $emoji }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                                
                                {{-- Products & Inventory --}}
                                <div x-show="searchIcon === '' || '{{ __('Products Inventory Stock Warehouse Box') }}'.toLowerCase().includes(searchIcon.toLowerCase())">
                                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">{{ __('Products & Inventory') }}</p>
                                    <div class="flex flex-wrap gap-1">
                                        @foreach(['📦', '🗃️', '🗄️', '📥', '📤', '🏷️', '🔖', '🎁', '🧰', '🔧', '🔩', '⚙️', '🛠️', '🔬'] as $emoji)
                                            <button type="button" 
                                                    wire:click="$set('icon', '{{ $emoji }}')" 
                                                    @click="showIconPicker = false"
                                                    class="text-xl p-1.5 rounded hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                                                {{ $emoji }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                                
                                {{-- Documents & Reports --}}
                                <div x-show="searchIcon === '' || '{{ __('Documents Reports Files Paper') }}'.toLowerCase().includes(searchIcon.toLowerCase())">
                                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">{{ __('Documents & Reports') }}</p>
                                    <div class="flex flex-wrap gap-1">
                                        @foreach(['📄', '📃', '📑', '📁', '🗂️', '📂', '🗒️', '📝', '✏️', '🖊️', '📰', '🗞️', '📓', '📒'] as $emoji)
                                            <button type="button" 
                                                    wire:click="$set('icon', '{{ $emoji }}')" 
                                                    @click="showIconPicker = false"
                                                    class="text-xl p-1.5 rounded hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                                                {{ $emoji }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                                
                                {{-- Manufacturing & Industry --}}
                                <div x-show="searchIcon === '' || '{{ __('Manufacturing Industry Factory Production') }}'.toLowerCase().includes(searchIcon.toLowerCase())">
                                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">{{ __('Manufacturing & Industry') }}</p>
                                    <div class="flex flex-wrap gap-1">
                                        @foreach(['🏭', '⚒️', '🔨', '🪛', '🔧', '⚙️', '🛠️', '⛏️', '🧱', '🪵', '🔩', '🗜️', '⚗️', '🧪'] as $emoji)
                                            <button type="button" 
                                                    wire:click="$set('icon', '{{ $emoji }}')" 
                                                    @click="showIconPicker = false"
                                                    class="text-xl p-1.5 rounded hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                                                {{ $emoji }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                                
                                {{-- Medical & Health --}}
                                <div x-show="searchIcon === '' || '{{ __('Medical Health Hospital Doctor') }}'.toLowerCase().includes(searchIcon.toLowerCase())">
                                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">{{ __('Medical & Health') }}</p>
                                    <div class="flex flex-wrap gap-1">
                                        @foreach(['🏥', '💊', '💉', '🩺', '🩹', '🩻', '🧬', '🔬', '🩼', '♿', '🚑', '⚕️', '🧪', '🩸'] as $emoji)
                                            <button type="button" 
                                                    wire:click="$set('icon', '{{ $emoji }}')" 
                                                    @click="showIconPicker = false"
                                                    class="text-xl p-1.5 rounded hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                                                {{ $emoji }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                                
                                {{-- Misc & Settings --}}
                                <div x-show="searchIcon === '' || '{{ __('Settings Configuration System') }}'.toLowerCase().includes(searchIcon.toLowerCase())">
                                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">{{ __('Settings & System') }}</p>
                                    <div class="flex flex-wrap gap-1">
                                        @foreach(['⚙️', '🔧', '🛡️', '🔒', '🔓', '🗝️', '🔑', '📡', '💻', '🖥️', '⌨️', '🖱️', '📱', '🔔'] as $emoji)
                                            <button type="button" 
                                                    wire:click="$set('icon', '{{ $emoji }}')" 
                                                    @click="showIconPicker = false"
                                                    class="text-xl p-1.5 rounded hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                                                {{ $emoji }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ __('Click the icon button to open the picker and select an icon') }}</p>
                </div>

                <div>
                    <label class="erp-label">{{ __('Color Theme') }}</label>
                    <div class="flex gap-2 flex-wrap">
                        @foreach(['emerald' => 'bg-emerald-500', 'blue' => 'bg-blue-500', 'purple' => 'bg-purple-500', 'red' => 'bg-red-500', 'amber' => 'bg-amber-500', 'cyan' => 'bg-cyan-500', 'pink' => 'bg-pink-500', 'indigo' => 'bg-indigo-500', 'teal' => 'bg-teal-500', 'orange' => 'bg-orange-500'] as $colorName => $colorClass)
                            <label class="cursor-pointer" title="{{ ucfirst($colorName) }}">
                                <input type="radio" wire:model.live="color" value="{{ $colorName }}" class="sr-only peer">
                                <div class="w-8 h-8 rounded-full {{ $colorClass }} peer-checked:ring-2 peer-checked:ring-offset-2 peer-checked:ring-slate-400 dark:peer-checked:ring-offset-slate-800 hover:scale-110 transition-transform"></div>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Advanced Settings - Hidden by default --}}
        <div class="erp-card overflow-hidden">
            <button type="button" @click="showAdvanced = !showAdvanced" 
                    class="w-full p-4 flex items-center justify-between text-left hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-slate-500" :class="showAdvanced && 'rotate-90'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                    <span class="font-medium text-slate-700 dark:text-slate-200">{{ __('Advanced Settings') }}</span>
                </div>
                <span class="text-xs text-slate-500 dark:text-slate-400">{{ __('Technical options for developers') }}</span>
            </button>
            
            <div x-show="showAdvanced" 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 max-h-0"
                 x-transition:enter-end="opacity-100 max-h-[1000px]"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 max-h-[1000px]"
                 x-transition:leave-end="opacity-0 max-h-0"
                 class="border-t border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="p-6">
                    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-3 mb-4">
                        <p class="text-xs text-amber-700 dark:text-amber-300">
                            <strong>{{ __('Warning') }}:</strong> {{ __('These settings are for technical users. Changing them incorrectly may cause issues.') }}
                        </p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="erp-label">{{ __('Module Key') }} <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="key" class="erp-input font-mono text-sm @error('key') border-red-500 @enderror" placeholder="e.g., hrm, inventory">
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ __('Unique identifier used in code (lowercase, no spaces)') }}</p>
                            @error('key') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="erp-label">{{ __('Sort Order') }}</label>
                            <input type="number" wire:model="sort_order" class="erp-input" min="0">
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ __('Lower numbers appear first in menus') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Custom Fields - Simplified --}}
        <div class="erp-card p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-800 dark:text-slate-100">{{ __('Custom Fields') }}</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">{{ __('Add extra fields that will appear in forms for this module') }}</p>
                </div>
                <button type="button" wire:click="addCustomField" class="erp-btn erp-btn-secondary text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    {{ __('Add Field') }}
                </button>
            </div>

            @if(count($customFields) > 0)
                <div class="space-y-4">
                    @foreach($customFields as $index => $field)
                        <div class="border border-slate-200 dark:border-slate-700 rounded-lg p-4 bg-slate-50 dark:bg-slate-800/50">
                            <div class="flex items-start justify-between mb-3">
                                <span class="text-sm font-medium text-slate-600 dark:text-slate-300">{{ __('Field') }} #{{ $index + 1 }}</span>
                                <button type="button" wire:click="removeCustomField({{ $index }})" class="text-red-500 hover:text-red-700 dark:hover:text-red-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div>
                                    <label class="erp-label text-xs">{{ __('Field Name') }}</label>
                                    <input type="text" wire:model="customFields.{{ $index }}.field_label" class="erp-input text-sm" placeholder="{{ __('e.g., Department') }}">
                                </div>
                                <div>
                                    <label class="erp-label text-xs">{{ __('Field Name (Arabic)') }}</label>
                                    <input type="text" wire:model="customFields.{{ $index }}.field_label_ar" dir="rtl" class="erp-input text-sm" placeholder="{{ __('e.g., القسم') }}">
                                </div>
                                <div>
                                    <label class="erp-label text-xs">{{ __('Field Type') }}</label>
                                    <select wire:model="customFields.{{ $index }}.field_type" class="erp-input text-sm">
                                        <option value="text">📝 {{ __('Text') }}</option>
                                        <option value="textarea">📄 {{ __('Long Text') }}</option>
                                        <option value="number">🔢 {{ __('Number') }}</option>
                                        <option value="email">✉️ {{ __('Email') }}</option>
                                        <option value="phone">📱 {{ __('Phone') }}</option>
                                        <option value="date">📅 {{ __('Date') }}</option>
                                        <option value="datetime">🕐 {{ __('Date & Time') }}</option>
                                        <option value="select">📋 {{ __('Dropdown List') }}</option>
                                        <option value="checkbox">☑️ {{ __('Checkbox') }}</option>
                                        <option value="file">📎 {{ __('File Upload') }}</option>
                                        <option value="image">🖼️ {{ __('Image') }}</option>
                                    </select>
                                </div>
                                <div class="flex items-center gap-4 md:col-span-2 lg:col-span-3">
                                    <label class="flex items-center gap-2 text-sm cursor-pointer">
                                        <input type="checkbox" wire:model="customFields.{{ $index }}.is_required" class="rounded border-slate-300 dark:border-slate-600 text-emerald-600 dark:bg-slate-700">
                                        <span class="text-slate-700 dark:text-slate-300">{{ __('Required field') }}</span>
                                    </label>
                                    <label class="flex items-center gap-2 text-sm cursor-pointer">
                                        <input type="checkbox" wire:model="customFields.{{ $index }}.is_active" class="rounded border-slate-300 dark:border-slate-600 text-emerald-600 dark:bg-slate-700">
                                        <span class="text-slate-700 dark:text-slate-300">{{ __('Active') }}</span>
                                    </label>
                                    
                                    {{-- Technical field key (hidden by default) --}}
                                    <div x-show="showAdvanced" class="flex-1">
                                        <label class="erp-label text-xs">{{ __('Field Key (Technical)') }}</label>
                                        <input type="text" wire:model="customFields.{{ $index }}.field_key" class="erp-input text-sm font-mono" placeholder="e.g., department_id">
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-lg">
                    <svg class="w-12 h-12 mx-auto text-slate-400 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">{{ __('No custom fields yet') }}</p>
                    <p class="text-xs text-slate-400 dark:text-slate-500">{{ __('Click "Add Field" to create custom fields for this module') }}</p>
                </div>
            @endif
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.modules.index') }}" class="erp-btn erp-btn-secondary">{{ __('Cancel') }}</a>
            <button type="submit" class="erp-btn erp-btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                {{ $editMode ? __('Update Module') : __('Create Module') }}
            </button>
        </div>
    </form>
</div>
