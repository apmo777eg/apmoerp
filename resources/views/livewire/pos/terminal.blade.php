{{-- resources/views/livewire/pos/terminal.blade.php --}}
<div
    x-data="erpPosTerminal({ 
        branchId: {{ $branchId }},
        baseCurrency: '{{ $baseCurrency }}',
        currencyRates: @json($currencyRates),
        currencySymbols: @json($currencySymbols)
    })"
    x-init="init()"
    class="space-y-4"
>
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
        <div>
            <h1 class="text-lg font-semibold text-slate-800">
                {{ __('POS Terminal') }}
            </h1>
            <p class="text-sm text-slate-500">
                {{ __('Fast selling screen connected to the API (products search + checkout).') }}
            </p>
            <p class="mt-1 text-xs text-emerald-600">
                {{ __('Branch:') }} {{ $branchName }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button"
                    x-on:click="openSessionModal()"
                    class="erp-btn-secondary text-xs">
                <span x-show="!currentSession">{{ __('Open Session') }}</span>
                <span x-show="currentSession">{{ __('Close Session') }}</span>
            </button>
            <div class="w-full sm:w-72">
                <div class="relative">
                    <input type="search"
                           x-model.debounce.400ms="search"
                           x-on:input.debounce.400ms="fetchProducts()"
                           placeholder="{{ __('Search products or scan barcode...') }}"
                           class="erp-input rounded-full pr-9">
                    <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400 text-sm">
                        <span x-show="!isSearching">🔎</span>
                        <span x-show="isSearching" class="h-4 w-4 animate-spin rounded-full border-2 border-emerald-200 border-t-emerald-500"></span>
                    </span>
                </div>
            </div>
        </div>
    </div>


    <div class="flex flex-wrap items-center justify-end gap-2 text-[11px] text-slate-600">
        <div class="inline-flex items-center gap-1 rounded-full border border-blue-200 bg-blue-50 px-2 py-0.5" title="{{ __('Display currency for reference only. All payments processed in base currency.') }}">
            <span class="text-blue-600">{{ __('View in') }}:</span>
            <select x-model="displayCurrency" class="bg-transparent border-0 text-xs text-blue-700 font-medium focus:ring-0 py-0 pr-5 cursor-pointer">
                @foreach($currencies as $currency)
                    <option value="{{ $currency->code }}">{{ $currency->code }}@if($currency->is_base) ({{ __('Base') }})@endif</option>
                @endforeach
            </select>
        </div>
        <div class="inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5"
             x-show="offlineQueue && offlineQueue.length">
            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
            <span x-text="offlineQueue.length + ' {{ __('pending offline orders') }}'"></span>
        </div>
        <button type="button"
                x-on:click="syncOfflineQueue && syncOfflineQueue()"
                class="inline-flex items-center gap-1 rounded-full border border-sky-200 bg-sky-50 px-2 py-0.5 hover:bg-sky-100">
            <span class="h-2 w-2 rounded-full bg-sky-400"></span>
            <span>{{ __('Sync now') }}</span>
        </button>
        <span class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5"
              :class="offline ? 'border-amber-200 bg-amber-50 text-amber-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'">
            <span class="h-2 w-2 rounded-full"
                  :class="offline ? 'bg-amber-500' : 'bg-emerald-500'"></span>
            <span x-text="offline ? '{{ __('Offline mode') }}' : '{{ __('Online') }}'"></span>
        </span>
    </div>


    <template x-if="message">
        <div class="rounded-2xl border px-3 py-2 text-xs"
             :class="{
                'border-emerald-200 bg-emerald-50 text-emerald-800': message.type === 'success',
                'border-red-200 bg-red-50 text-red-700': message.type === 'error',
                'border-slate-200 bg-slate-50 text-slate-700': message.type === 'info'
             }"
        >
            <div class="flex items-center justify-between gap-2">
                <p x-text="message.text"></p>
                <button type="button"
                        class="text-[0.7rem] font-semibold opacity-70 hover:opacity-100"
                        x-on:click="clearMessage()">
                    {{ __('Close') }}
                </button>
            </div>
        </div>
    </template>

    <div class="grid gap-4 lg:grid-cols-3">
        {{-- Products list --}}
        <div class="lg:col-span-2 space-y-3">
            <div class="rounded-2xl border border-slate-200 bg-white/80 p-3 text-xs shadow-sm shadow-emerald-500/10">
                <div class="flex items-center justify-between gap-2 mb-2">
                    <h2 class="text-sm font-semibold text-slate-800">
                        {{ __('Products') }}
                    </h2>
                    <p class="text-[0.7rem] text-slate-500" x-show="offline">
                        {{ __('Offline mode: product search requires internet.') }}
                    </p>
                </div>

                <template x-if="!products.length && search.length < 2">
                    <p class="text-xs text-slate-500">
                        {{ __('Start typing at least 2 characters to search for products.') }}
                    </p>
                </template>

                <template x-if="isSearching && search.length >= 2">
                    <div class="flex items-center gap-2 text-xs text-slate-500">
                        <span class="h-4 w-4 animate-spin rounded-full border-2 border-emerald-200 border-t-emerald-500"></span>
                        <span>{{ __('Loading products...') }}</span>
                    </div>
                </template>

                <template x-if="!products.length && search.length >= 2 && !isSearching">
                    <p class="text-xs text-slate-500">
                        {{ __('No products found for this search.') }}
                    </p>
                </template>

                <div class="mt-2 grid grid-cols-1 gap-2 md:grid-cols-2" x-show="products.length">
                    <template x-for="product in products" :key="product.id ?? product.product_id">
                        <button type="button"
                                x-on:click="addProduct(product)"
                                class="flex flex-col items-start rounded-2xl border border-emerald-100 bg-gradient-to-br from-emerald-50 via-white to-emerald-50 px-3 py-2 text-start text-xs text-slate-800 shadow-sm shadow-emerald-500/30 hover:border-emerald-300 hover:shadow-md">
                            <span class="font-semibold truncate" x-text="product.name ?? product.label ?? 'Item'"></span>
                            <span class="mt-0.5 text-[0.7rem] text-slate-500 truncate" x-text="product.sku ?? product.code ?? ''"></span>
                            <span class="mt-1 text-[0.75rem] font-semibold text-emerald-700" x-text="(product.price ?? product.sale_price ?? 0).toFixed(2) + ' ' + (product.price_currency ?? 'EGP')"></span>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        {{-- Cart --}}
        <div class="space-y-3">
            <div class="rounded-2xl border border-slate-200 bg-white/80 p-3 text-xs shadow-sm shadow-emerald-500/10">
                <div class="flex items-center justify-between gap-2 mb-1">
                    <h2 class="text-sm font-semibold text-slate-800">
                        {{ __('Cart') }}
                    </h2>
                    <p class="text-[0.7rem] text-slate-500" x-show="offline">
                        {{ __('Offline: checkout will be queued locally.') }}
                    </p>
                </div>

                <template x-if="!cart.length">
                    <p class="text-xs text-slate-500">
                        {{ __('No items yet. Choose products from the list.') }}
                    </p>
                </template>

                <div class="space-y-2 max-h-64 overflow-y-auto" x-show="cart.length">
                    <template x-for="(item, index) in cart" :key="index">
                        <div class="flex items-start justify-between gap-2 rounded-xl bg-slate-50 px-2 py-1.5">
                            <div class="flex-1">
                                <p class="text-xs font-semibold text-slate-800 truncate" x-text="item.name"></p>
                                <p class="text-[0.7rem] text-slate-500 truncate" x-text="'#' + item.product_id"></p>
                                <div class="mt-1 flex items-center gap-1 text-[0.7rem] text-slate-600">
                                    <span>{{ __('Qty') }}</span>
                                    <input type="number" min="1" step="1"
                                           x-model.number="item.qty"
                                           x-on:change="updateQty(index, item.qty)"
                                           class="h-7 w-14 rounded-lg border border-slate-200 bg-white px-1 text-[0.7rem]">
                                    <span class="ml-1">{{ __('Price') }}</span>
                                    <input type="number" min="0" step="0.01"
                                           x-model.number="item.price"
                                           x-on:change="updatePrice(index, item.price)"
                                           class="h-7 w-20 rounded-lg border border-slate-200 bg-white px-1 text-[0.7rem]">
                                </div>
                                {{-- Discount per item --}}
                                <div class="mt-1 flex items-center gap-1 text-[0.7rem] text-slate-600">
                                    <span>{{ __('Discount') }}</span>
                                    <input type="number" min="0" max="100" step="1"
                                           x-model.number="item.discount"
                                           class="h-7 w-14 rounded-lg border border-slate-200 bg-white px-1 text-[0.7rem]">
                                    <span>%</span>
                                </div>
                            </div>
                            <div class="flex flex-col items-end gap-1">
                                <button type="button"
                                        x-on:click="removeItem(index)"
                                        class="inline-flex items-center rounded-full bg-red-50 px-2 py-0.5 text-[0.65rem] font-semibold text-red-700 hover:bg-red-100">
                                    {{ __('Remove') }}
                                </button>
                                <p class="text-[0.75rem] font-semibold text-slate-800"
                                   x-text="calculateItemTotal(item).toFixed(2) + ' ' + (item.price_currency ?? 'EGP')"></p>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Totals --}}
                <div class="mt-3 border-t border-slate-200 pt-2 space-y-1 text-xs">
                    <div class="flex justify-between">
                        <span class="text-slate-600">{{ __('Subtotal') }}</span>
                        <span x-text="subtotal.toFixed(2) + ' EGP'"></span>
                    </div>
                    <div class="flex justify-between text-amber-600" x-show="discountTotal > 0">
                        <span>{{ __('Discount') }}</span>
                        <span x-text="'-' + discountTotal.toFixed(2) + ' EGP'"></span>
                    </div>
                    <div class="flex justify-between font-semibold text-emerald-700 text-base pt-1 border-t">
                        <span>{{ __('Total') }}</span>
                        <span x-text="total.toFixed(2) + ' EGP'"></span>
                    </div>
                    <div class="flex justify-between text-xs text-blue-500 bg-blue-50 rounded px-1 py-0.5 mt-1" x-show="displayCurrency !== 'EGP'">
                        <span>≈ {{ __('Approx.') }}</span>
                        <span x-text="formatCurrency(total)"></span>
                    </div>
                </div>

                <div class="mt-3 flex flex-col gap-2">
                    <button type="button"
                            x-on:click="openPaymentModal()"
                            x-bind:disabled="isCheckingOut || !cart.length"
                            class="erp-btn-primary w-full justify-center disabled:opacity-60 disabled:cursor-not-allowed">
                        <span x-show="!isCheckingOut">
                            {{ __('Checkout') }}
                        </span>
                        <span x-show="isCheckingOut" class="inline-flex items-center gap-2">
                            <span class="h-4 w-4 animate-spin rounded-full border-2 border-emerald-200 border-t-emerald-500"></span>
                            {{ __('Processing...') }}
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Payment Modal --}}
    <div x-show="showPaymentModal" 
         x-cloak
         class="z-modal fixed inset-0 flex items-center justify-center bg-black/50"
         x-transition>
        <div class="w-full max-w-lg mx-4 bg-white rounded-2xl shadow-2xl p-6" x-on:click.outside="showPaymentModal = false">
            <h3 class="text-lg font-semibold text-slate-800 mb-4">{{ __('Payment') }}</h3>
            
            <div class="mb-4 p-3 bg-emerald-50 rounded-xl">
                <div class="flex justify-between text-sm">
                    <span class="text-slate-600">{{ __('Total Amount') }}</span>
                    <span class="font-bold text-emerald-700 text-lg" x-text="total.toFixed(2) + ' EGP'"></span>
                </div>
                <div class="flex justify-between text-sm mt-2" x-show="totalPaid > 0">
                    <span class="text-slate-600">{{ __('Paid') }}</span>
                    <span class="font-semibold text-blue-600" x-text="totalPaid.toFixed(2) + ' EGP'"></span>
                </div>
                <div class="flex justify-between text-sm mt-2" :class="remaining > 0 ? 'text-red-600' : 'text-emerald-600'">
                    <span>{{ __('Remaining') }}</span>
                    <span class="font-semibold" x-text="remaining.toFixed(2) + ' EGP'"></span>
                </div>
            </div>

            {{-- Payment Methods --}}
            <div class="space-y-3 mb-4">
                <template x-for="(payment, index) in payments" :key="index">
                    <div class="p-3 border border-slate-200 rounded-xl">
                        <div class="flex items-center gap-3">
                            <select x-model="payment.method" class="erp-input flex-1">
                                <option value="cash">{{ __('Cash') }}</option>
                                <option value="card">{{ __('Card') }}</option>
                                <option value="transfer">{{ __('Bank Transfer') }}</option>
                                <option value="cheque">{{ __('Cheque') }}</option>
                            </select>
                            <input type="number" 
                                   x-model.number="payment.amount" 
                                   step="0.01" min="0"
                                   placeholder="{{ __('Amount') }}"
                                   class="erp-input w-32">
                            <button type="button" 
                                    x-on:click="removePayment(index)"
                                    x-show="payments.length > 1"
                                    class="text-red-500 hover:text-red-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        
                        {{-- Card Details --}}
                        <div x-show="payment.method === 'card'" class="mt-2 grid grid-cols-2 gap-2">
                            <input type="text" x-model="payment.card_type" placeholder="{{ __('Card Type (Visa/MC)') }}" class="erp-input text-sm">
                            <input type="text" x-model="payment.card_last_four" placeholder="{{ __('Last 4 digits') }}" maxlength="4" class="erp-input text-sm">
                        </div>
                        
                        {{-- Bank Transfer Details --}}
                        <div x-show="payment.method === 'transfer'" class="mt-2 grid grid-cols-2 gap-2">
                            <input type="text" x-model="payment.bank_name" placeholder="{{ __('Bank Name') }}" class="erp-input text-sm">
                            <input type="text" x-model="payment.reference_no" placeholder="{{ __('Reference No.') }}" class="erp-input text-sm">
                        </div>
                        
                        {{-- Cheque Details --}}
                        <div x-show="payment.method === 'cheque'" class="mt-2 grid grid-cols-2 gap-2">
                            <input type="text" x-model="payment.cheque_number" placeholder="{{ __('Cheque Number') }}" class="erp-input text-sm">
                            <input type="date" x-model="payment.cheque_date" class="erp-input text-sm">
                        </div>
                    </div>
                </template>
            </div>

            {{-- Quick Cash Buttons --}}
            <div class="flex flex-wrap gap-2 mb-4" x-show="payments.length === 1 && payments[0].method === 'cash'">
                <button type="button" x-on:click="payments[0].amount = total" class="px-3 py-1 text-xs bg-emerald-100 text-emerald-700 rounded-full hover:bg-emerald-200">{{ __('Exact') }}</button>
                <button type="button" x-on:click="payments[0].amount = Math.ceil(total / 10) * 10" class="px-3 py-1 text-xs bg-slate-100 text-slate-700 rounded-full hover:bg-slate-200">{{ __('Round 10') }}</button>
                <button type="button" x-on:click="payments[0].amount = Math.ceil(total / 50) * 50" class="px-3 py-1 text-xs bg-slate-100 text-slate-700 rounded-full hover:bg-slate-200">{{ __('Round 50') }}</button>
                <button type="button" x-on:click="payments[0].amount = Math.ceil(total / 100) * 100" class="px-3 py-1 text-xs bg-slate-100 text-slate-700 rounded-full hover:bg-slate-200">{{ __('Round 100') }}</button>
            </div>

            <button type="button" 
                    x-on:click="addPayment()"
                    class="w-full mb-4 py-2 text-sm text-emerald-600 border border-dashed border-emerald-300 rounded-xl hover:bg-emerald-50">
                + {{ __('Add Payment Method') }}
            </button>

            {{-- Change --}}
            <div class="p-3 bg-amber-50 rounded-xl mb-4" x-show="change > 0">
                <div class="flex justify-between">
                    <span class="text-amber-700">{{ __('Change to return') }}</span>
                    <span class="font-bold text-amber-700 text-lg" x-text="change.toFixed(2) + ' EGP'"></span>
                </div>
            </div>

            <div class="flex gap-3">
                <button type="button" 
                        x-on:click="showPaymentModal = false"
                        class="erp-btn-secondary flex-1">
                    {{ __('Cancel') }}
                </button>
                <button type="button" 
                        x-on:click="checkout()"
                        x-bind:disabled="isCheckingOut || totalPaid < total"
                        class="erp-btn-primary flex-1 disabled:opacity-60">
                    <span x-show="!isCheckingOut">{{ __('Complete Sale') }}</span>
                    <span x-show="isCheckingOut">{{ __('Processing...') }}</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Session Modal --}}
    <div x-show="showSessionModal" 
         x-cloak
         class="z-modal fixed inset-0 flex items-center justify-center bg-black/50"
         x-transition>
        <div class="w-full max-w-md mx-4 bg-white rounded-2xl shadow-2xl p-6" x-on:click.outside="showSessionModal = false">
            <h3 class="text-lg font-semibold text-slate-800 mb-4" x-text="currentSession ? '{{ __('Close Session') }}' : '{{ __('Open Session') }}'"></h3>
            
            {{-- Open Session Form --}}
            <div x-show="!currentSession">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Opening Cash') }}</label>
                    <input type="number" x-model.number="sessionOpeningCash" step="0.01" min="0" class="erp-input w-full" placeholder="0.00">
                </div>
                <button type="button" 
                        x-on:click="openSession()"
                        class="erp-btn-primary w-full">
                    {{ __('Start Session') }}
                </button>
            </div>
            
            {{-- Close Session Form --}}
            <div x-show="currentSession">
                <div class="mb-4 p-3 bg-slate-50 rounded-xl text-sm">
                    <div class="flex justify-between mb-2">
                        <span class="text-slate-600">{{ __('Opening Cash') }}</span>
                        <span x-text="(currentSession?.opening_cash ?? 0).toFixed(2) + ' EGP'"></span>
                    </div>
                    <div class="flex justify-between mb-2">
                        <span class="text-slate-600">{{ __('Opened At') }}</span>
                        <span x-text="currentSession?.opened_at ?? '-'"></span>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Closing Cash') }}</label>
                    <input type="number" x-model.number="sessionClosingCash" step="0.01" min="0" class="erp-input w-full" placeholder="0.00">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Notes') }}</label>
                    <textarea x-model="sessionNotes" class="erp-input w-full" rows="2" placeholder="{{ __('Any notes about this session...') }}"></textarea>
                </div>
                
                <button type="button" 
                        x-on:click="closeSession()"
                        class="erp-btn-primary w-full bg-red-600 hover:bg-red-700">
                    {{ __('Close Session') }}
                </button>
            </div>
            
            <button type="button" 
                    x-on:click="showSessionModal = false"
                    class="mt-3 w-full py-2 text-sm text-slate-600 hover:text-slate-800">
                {{ __('Cancel') }}
            </button>
        </div>
    </div>
