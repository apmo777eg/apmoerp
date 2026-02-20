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
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Branch') }} <span class="text-red-500">*</span></label>
                    <select wire:model.live="branch_id" class="erp-input w-full" required>
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

    {{-- ERP Store API Tokens (for connecting external stores to this ERP) --}}
    <div class="max-w-3xl">
        <div class="erp-card p-6 space-y-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-base font-medium text-slate-800 dark:text-slate-200">{{ __('ERP Store API') }}</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        {{ __('Generate API tokens for this store to access the ERP Store API endpoints (v1/store).') }}
                        <a class="text-emerald-700 hover:underline" href="{{ route('admin.api-docs') }}">{{ __('View API docs') }}</a>
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        wire:click="openTokenModal"
                        class="erp-btn erp-btn-secondary"
                        @disabled(! $storeId)
                    >
                        {{ __('Generate API Token') }}
                    </button>
                </div>
            </div>

            @if(! $storeId)
                <div class="p-4 bg-amber-50 border border-amber-200 text-amber-800 rounded-xl">
                    {{ __('Save the store first to generate API tokens.') }}
                </div>
            @endif

            @if($storeId)
                @if(! empty($tokens))
                    <div class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                            <thead class="bg-slate-50 dark:bg-slate-900">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300">{{ __('Name') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300">{{ __('Abilities') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300">{{ __('Expires') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300">{{ __('Last used') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 dark:text-slate-300">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
                                @foreach($tokens as $t)
                                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                                        <td class="px-4 py-3 text-sm text-slate-900 dark:text-slate-100">
                                            <div class="flex items-center gap-2">
                                                <span class="font-medium">{{ $t['name'] ?? '' }}</span>
                                                @if(($t['is_expired'] ?? false) === true)
                                                    <span class="text-xs px-2 py-0.5 rounded-full bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-200">{{ __('Expired') }}</span>
                                                @endif
                                            </div>
                                            <div class="text-xs text-slate-500 dark:text-slate-400">{{ __('Created') }}: {{ $t['created_at'] ?? '-' }}</div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex flex-wrap gap-1">
                                                @php $abilities = $t['abilities'] ?? []; @endphp
                                                @if(empty($abilities))
                                                    <span class="text-xs text-slate-400">-</span>
                                                @else
                                                    @foreach($abilities as $ab)
                                                        <span class="text-xs px-2 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200">{{ $ab }}</span>
                                                    @endforeach
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                                            {{ $t['expires_at'] ?? '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                                            {{ $t['last_used_at'] ?? '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <button
                                                type="button"
                                                wire:click="revokeToken({{ $t['id'] }})"
                                                wire:confirm="{{ __('Are you sure you want to revoke this token? This cannot be undone.') }}"
                                                class="text-sm font-semibold text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                            >
                                                {{ __('Revoke') }}
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-4 bg-slate-50 dark:bg-slate-900/40 rounded-xl text-sm text-slate-600 dark:text-slate-300">
                        {{ __('No tokens yet. Generate one to start connecting this store to the ERP API.') }}
                    </div>
                @endif
            @endif
        </div>
    </div>

    {{-- Token Modal (popup-gallery style, scroll-safe) --}}
    @if($showTokenModal)
        <div class="fixed inset-0 z-modal-backdrop bg-black/40 backdrop-blur-sm" aria-hidden="true" wire:click="closeTokenModal"></div>
        <div class="fixed inset-0 z-modal overflow-y-auto p-4 sm:p-6" role="dialog" aria-modal="true">
            <div class="min-h-full flex items-end sm:items-center justify-center">
                <div class="w-full max-w-2xl">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-emerald-200 dark:border-emerald-900 overflow-hidden max-h-[90vh] flex flex-col">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-emerald-50/40 dark:bg-gray-900/40 flex items-center justify-between sticky top-0">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Generate API Token') }}</h3>
                            <button type="button" wire:click="closeTokenModal" class="text-gray-500 hover:text-gray-700 dark:text-gray-300 dark:hover:text-white p-2 -m-2 rounded-lg">
                                <span class="sr-only">{{ __('Close') }}</span>
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="p-6 overflow-y-auto flex-1 space-y-4">
                            @if($generated_token)
                                <div class="p-4 rounded-xl border border-emerald-200 bg-emerald-50 dark:bg-emerald-900/30">
                                    <p class="text-sm font-medium text-emerald-900 dark:text-emerald-200">{{ __('Token generated successfully') }}</p>
                                    <p class="text-xs text-emerald-800 dark:text-emerald-300 mt-1">{{ __('Copy it now. For security, it will not be shown again.') }}</p>
                                    <div class="mt-3 flex items-center gap-2">
                                        <input
                                            type="text"
                                            readonly
                                            value="{{ $generated_token }}"
                                            class="erp-input w-full font-mono text-xs"
                                            onclick="this.select()"
                                        />
                                        <button
                                            type="button"
                                            class="erp-btn erp-btn-secondary"
                                            onclick="navigator.clipboard.writeText(@js($generated_token)); window.erpShowNotification('success', @js(__('Copied!')));"
                                        >
                                            {{ __('Copy') }}
                                        </button>
                                    </div>
                                </div>
                            @endif

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Token name') }}</label>
                                <input type="text" wire:model="token_name" class="mt-1 erp-input w-full" placeholder="{{ __('e.g. Shopify Connector') }}">
                                @error('token_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Abilities') }}</label>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                                    {{ __('Choose what this token can access. If you select Full access, other abilities are ignored.') }}
                                </p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    @foreach($tokenAbilityOptions as $key => $label)
                                        <label class="flex items-center gap-2 p-2 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-900 cursor-pointer">
                                            <input type="checkbox" wire:model="token_abilities" value="{{ $key }}" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                @error('token_abilities') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Expires at') }}</label>
                                <input type="date" wire:model="token_expires_at" class="mt-1 erp-input">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Optional. Leave empty for no expiry.') }}</p>
                                @error('token_expires_at') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 flex items-center justify-end gap-3 sticky bottom-0">
                            <button type="button" wire:click="closeTokenModal" class="erp-btn-secondary" wire:loading.attr="disabled" wire:target="createToken">
                                {{ __('Close') }}
                            </button>
                            <button type="button" wire:click="createToken" class="erp-btn-primary" wire:loading.attr="disabled" wire:target="createToken">
                                <span wire:loading.remove wire:target="createToken">{{ __('Generate') }}</span>
                                <span wire:loading wire:target="createToken" class="inline-flex items-center gap-2">
                                    <x-loading-indicator target="createToken" size="sm" />
                                    {{ __('Generating...') }}
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
