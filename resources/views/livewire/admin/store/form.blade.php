<div class="space-y-4">
    <div class="flex items-center justify-between gap-2">
        <div>
            <h1 class="text-lg font-semibold text-slate-800 dark:text-slate-100">
                {{ $storeId ? __('Edit Store') : __('Add Store') }}
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                {{ __('Configure store integration settings.') }}
            </p>
        </div>
    </div>

    @if(session()->has('success'))
        <div class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-lg flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('success') }}
        </div>
    @endif

    @if(session()->has('error'))
        <div class="p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('error') }}
        </div>
    @endif

    <form wire:submit.prevent="save" class="space-y-6 max-w-3xl">
        <div class="erp-card p-6 space-y-4">
            <h3 class="text-base font-medium text-slate-800 dark:text-slate-200 border-b pb-2">{{ __('Store Details') }}</h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Name') }} <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="name" class="erp-input w-full" required>
                    @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Type') }} <span class="text-red-500">*</span></label>
                    <select wire:model.live="type" class="erp-input w-full" required>
                        @if(is_array($storeTypes))
                            @foreach($storeTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        @endif
                    </select>
                    @error('type') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('URL') }} <span class="text-red-500">*</span></label>
                <input type="url" wire:model="url" class="erp-input w-full" placeholder="https://your-store.myshopify.com" required>
                @error('url') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Branch') }}</label>
                    <select wire:model.live="branch_id" class="erp-input w-full">
                        <option value="">{{ __('All Branches') }}</option>
                        @if(is_array($branches))
                            @foreach($branches as $branch)
                                <option value="{{ $branch['id'] ?? '' }}">{{ $branch['name'] ?? '' }}</option>
                            @endforeach
                        @endif
                    </select>
                    @error('branch_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div class="flex items-end">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model="is_active" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        <span class="text-sm text-slate-700 dark:text-slate-300">{{ __('Active') }}</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="erp-card p-6 space-y-4">
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="text-base font-medium text-slate-800 dark:text-slate-200 border-b pb-2">{{ __('API Credentials') }}</h3>
                </div>
            </div>
            
            {{-- Help text for API credentials --}}
            <div class="p-4 bg-blue-50 dark:bg-blue-900/30 rounded-xl border border-blue-200 dark:border-blue-700">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="text-sm text-blue-800 dark:text-blue-200">
                        <p class="font-medium mb-1">{{ __('Where to find these credentials?') }}</p>
                        <ul class="list-disc list-inside text-xs space-y-1 text-blue-700 dark:text-blue-300">
                            <li><strong>Shopify:</strong> {{ __('Admin > Settings > Apps > Develop apps > Create app') }}</li>
                            <li><strong>WooCommerce:</strong> {{ __('WordPress Admin > WooCommerce > Settings > Advanced > REST API') }}</li>
                            <li><strong>Salla:</strong> {{ __('Dashboard > Developer > Apps > Create new app') }}</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('API Key') }}</label>
                    <input type="text" wire:model="api_key" class="erp-input w-full" placeholder="{{ __('Paste your API key here') }}">
                    @error('api_key') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('API Secret') }}</label>
                    <input type="password" wire:model="api_secret" class="erp-input w-full" placeholder="{{ __('Paste your API secret here') }}">
                    @error('api_secret') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Access Token') }}</label>
                <input type="password" wire:model="access_token" class="erp-input w-full" placeholder="{{ __('Paste your access token here') }}">
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ __('Some platforms provide a single access token instead of key/secret pair') }}</p>
                @error('access_token') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Webhook Secret') }}</label>
                <input type="text" wire:model="webhook_secret" class="erp-input w-full" placeholder="{{ __('Optional - for webhook verification') }}">
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ __('Used to verify incoming webhook requests from the store') }}</p>
                @error('webhook_secret') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="erp-card p-6 space-y-4">
            <h3 class="text-base font-medium text-slate-800 dark:text-slate-200 border-b pb-2">{{ __('Sync Settings') }}</h3>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" wire:model="sync_settings.sync_products" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <span class="text-sm text-slate-700 dark:text-slate-300">{{ __('Sync Products') }}</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" wire:model="sync_settings.sync_inventory" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <span class="text-sm text-slate-700 dark:text-slate-300">{{ __('Sync Inventory') }}</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" wire:model="sync_settings.sync_orders" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <span class="text-sm text-slate-700 dark:text-slate-300">{{ __('Sync Orders') }}</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" wire:model="sync_settings.sync_customers" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <span class="text-sm text-slate-700 dark:text-slate-300">{{ __('Sync Customers') }}</span>
                </label>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" wire:model="sync_settings.auto_sync" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <span class="text-sm text-slate-700 dark:text-slate-300">{{ __('Auto Sync') }}</span>
                </label>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Sync Interval (minutes)') }}</label>
                    <input type="number" wire:model="sync_settings.sync_interval" class="erp-input w-full" min="5" max="1440">
                </div>
            </div>

            @if($modules && (is_array($modules) || (is_object($modules) && method_exists($modules, 'isNotEmpty') && $modules->isNotEmpty())))
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">{{ __('Sync Modules') }}</label>
                <div class="flex flex-wrap gap-2">
                    @foreach($modules as $module)
                        @php
                            $moduleId = is_object($module) ? $module->id : ($module['id'] ?? null);
                            $moduleName = is_object($module) ? $module->name : ($module['name'] ?? '');
                        @endphp
                        @if($moduleId && $moduleName)
                            <label class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 dark:bg-slate-700 rounded-lg cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-600 transition {{ in_array($moduleId, $sync_settings['sync_modules'] ?? []) ? 'ring-2 ring-emerald-500' : '' }}">
                                <input type="checkbox" wire:model="sync_settings.sync_modules" value="{{ $moduleId }}" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                <span class="text-sm text-slate-700 dark:text-slate-300">{{ $moduleName }}</span>
                            </label>
                        @endif
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.stores.index') }}"
               class="inline-flex items-center rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-2 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm hover:bg-slate-50 dark:hover:bg-slate-800">
                {{ __('Cancel') }}
            </a>
            <button type="submit" class="erp-btn erp-btn-primary">
                {{ $storeId ? __('Update') : __('Create') }}
            </button>
        </div>
    </form>
</div>