</div>

@script
// UNFIXED-01 FIX: Use @script block for proper Livewire 4 component-scoped JavaScript
// This ensures the script is properly executed during SPA navigation with wire:navigate
function erpPosTerminal(config) {
    return {
        branchId: config.branchId,
        search: '',
        products: [],
        cart: [],
        offline: false,
        offlineQueue: [],
        isSearching: false,
        isCheckingOut: false,
        message: null,
        showPaymentModal: false,
        showSessionModal: false,
        payments: [{ method: 'cash', amount: 0 }],
        currentSession: null,
        sessionOpeningCash: 0,
        sessionClosingCash: 0,
        sessionNotes: '',
        displayCurrency: config.baseCurrency || 'EGP',
        baseCurrency: config.baseCurrency || 'EGP',
        currencyRates: config.currencyRates || { 'EGP': 1 },
        currencySymbols: config.currencySymbols || { 'EGP': 'ج.م' },

        convertAmount(amount) {
            const rate = this.currencyRates[this.displayCurrency] || 1;
            return (amount * rate).toFixed(2);
        },

        formatCurrency(amount) {
            const converted = this.convertAmount(amount);
            return converted + ' ' + this.displayCurrency;
        },

        get subtotal() {
            return this.cart.reduce((sum, item) => sum + (item.qty * item.price), 0);
        },

        get discountTotal() {
            return this.cart.reduce((sum, item) => {
                const lineTotal = item.qty * item.price;
                const discount = (item.discount || 0) / 100;
                return sum + (lineTotal * discount);
            }, 0);
        },

        get total() {
            return this.subtotal - this.discountTotal;
        },

        get totalPaid() {
            return this.payments.reduce((sum, p) => sum + (parseFloat(p.amount) || 0), 0);
        },

        get remaining() {
            return Math.max(0, this.total - this.totalPaid);
        },

        get change() {
            return Math.max(0, this.totalPaid - this.total);
        },

        calculateItemTotal(item) {
            const lineTotal = item.qty * item.price;
            const discount = (item.discount || 0) / 100;
            return lineTotal - (lineTotal * discount);
        },

        init() {
            this.loadOfflineQueue();
            this.checkSession();
            window.addEventListener('offline', () => this.offline = true);
            window.addEventListener('online', () => {
                this.offline = false;
                this.syncOfflineQueue();
            });
        },

        async fetchProducts() {
            if (this.search.length < 2) {
                this.products = [];
                return;
            }
            this.isSearching = true;
            try {
                const res = await fetch(`/api/v1/products?search=${encodeURIComponent(this.search)}&limit=20`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                this.products = data.data || data || [];
            } catch (e) {
                console.error('Search error:', e);
                this.products = [];
            }
            this.isSearching = false;
        },

        addProduct(product) {
            const existing = this.cart.find(c => c.product_id === (product.id ?? product.product_id));
            if (existing) {
                existing.qty++;
            } else {
                this.cart.push({
                    product_id: product.id ?? product.product_id,
                    name: product.name ?? product.label ?? 'Item',
                    sku: product.sku ?? product.code ?? '',
                    qty: 1,
                    price: parseFloat(product.price ?? product.sale_price ?? 0),
                    discount: 0
                });
            }
        },

        updateQty(index, qty) {
            if (qty < 1) this.cart[index].qty = 1;
        },

        updatePrice(index, price) {
            if (price < 0) this.cart[index].price = 0;
        },

        removeItem(index) {
            this.cart.splice(index, 1);
        },

        openPaymentModal() {
            this.payments = [{ method: 'cash', amount: this.total }];
            this.showPaymentModal = true;
        },

        addPayment() {
            this.payments.push({ method: 'cash', amount: 0 });
        },

        removePayment(index) {
            if (this.payments.length > 1) {
                this.payments.splice(index, 1);
            }
        },

        async checkout() {
            if (!this.cart.length) return;
            this.isCheckingOut = true;

            const payload = {
                branch_id: this.branchId,
                items: this.cart.map(item => ({
                    product_id: item.product_id,
                    qty: item.qty,
                    price: item.price,
                    discount: item.discount || 0,
                    percent: true
                })),
                payments: this.payments.filter(p => p.amount > 0).map(p => ({
                    method: p.method,
                    amount: p.amount,
                    reference_no: p.reference_no || null,
                    card_type: p.card_type || null,
                    card_last_four: p.card_last_four || null,
                    bank_name: p.bank_name || null,
                    cheque_number: p.cheque_number || null,
                    cheque_date: p.cheque_date || null
                }))
            };

            if (this.offline) {
                this.offlineQueue.push({ ...payload, timestamp: Date.now() });
                this.saveOfflineQueue();
                this.showMessage('success', '{{ __('Order queued for sync when online') }}');
                this.resetCart();
                this.isCheckingOut = false;
                return;
            }

            try {
                const res = await fetch('/api/v1/pos/checkout', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();
                if (res.ok && data.data) {
                    this.showMessage('success', '{{ __('Sale completed successfully!') }}');
                    this.resetCart();
                    this.showPaymentModal = false;
                    if (typeof erpShowNotification === 'function') {
                        erpShowNotification('{{ __('Sale completed') }}', 'success');
                    }
                } else {
                    throw new Error(data.message || 'Checkout failed');
                }
            } catch (e) {
                console.error('Checkout error:', e);
                this.showMessage('error', e.message || '{{ __('Checkout failed. Please try again.') }}');
            }

            this.isCheckingOut = false;
        },

        resetCart() {
            this.cart = [];
            this.payments = [{ method: 'cash', amount: 0 }];
        },

        showMessage(type, text) {
            this.message = { type, text };
            setTimeout(() => this.message = null, 5000);
        },

        clearMessage() {
            this.message = null;
        },

        saveOfflineQueue() {
            localStorage.setItem('pos_offline_queue', JSON.stringify(this.offlineQueue));
        },

        loadOfflineQueue() {
            const saved = localStorage.getItem('pos_offline_queue');
            this.offlineQueue = saved ? JSON.parse(saved) : [];
        },

        async syncOfflineQueue() {
            if (!this.offlineQueue.length || this.offline) return;
            
            const queue = [...this.offlineQueue];
            this.offlineQueue = [];
            
            for (const order of queue) {
                try {
                    await fetch('/api/v1/pos/checkout', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                        },
                        body: JSON.stringify({ ...order, notes: 'Synced from offline POS' })
                    });
                } catch (e) {
                    this.offlineQueue.push(order);
                }
            }
            
            this.saveOfflineQueue();
            if (this.offlineQueue.length === 0) {
                this.showMessage('success', '{{ __('All offline orders synced!') }}');
            }
        },

        openSessionModal() {
            this.showSessionModal = true;
        },

        async checkSession() {
            try {
                const res = await fetch(`/api/v1/pos/session?branch_id=${this.branchId}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                this.currentSession = data.data || null;
            } catch (e) {
                console.error('Session check error:', e);
            }
        },

        async openSession() {
            try {
                const res = await fetch('/api/v1/pos/session/open', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        branch_id: this.branchId,
                        opening_cash: this.sessionOpeningCash
                    })
                });
                const data = await res.json();
                if (res.ok) {
                    this.currentSession = data.data;
                    this.showSessionModal = false;
                    this.showMessage('success', '{{ __('Session opened successfully') }}');
                }
            } catch (e) {
                this.showMessage('error', '{{ __('Failed to open session') }}');
            }
        },

        async closeSession() {
            if (!this.currentSession) return;
            try {
                const res = await fetch(`/api/v1/pos/session/${this.currentSession.id}/close`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        closing_cash: this.sessionClosingCash,
                        notes: this.sessionNotes
                    })
                });
                const data = await res.json();
                if (res.ok) {
                    this.currentSession = null;
                    this.showSessionModal = false;
                    this.showMessage('success', '{{ __('Session closed successfully') }}');
                }
            } catch (e) {
                this.showMessage('error', '{{ __('Failed to close session') }}');
            }
        }
    };
}
@endscript
