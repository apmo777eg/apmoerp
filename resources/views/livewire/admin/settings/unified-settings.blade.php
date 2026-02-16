<div class="container mx-auto px-4 py-6"
    x-data="{
        validTabs: @js(array_keys($tabs)),
        init() {
            // Handle hash on initial load
            const hash = window.location.hash.slice(1);
            if (hash && this.isValidTab(hash)) {
                this.switchToTab(hash);
            }
            
            // Handle browser back/forward
            window.addEventListener('popstate', () => {
                const hash = window.location.hash.slice(1);
                if (hash && this.isValidTab(hash)) {
                    this.switchToTab(hash);
                }
            });
        },
        isValidTab(tab) {
            return this.validTabs.includes(tab);
        },
        switchToTab(tab) {
            // Only switch if tab exists in the available tabs
            if (this.isValidTab(tab)) {
                @this.switchTab(tab);
            }
        }
    }"
    x-on:tab-changed.window="
        const tab = $event.detail.tab;
        // Update URL hash without triggering navigation
        const url = new URL(window.location);
        url.hash = tab;
        history.pushState({}, '', url);
    "
>
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-slate-800 dark:text-slate-100">{{ __('Settings') }}</h1>
        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ __('Manage your system settings') }}</p>
    </div>

    @if (session()->has('success'))
        <div class="mb-4 rounded-md bg-green-50 dark:bg-green-900/20 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ms-3">
                    <p class="text-sm font-medium text-green-800 dark:text-green-200">
                        {{ session('success') }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    <div class="erp-card p-0 overflow-hidden">
        <!-- Tabs with Icons -->
        <div class="border-b border-slate-200 dark:border-slate-700">
            <nav class="flex -mb-px overflow-x-auto scrollbar-thin scrollbar-thumb-slate-300 dark:scrollbar-thumb-slate-600">
                @foreach($tabs as $tabKey => $tabLabel)
                    <button type="button"
                        wire:click="switchTab('{{ $tabKey }}')"
                        data-tab-key="{{ $tabKey }}"
                        class="group flex items-center gap-2 px-4 py-3 text-sm font-medium whitespace-nowrap border-b-2 transition-colors
                            {{ $activeTab === $tabKey
                                ? 'border-emerald-500 text-emerald-700 dark:text-emerald-300 bg-emerald-50/60 dark:bg-emerald-900/20'
                                : 'border-transparent text-slate-600 hover:text-slate-800 hover:border-slate-300 dark:text-slate-300 dark:hover:text-slate-100 hover:bg-slate-50 dark:hover:bg-slate-800/60' }}">
                        @if(isset($tabIcons[$tabKey]))
                            <svg class="w-4 h-4 {{ $activeTab === $tabKey ? 'text-emerald-600 dark:text-emerald-300' : 'text-slate-400 group-hover:text-slate-600 dark:group-hover:text-slate-200' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $tabIcons[$tabKey] }}"/>
                            </svg>
                        @endif
                        <span>{{ __($tabLabel) }}</span>
                    </button>
                @endforeach
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
            {{-- Tab Description --}}
            @if(isset($tabDescriptions[$activeTab]))
                <div class="mb-6 p-4 bg-emerald-50/70 dark:bg-emerald-900/20 rounded-lg border border-emerald-200 dark:border-emerald-800" role="region" aria-label="{{ __('Tab information') }}">
                    <div class="flex items-start gap-3">
                        @if(isset($tabIcons[$activeTab]))
                            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-300 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $tabIcons[$activeTab] }}"/>
                            </svg>
                        @endif
                        <div>
                            <h3 class="text-sm font-semibold text-emerald-800 dark:text-emerald-200">{{ __($tabs[$activeTab]) }}</h3>
                            <p class="text-sm text-emerald-700 dark:text-emerald-300 mt-0.5">{{ __($tabDescriptions[$activeTab]) }}</p>
                        </div>
                    </div>
                </div>
            @endif
            
            @if($activeTab === 'general')
                <form wire:submit.prevent="saveGeneral">
                    <div class="space-y-6">
                        <div>
                            <label class="erp-label">
                                {{ __('Company Name') }}
                            </label>
                            <input type="text" wire:model="company_name"
                                class="mt-1 erp-input">
                            @error('company_name') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="erp-label">
                                {{ __('Company Email') }}
                            </label>
                            <input type="email" wire:model="company_email"
                                class="mt-1 erp-input">
                            @error('company_email') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="erp-label">
                                {{ __('Company Phone') }}
                            </label>
                            <input type="text" wire:model="company_phone"
                                class="mt-1 erp-input">
                            @error('company_phone') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="erp-label">
                                {{ __('Timezone') }}
                            </label>
                            <select wire:model="timezone"
                                class="mt-1 erp-input">
                                <option value="UTC">{{ __('UTC') }}</option>
                                <option value="Africa/Cairo">{{ __('Africa/Cairo') }}</option>
                                <option value="Asia/Dubai">{{ __('Asia/Dubai') }}</option>
                                <option value="Asia/Riyadh">{{ __('Asia/Riyadh') }}</option>
                                <option value="Europe/London">{{ __('Europe/London') }}</option>
                                <option value="America/New_York">{{ __('America/New_York') }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="erp-label">
                                {{ __('Date Format') }}
                            </label>
                            <select wire:model="date_format"
                                class="mt-1 erp-input">
                                <option value="Y-m-d">{{ __('YYYY-MM-DD') }}</option>
                                <option value="d/m/Y">{{ __('DD/MM/YYYY') }}</option>
                                <option value="m/d/Y">{{ __('MM/DD/YYYY') }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="erp-label">
                                {{ __('Default Currency') }}
                            </label>
                            <select wire:model="default_currency"
                                class="mt-1 erp-input">
                                <option value="">{{ __('Select Currency') }}</option>
                                @foreach($currencies as $currency)
                                    <option value="{{ $currency->code }}">
                                        {{ $currency->code }} - {{ $currency->name }} ({{ $currency->symbol }})
                                    </option>
                                @endforeach
                            </select>
                            @error('default_currency') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex justify-end">
                            <button type="submit"
                                class="erp-btn-primary">
                                {{ __('Save Changes') }}
                            </button>
                        </div>
                    </div>
                </form>

            @elseif($activeTab === 'branding')
                <form wire:submit.prevent="saveBranding">
                    <div class="space-y-6">
                        <div>
                            <label class="erp-label">{{ __('Company Tagline') }}</label>
                            <input type="text" wire:model="branding_tagline" 
                                class="mt-1 erp-input"
                                placeholder="{{ __('Your company slogan or tagline') }}">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="erp-label">{{ __('Primary Color') }}</label>
                                <div class="mt-1 flex items-center gap-2">
                                    <input type="color" wire:model="branding_primary_color" 
                                        class="h-10 w-14 rounded border-slate-300 cursor-pointer">
                                    <input type="text" wire:model="branding_primary_color" 
                                        class="erp-input flex-1"
                                        placeholder="#10b981">
                                </div>
                            </div>

                            <div>
                                <label class="erp-label">{{ __('Secondary Color') }}</label>
                                <div class="mt-1 flex items-center gap-2">
                                    <input type="color" wire:model="branding_secondary_color" 
                                        class="h-10 w-14 rounded border-slate-300 cursor-pointer">
                                    <input type="text" wire:model="branding_secondary_color" 
                                        class="erp-input flex-1"
                                        placeholder="#3b82f6">
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">{{ __('Logo') }}</label>
                                <p class="text-xs text-slate-500 mb-3">{{ __('Select your company logo from the Media Library. Recommended: max 400px width.') }}</p>
                                <livewire:components.media-picker 
                                    :value="$branding_logo_id"
                                    accept-mode="image"
                                    :max-size="2048"
                                    field-id="branding-logo"
                                    wire:key="logo-picker-{{ $branding_logo_id ?: 'empty' }}"
                                ></livewire:components.media-picker>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">{{ __('Favicon') }}</label>
                                <p class="text-xs text-slate-500 mb-3">{{ __('Select your favicon from the Media Library. Recommended: 32x32 or 64x64 pixels.') }}</p>
                                <livewire:components.media-picker 
                                    :value="$branding_favicon_id"
                                    accept-mode="image"
                                    :max-size="1024"
                                    field-id="branding-favicon"
                                    wire:key="favicon-picker-{{ $branding_favicon_id ?: 'empty' }}"
                                ></livewire:components.media-picker>
                            </div>
                        </div>

                        <div class="p-4 bg-slate-50 dark:bg-slate-900 rounded-lg">
                            <h4 class="font-medium text-slate-800 dark:text-slate-100 mb-2">{{ __('Preview') }}</h4>
                            <div class="flex items-center gap-3 p-3 rounded-lg" style="background: {{ $branding_primary_color }}20;">
                                @if($branding_logo)
                                    <img src="{{ $branding_logo }}" alt="Logo" class="h-10 object-contain">
                                @else
                                    <div class="h-10 w-10 rounded-lg flex items-center justify-center" style="background: {{ $branding_primary_color }};">
                                        <span class="text-white font-bold">{{ substr($company_name ?? 'E', 0, 1) }}</span>
                                    </div>
                                @endif
                                <div>
                                    <span class="font-semibold" style="color: {{ $branding_primary_color }};">{{ $company_name ?? 'Company Name' }}</span>
                                    @if($branding_tagline)
                                        <p class="text-xs text-slate-500">{{ $branding_tagline }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit"
                                class="px-4 py-2 text-white rounded-md hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2"
                                style="background: {{ $branding_primary_color }};">
                                {{ __('Save Changes') }}
                            </button>
                        </div>
                    </div>
                </form>

            @elseif($activeTab === 'branch')
                <form wire:submit.prevent="saveBranch">
                    <div class="space-y-6">
                        <div class="flex items-center">
                            <input type="checkbox" wire:model="multi_branch" id="multi_branch"
                                class="erp-checkbox">
                            <label for="multi_branch" class="ms-2 block text-sm text-slate-800 dark:text-slate-100">
                                {{ __('Enable Multi-Branch Mode') }}
                            </label>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" wire:model="require_branch_selection" id="require_branch"
                                class="erp-checkbox">
                            <label for="require_branch" class="ms-2 block text-sm text-slate-800 dark:text-slate-100">
                                {{ __('Require Branch Selection') }}
                            </label>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit"
                                class="erp-btn-primary">
                                {{ __('Save Changes') }}
                            </button>
                        </div>
                    </div>
                </form>

            @elseif($activeTab === 'currencies')
                <div>
                    <p class="text-slate-600 dark:text-slate-400 mb-4">{{ __('Currency management has been moved to') }}</p>
                    <a href="{{ route('admin.currencies.index') }}"
                        class="erp-btn-primary">
                        {{ __('Manage Currencies') }}
                    </a>
                </div>

            @elseif($activeTab === 'rates')
                <div>
                    <p class="text-slate-600 dark:text-slate-400 mb-4">{{ __('Exchange rate management has been moved to') }}</p>
                    <a href="{{ route('admin.currency-rates.index') }}"
                        class="erp-btn-primary">
                        {{ __('Manage Exchange Rates') }}
                    </a>
                </div>

            @elseif($activeTab === 'translations')
                <div class="space-y-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-slate-800 dark:text-slate-100">{{ __('Translation Manager') }}</h3>
                            <p class="text-slate-600 dark:text-slate-400">{{ __('Manage translations for Arabic and English languages') }}</p>
                        </div>
                        <a href="{{ route('admin.translations.index') }}" 
                           class="erp-btn-primary">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
                            </svg>
                            {{ __('Open Translation Manager') }}
                        </a>
                    </div>
                    
                    <div class="bg-slate-50 dark:bg-slate-900 rounded-lg p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <a href="{{ route('admin.translations.index') }}" class="block p-4 bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-600 hover:border-emerald-500 hover:shadow-md transition">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg">
                                        <svg class="w-6 h-6 text-emerald-700 dark:text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-medium text-slate-800 dark:text-slate-100">{{ __('View Translations') }}</p>
                                        <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Browse all translations') }}</p>
                                    </div>
                                </div>
                            </a>
                            
                            <a href="{{ route('admin.translations.create') }}" class="block p-4 bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-600 hover:border-emerald-500 hover:shadow-md transition">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-medium text-slate-800 dark:text-slate-100">{{ __('Add Translation') }}</p>
                                        <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Add new translation key') }}</p>
                                    </div>
                                </div>
                            </a>
                            
                            <div class="block p-4 bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-600">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-purple-100 dark:bg-purple-900 rounded-lg">
                                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-medium text-slate-800 dark:text-slate-100">{{ __('Supported Languages') }}</p>
                                        <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Arabic & English') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            @elseif($activeTab === 'security')
                <form wire:submit.prevent="saveSecurity">
                    <div class="space-y-6">
                        <div class="flex items-center">
                            <input type="checkbox" wire:model="require_2fa" id="require_2fa"
                                class="erp-checkbox">
                            <label for="require_2fa" class="ms-2 block text-sm text-slate-800 dark:text-slate-100">
                                {{ __('Require Two-Factor Authentication') }}
                            </label>
                        </div>

                        <div>
                            <label class="erp-label">
                                {{ __('Session Timeout (minutes)') }}
                            </label>
                            <input type="number" wire:model="session_timeout" min="5" max="1440"
                                class="mt-1 erp-input">
                            @error('session_timeout') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" wire:model="enable_audit_log" id="enable_audit"
                                class="erp-checkbox">
                            <label for="enable_audit" class="ms-2 block text-sm text-slate-800 dark:text-slate-100">
                                {{ __('Enable Audit Logging') }}
                            </label>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit"
                                class="erp-btn-primary">
                                {{ __('Save Changes') }}
                            </button>
                        </div>
                    </div>
                </form>

            @elseif($activeTab === 'backup')
                <form wire:submit.prevent="saveBackup">
                    <div class="space-y-6">
                        <div class="flex items-center">
                            <input type="checkbox" wire:model="auto_backup" id="auto_backup"
                                class="erp-checkbox">
                            <label for="auto_backup" class="ms-2 block text-sm text-slate-800 dark:text-slate-100">
                                {{ __('Enable Automatic Backups') }}
                            </label>
                        </div>

                        <div>
                            <label class="erp-label">
                                {{ __('Backup Frequency') }}
                            </label>
                            <select wire:model="backup_frequency"
                                class="mt-1 erp-input">
                                <option value="daily">{{ __('Daily') }}</option>
                                <option value="weekly">{{ __('Weekly') }}</option>
                                <option value="monthly">{{ __('Monthly') }}</option>
                            </select>
                            @error('backup_frequency') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="erp-label">
                                {{ __('Retention Period (days)') }}
                            </label>
                            <input type="number" wire:model="backup_retention_days" min="1" max="365"
                                class="mt-1 erp-input">
                            @error('backup_retention_days') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="erp-label">
                                {{ __('Backup Storage') }}
                            </label>
                            <select wire:model="backup_storage"
                                class="mt-1 erp-input">
                                <option value="local">{{ __('Local Storage') }}</option>
                                <option value="s3">{{ __('Amazon S3') }}</option>
                                <option value="ftp">{{ __('FTP Server') }}</option>
                            </select>
                            @error('backup_storage') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex justify-end">
                            <button type="submit"
                                class="erp-btn-primary">
                                {{ __('Save Changes') }}
                            </button>
                        </div>
                    </div>
                </form>

            @elseif($activeTab === 'inventory')
                <form wire:submit.prevent="saveInventory">
                    <div class="space-y-6">
                        <div>
                            <label class="erp-label">
                                {{ __('Inventory Costing Method') }}
                            </label>
                            <select wire:model="inventory_costing_method"
                                class="mt-1 erp-input">
                                <option value="FIFO">{{ __('FIFO (First In, First Out)') }}</option>
                                <option value="LIFO">{{ __('LIFO (Last In, First Out)') }}</option>
                                <option value="AVG">{{ __('Weighted Average') }}</option>
                            </select>
                            @error('inventory_costing_method') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="erp-label">
                                {{ __('Low Stock Alert Threshold') }}
                            </label>
                            <input type="number" wire:model="stock_alert_threshold" min="0"
                                class="mt-1 erp-input">
                            @error('stock_alert_threshold') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" wire:model="use_per_product_threshold" id="use_per_product"
                                class="erp-checkbox">
                            <label for="use_per_product" class="ms-2 block text-sm text-slate-800 dark:text-slate-100">
                                {{ __('Use Per-Product Stock Threshold') }}
                            </label>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit"
                                class="erp-btn-primary">
                                {{ __('Save Changes') }}
                            </button>
                        </div>
                    </div>
                </form>

            @elseif($activeTab === 'pos')
                <form wire:submit.prevent="savePos">
                    <div class="space-y-6">
                        <div class="flex items-center">
                            <input type="checkbox" wire:model="pos_allow_negative_stock" id="pos_negative"
                                class="erp-checkbox">
                            <label for="pos_negative" class="ms-2 block text-sm text-slate-800 dark:text-slate-100">
                                {{ __('Allow Negative Stock in POS') }}
                            </label>
                        </div>

                        <div>
                            <label class="erp-label">
                                {{ __('Maximum Discount Percent') }}
                            </label>
                            <input type="number" wire:model="pos_max_discount_percent" min="0" max="100"
                                class="mt-1 erp-input">
                            @error('pos_max_discount_percent') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" wire:model="pos_auto_print_receipt" id="pos_auto_print"
                                class="erp-checkbox">
                            <label for="pos_auto_print" class="ms-2 block text-sm text-slate-800 dark:text-slate-100">
                                {{ __('Auto Print Receipt After Sale') }}
                            </label>
                        </div>

                        <div>
                            <label class="erp-label">
                                {{ __('Rounding Rule') }}
                            </label>
                            <select wire:model="pos_rounding_rule"
                                class="mt-1 erp-input">
                                <option value="none">{{ __('No Rounding') }}</option>
                                <option value="0.05">{{ __('Round to 0.05') }}</option>
                                <option value="0.10">{{ __('Round to 0.10') }}</option>
                                <option value="0.25">{{ __('Round to 0.25') }}</option>
                                <option value="0.50">{{ __('Round to 0.50') }}</option>
                                <option value="1.00">{{ __('Round to 1.00') }}</option>
                            </select>
                            @error('pos_rounding_rule') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex justify-end">
                            <button type="submit"
                                class="erp-btn-primary">
                                {{ __('Save Changes') }}
                            </button>
                        </div>
                    </div>
                </form>

            @elseif($activeTab === 'accounting')
                <form wire:submit.prevent="saveAccounting">
                    <div class="space-y-6">
                        <div>
                            <label class="erp-label">
                                {{ __('Chart of Accounts Template') }}
                            </label>
                            <select wire:model="accounting_coa_template"
                                class="mt-1 erp-input">
                                <option value="standard">{{ __('Standard') }}</option>
                                <option value="retail">{{ __('Retail Business') }}</option>
                                <option value="service">{{ __('Service Business') }}</option>
                            </select>
                            @error('accounting_coa_template') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex justify-end">
                            <button type="submit"
                                class="erp-btn-primary">
                                {{ __('Save Changes') }}
                            </button>
                        </div>
                    </div>
                </form>

            @elseif($activeTab === 'warehouse')
                <div class="space-y-4">
                    <p class="text-slate-600 dark:text-slate-400">{{ __('Warehouse management settings') }}</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <a href="{{ route('app.warehouse.index') }}"
                            class="flex items-center p-4 bg-slate-50 dark:bg-slate-900 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800/60 transition">
                            <div class="flex-shrink-0 w-10 h-10 flex items-center justify-center bg-emerald-100 dark:bg-emerald-900/30 rounded-lg">
                                <svg class="w-6 h-6 text-emerald-700 dark:text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </div>
                            <div class="ms-4">
                                <p class="text-sm font-medium text-slate-800 dark:text-slate-100">{{ __('Manage Warehouses') }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('View and manage warehouse locations') }}</p>
                            </div>
                        </a>
                        <a href="{{ route('app.warehouse.transfers.index') }}"
                            class="flex items-center p-4 bg-slate-50 dark:bg-slate-900 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800/60 transition">
                            <div class="flex-shrink-0 w-10 h-10 flex items-center justify-center bg-green-100 dark:bg-green-900 rounded-lg">
                                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                </svg>
                            </div>
                            <div class="ms-4">
                                <p class="text-sm font-medium text-slate-800 dark:text-slate-100">{{ __('Stock Transfers') }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Transfer stock between warehouses') }}</p>
                            </div>
                        </a>
                    </div>
                </div>

            @elseif($activeTab === 'manufacturing')
                <div class="space-y-4">
                    <p class="text-slate-600 dark:text-slate-400">{{ __('Manufacturing module settings') }}</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <a href="{{ route('app.manufacturing.boms.index') }}"
                            class="flex items-center p-4 bg-slate-50 dark:bg-slate-900 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800/60 transition">
                            <div class="flex-shrink-0 w-10 h-10 flex items-center justify-center bg-purple-100 dark:bg-purple-900 rounded-lg">
                                <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                            </div>
                            <div class="ms-4">
                                <p class="text-sm font-medium text-slate-800 dark:text-slate-100">{{ __('Bills of Materials') }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Manage product BOMs') }}</p>
                            </div>
                        </a>
                        <a href="{{ route('app.manufacturing.work-centers.index') }}"
                            class="flex items-center p-4 bg-slate-50 dark:bg-slate-900 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800/60 transition">
                            <div class="flex-shrink-0 w-10 h-10 flex items-center justify-center bg-orange-100 dark:bg-orange-900 rounded-lg">
                                <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <div class="ms-4">
                                <p class="text-sm font-medium text-slate-800 dark:text-slate-100">{{ __('Work Centers') }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Configure manufacturing work centers') }}</p>
                            </div>
                        </a>
                    </div>
                </div>

            @elseif($activeTab === 'hrm')
                <form wire:submit.prevent="saveHrm">
                    <div class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="erp-label">
                                    {{ __('Working Days Per Week') }}
                                </label>
                                <input type="number" wire:model="hrm_working_days_per_week" min="1" max="7"
                                    class="mt-1 erp-input">
                                @error('hrm_working_days_per_week') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="erp-label">
                                    {{ __('Working Hours Per Day') }}
                                </label>
                                <input type="number" wire:model="hrm_working_hours_per_day" min="1" max="24" step="0.5"
                                    class="mt-1 erp-input">
                                @error('hrm_working_hours_per_day') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="erp-label">
                                {{ __('Late Arrival Threshold (minutes)') }}
                            </label>
                            <input type="number" wire:model="hrm_late_arrival_threshold" min="0"
                                class="mt-1 erp-input">
                            @error('hrm_late_arrival_threshold') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <h4 class="font-medium text-slate-800 dark:text-slate-100 border-b pb-2">{{ __('Allowances') }}</h4>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="erp-label">
                                    {{ __('Transport Allowance Type') }}
                                </label>
                                <select wire:model="hrm_transport_allowance_type"
                                    class="mt-1 erp-input">
                                    <option value="percentage">{{ __('Percentage') }}</option>
                                    <option value="fixed">{{ __('Fixed Amount') }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="erp-label">
                                    {{ __('Transport Allowance Value') }}
                                </label>
                                <input type="number" wire:model="hrm_transport_allowance_value" min="0" step="0.01"
                                    class="mt-1 erp-input">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="erp-label">
                                    {{ __('Housing Allowance Type') }}
                                </label>
                                <select wire:model="hrm_housing_allowance_type"
                                    class="mt-1 erp-input">
                                    <option value="percentage">{{ __('Percentage') }}</option>
                                    <option value="fixed">{{ __('Fixed Amount') }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="erp-label">
                                    {{ __('Housing Allowance Value') }}
                                </label>
                                <input type="number" wire:model="hrm_housing_allowance_value" min="0" step="0.01"
                                    class="mt-1 erp-input">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="erp-label">
                                    {{ __('Meal Allowance (Fixed)') }}
                                </label>
                                <input type="number" wire:model="hrm_meal_allowance" min="0" step="0.01"
                                    class="mt-1 erp-input">
                            </div>
                            <div>
                                <label class="erp-label">
                                    {{ __('Health Insurance Deduction') }}
                                </label>
                                <input type="number" wire:model="hrm_health_insurance_deduction" min="0" step="0.01"
                                    class="mt-1 erp-input">
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit"
                                class="erp-btn-primary">
                                {{ __('Save Changes') }}
                            </button>
                        </div>
                    </div>
                </form>

            @elseif($activeTab === 'rental')
                <form wire:submit.prevent="saveRental">
                    <div class="space-y-6">
                        <div>
                            <label class="erp-label">
                                {{ __('Grace Period (days)') }}
                            </label>
                            <input type="number" wire:model="rental_grace_period_days" min="0"
                                class="mt-1 erp-input">
                            <p class="mt-1 text-xs text-slate-500">{{ __('Number of days before late payment penalty applies') }}</p>
                            @error('rental_grace_period_days') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="erp-label">
                                    {{ __('Late Payment Penalty Type') }}
                                </label>
                                <select wire:model="rental_penalty_type"
                                    class="mt-1 erp-input">
                                    <option value="percentage">{{ __('Percentage') }}</option>
                                    <option value="fixed">{{ __('Fixed Amount') }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="erp-label">
                                    {{ __('Penalty Value') }}
                                </label>
                                <input type="number" wire:model="rental_penalty_value" min="0" step="0.01"
                                    class="mt-1 erp-input">
                                @error('rental_penalty_value') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit"
                                class="erp-btn-primary">
                                {{ __('Save Changes') }}
                            </button>
                        </div>
                    </div>
                </form>

            @elseif($activeTab === 'fixed_assets')
                <div class="space-y-4">
                    <p class="text-slate-600 dark:text-slate-400">{{ __('Fixed assets module settings') }}</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <a href="{{ route('app.fixed-assets.index') }}"
                            class="flex items-center p-4 bg-slate-50 dark:bg-slate-900 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800/60 transition">
                            <div class="flex-shrink-0 w-10 h-10 flex items-center justify-center bg-indigo-100 dark:bg-indigo-900 rounded-lg">
                                <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div class="ms-4">
                                <p class="text-sm font-medium text-slate-800 dark:text-slate-100">{{ __('Manage Fixed Assets') }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('View and manage company assets') }}</p>
                            </div>
                        </a>
                        <a href="{{ route('app.fixed-assets.depreciation') }}"
                            class="flex items-center p-4 bg-slate-50 dark:bg-slate-900 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800/60 transition">
                            <div class="flex-shrink-0 w-10 h-10 flex items-center justify-center bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                                <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/>
                                </svg>
                            </div>
                            <div class="ms-4">
                                <p class="text-sm font-medium text-slate-800 dark:text-slate-100">{{ __('Depreciation') }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('View asset depreciation') }}</p>
                            </div>
                        </a>
                    </div>
                </div>

            @elseif($activeTab === 'sales')
                <form wire:submit.prevent="saveSales">
                    <div class="space-y-6">
                        <div>
                            <label class="erp-label">
                                {{ __('Default Payment Terms (days)') }}
                            </label>
                            <input type="number" wire:model="sales_payment_terms_days" min="0"
                                class="mt-1 erp-input">
                            @error('sales_payment_terms_days') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="erp-label">
                                {{ __('Invoice Prefix') }}
                            </label>
                            <input type="text" wire:model="sales_invoice_prefix" maxlength="10"
                                class="mt-1 erp-input">
                            @error('sales_invoice_prefix') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="erp-label">
                                {{ __('Invoice Starting Number') }}
                            </label>
                            <input type="number" wire:model="sales_invoice_starting_number" min="1"
                                class="mt-1 erp-input">
                            @error('sales_invoice_starting_number') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex justify-end">
                            <button type="submit"
                                class="erp-btn-primary">
                                {{ __('Save Changes') }}
                            </button>
                        </div>
                    </div>
                </form>

            @elseif($activeTab === 'purchases')
                <div class="space-y-4">
                    <p class="text-slate-600 dark:text-slate-400">{{ __('Purchases module settings') }}</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <a href="{{ route('app.purchases.index') }}"
                            class="flex items-center p-4 bg-slate-50 dark:bg-slate-900 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800/60 transition">
                            <div class="flex-shrink-0 w-10 h-10 flex items-center justify-center bg-emerald-100 dark:bg-emerald-900 rounded-lg">
                                <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                            </div>
                            <div class="ms-4">
                                <p class="text-sm font-medium text-slate-800 dark:text-slate-100">{{ __('Manage Purchases') }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('View and manage purchase orders') }}</p>
                            </div>
                        </a>
                        <a href="{{ route('suppliers.index') }}"
                            class="flex items-center p-4 bg-slate-50 dark:bg-slate-900 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800/60 transition">
                            <div class="flex-shrink-0 w-10 h-10 flex items-center justify-center bg-cyan-100 dark:bg-cyan-900 rounded-lg">
                                <svg class="w-6 h-6 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </div>
                            <div class="ms-4">
                                <p class="text-sm font-medium text-slate-800 dark:text-slate-100">{{ __('Manage Suppliers') }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('View and manage suppliers') }}</p>
                            </div>
                        </a>
                    </div>
                </div>

            @elseif($activeTab === 'integrations')
                <div class="space-y-4">
                    <p class="text-slate-600 dark:text-slate-400">{{ __('Integration and API settings') }}</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <a href="{{ route('admin.stores.index') }}"
                            class="flex items-center p-4 bg-slate-50 dark:bg-slate-900 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800/60 transition">
                            <div class="flex-shrink-0 w-10 h-10 flex items-center justify-center bg-pink-100 dark:bg-pink-900 rounded-lg">
                                <svg class="w-6 h-6 text-pink-600 dark:text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                            </div>
                            <div class="ms-4">
                                <p class="text-sm font-medium text-slate-800 dark:text-slate-100">{{ __('Store Integrations') }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Shopify, WooCommerce, and more') }}</p>
                            </div>
                        </a>
                    </div>
                </div>

            @elseif($activeTab === 'notifications')
                <form wire:submit.prevent="saveNotifications">
                    <div class="space-y-6">
                        <div class="flex items-center">
                            <input type="checkbox" wire:model="notifications_low_stock" id="notify_low_stock"
                                class="erp-checkbox">
                            <label for="notify_low_stock" class="ms-2 block text-sm text-slate-800 dark:text-slate-100">
                                {{ __('Low Stock Notifications') }}
                            </label>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" wire:model="notifications_payment_due" id="notify_payment_due"
                                class="erp-checkbox">
                            <label for="notify_payment_due" class="ms-2 block text-sm text-slate-800 dark:text-slate-100">
                                {{ __('Payment Due Notifications') }}
                            </label>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" wire:model="notifications_new_order" id="notify_new_order"
                                class="erp-checkbox">
                            <label for="notify_new_order" class="ms-2 block text-sm text-slate-800 dark:text-slate-100">
                                {{ __('New Order Notifications') }}
                            </label>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit"
                                class="erp-btn-primary">
                                {{ __('Save Changes') }}
                            </button>
                        </div>
                    </div>
                </form>

            @elseif($activeTab === 'advanced')
                <form wire:submit.prevent="saveAdvanced">
                    <div class="space-y-6">
                        <div class="flex items-center">
                            <input type="checkbox" wire:model="enable_api" id="enable_api"
                                class="erp-checkbox">
                            <label for="enable_api" class="ms-2 block text-sm text-slate-800 dark:text-slate-100">
                                {{ __('Enable API Access') }}
                            </label>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" wire:model="enable_webhooks" id="enable_webhooks"
                                class="erp-checkbox">
                            <label for="enable_webhooks" class="ms-2 block text-sm text-slate-800 dark:text-slate-100">
                                {{ __('Enable Webhooks') }}
                            </label>
                        </div>

                        <div>
                            <label class="erp-label">
                                {{ __('Cache TTL (seconds)') }}
                            </label>
                            <input type="number" wire:model="cache_ttl" min="60" max="86400"
                                class="mt-1 erp-input">
                            @error('cache_ttl') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex justify-end">
                            <button type="submit"
                                class="erp-btn-primary">
                                {{ __('Save Changes') }}
                            </button>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>
