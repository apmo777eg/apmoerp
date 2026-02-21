// resources/js/pos.js
/**
 * POS Terminal Frontend (Alpine component)
 *
 * IMPORTANT:
 * - The POS Blade view (`resources/views/livewire/pos/terminal.blade.php`) expects a rich Alpine data object.
 * - Previously, the view shipped with an inline `erpPosTerminal()` implementation, but the Vite bundle
 *   (app.js -> imports this file) overwrote it after HTML parsing. That mismatch broke the POS UI
 *   (missing properties/methods) and caused "endless loading" / non-working buttons.
 *
 * This file is now the single source of truth.
 */

// ---------- Small utilities ----------

/** Create a UUIDv4 (good enough for idempotency keys in the browser). */
function uuidv4() {
    // Prefer crypto API when available
    if (window.crypto?.randomUUID) {
        return window.crypto.randomUUID();
    }

    // Fallback (RFC4122-ish)
    const rnd = (n) => (Math.random() * n) | 0;
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
        const r = rnd(16);
        const v = c === 'x' ? r : (r & 0x3) | 0x8;
        return v.toString(16);
    });
}

/** Normalize common API response shapes to a data payload. */
function unwrapApiResponse(responseData) {
    // ApiResponse::success => { success: true, message, data }
    if (responseData && typeof responseData === 'object' && 'data' in responseData) {
        return responseData.data;
    }
    return responseData;
}

/**
 * Extract a user-facing message from Axios errors.
 * Keeps the UI from looking like "it is loading forever".
 */
function axiosErrorMessage(error, fallback = 'Request failed') {
    if (!error) return fallback;
    if (error.code === 'ECONNABORTED') return 'Request timed out. Please try again.';

    const status = error.response?.status;
    const data = error.response?.data;

    if (status === 401) return 'Session expired. Please login again.';
    if (status === 403) return 'You do not have permission to perform this action.';
    if (status === 409) return data?.message || 'Conflict. Please refresh and retry.';
    if (status === 422) {
        // Validation response formats
        if (data?.errors && typeof data.errors === 'object') {
            const flat = Object.values(data.errors).flat().filter(Boolean);
            if (flat.length) return flat.join(' ');
        }
        return data?.message || 'Validation failed.';
    }
    if (status >= 500) return 'Server error. Please try again later.';

    return data?.message || error.message || fallback;
}

// ---------- POS Alpine component ----------

export function erpPosTerminal(config = {}) {
    const branchId = Number(config.branchId || 0);
    const warehouseId = config.warehouseId !== undefined ? Number(config.warehouseId) : null;

    const apiBase = `/api/v1/branches/${branchId}`;
    // Keep backward-compatible localStorage keys (older builds used these)
    const storageKey = `pos_cart_branch_${branchId}`;
    const offlineQueueKey = `pos_offline_sales_branch_${branchId}`;

    const TIMEOUTS = {
        search: 10000,
        checkout: 20000,
        session: 12000,
        sync: 25000,
    };

    return {
        // ---------- Config ----------
        branchId,
        warehouseId,
        apiBase,

        baseCurrency: config.baseCurrency || 'EGP',
        displayCurrency: config.baseCurrency || 'EGP',
        currencyRates: config.currencyRates || { EGP: 1 },
        currencySymbols: config.currencySymbols || { EGP: 'EGP' },

        // ---------- State ----------
        search: '',
        isSearching: false,
        isCheckingOut: false,
        products: [],

        cart: [],

        // UI
        message: null,
        offline: !window.navigator.onLine,
        offlineQueue: [],

        // Payments modal
        showPaymentModal: false,
        payments: [{ method: 'cash', amount: 0 }],

        // Session modal
        showSessionModal: false,
        currentSession: null,
        sessionOpeningCash: 0,
        sessionClosingCash: 0,
        sessionNotes: '',

        // ---------- Computed totals (base currency) ----------
        get subtotal() {
            return (this.cart || []).reduce((sum, item) => {
                const qty = parseFloat(item.qty) || 0;
                const price = parseFloat(item.price) || 0;
                return sum + qty * price;
            }, 0);
        },

        get discountTotal() {
            return (this.cart || []).reduce((sum, item) => {
                const qty = parseFloat(item.qty) || 0;
                const price = parseFloat(item.price) || 0;
                const discount = parseFloat(item.discount) || 0;
                const isPercent = !!item.percent;

                const lineSub = qty * price;
                const lineDisc = isPercent ? (lineSub * discount) / 100 : discount;
                return sum + Math.max(0, lineDisc);
            }, 0);
        },

        get total() {
            const t = this.subtotal - this.discountTotal;
            return Math.max(0, t);
        },

        get totalPaid() {
            return (this.payments || []).reduce((sum, p) => sum + (parseFloat(p.amount) || 0), 0);
        },

        get remaining() {
            return Math.max(0, this.total - this.totalPaid);
        },

        get change() {
            return Math.max(0, this.totalPaid - this.total);
        },

        // ---------- Formatting / helpers ----------
        convertAmount(amount) {
            const rate = this.currencyRates?.[this.displayCurrency] || 1;
            return (parseFloat(amount) || 0) * rate;
        },

        formatCurrency(amount) {
            const converted = this.convertAmount(amount).toFixed(2);
            return `${converted} ${this.displayCurrency}`;
        },

        calculateItemTotal(item) {
            const qty = parseFloat(item?.qty) || 0;
            const price = parseFloat(item?.price) || 0;
            const discount = parseFloat(item?.discount) || 0;
            const isPercent = !!item?.percent;

            const lineSub = qty * price;
            const lineDisc = isPercent ? (lineSub * discount) / 100 : discount;
            return Math.max(0, lineSub - Math.max(0, lineDisc));
        },

        clearMessage() {
            this.message = null;
        },

        showMessage(type, text, timeoutMs = 5000) {
            this.message = { type, text };
            if (timeoutMs) {
                window.setTimeout(() => {
                    // Only clear if it's the same message (avoid race conditions)
                    if (this.message?.text === text) this.message = null;
                }, timeoutMs);
            }
        },

        // ---------- Lifecycle ----------
        init() {
            this.loadCart();
            this.loadOfflineQueue();

            // initial status
            this.offline = !window.navigator.onLine;

            // If user comes back online, try syncing automatically
            window.addEventListener('online', () => {
                this.offline = false;
                this.syncOfflineQueue();
            });
            window.addEventListener('offline', () => {
                this.offline = true;
            });

            // Session state
            this.checkSession();
        },

        // ---------- Local storage ----------
        loadCart() {
            try {
                let raw = window.localStorage.getItem(storageKey);

                // Legacy (older inline POS script)
                if (!raw) {
                    const legacy = window.localStorage.getItem('pos_cart');
                    if (legacy) {
                        raw = legacy;
                        // migrate
                        window.localStorage.setItem(storageKey, legacy);
                        window.localStorage.removeItem('pos_cart');
                    }
                }

                this.cart = raw ? JSON.parse(raw) : [];
            } catch (e) {
                console.error('[POS] Failed to load cart', e);
                this.cart = [];
            }
        },

        persistCart() {
            try {
                window.localStorage.setItem(storageKey, JSON.stringify(this.cart || []));
            } catch (e) {
                console.error('[POS] Failed to persist cart', e);
            }
        },

        loadOfflineQueue() {
            try {
                let raw = window.localStorage.getItem(offlineQueueKey);

                // Legacy (older inline POS script)
                if (!raw) {
                    const legacy = window.localStorage.getItem('pos_offline_queue');
                    if (legacy) {
                        raw = legacy;
                        window.localStorage.setItem(offlineQueueKey, legacy);
                        window.localStorage.removeItem('pos_offline_queue');
                    }
                }

                this.offlineQueue = raw ? JSON.parse(raw) : [];
            } catch (e) {
                console.error('[POS] Failed to load offline queue', e);
                this.offlineQueue = [];
            }
        },

        persistOfflineQueue() {
            try {
                window.localStorage.setItem(offlineQueueKey, JSON.stringify(this.offlineQueue || []));
            } catch (e) {
                console.error('[POS] Failed to persist offline queue', e);
            }
        },

        // ---------- Products ----------
        async fetchProducts() {
            if (!this.search || this.search.length < 2) {
                this.products = [];
                return;
            }

            if (this.offline) {
                this.showMessage('info', 'Offline: product search is unavailable.');
                return;
            }

            if (this.isSearching) return;
            this.isSearching = true;
            this.products = [];
            this.clearMessage();

            try {
                const res = await window.axios.get(`${apiBase}/products/search`, {
                    params: { q: this.search },
                    timeout: TIMEOUTS.search,
                });

                const payload = unwrapApiResponse(res.data);

                // Support paginated responses
                if (Array.isArray(payload)) {
                    this.products = payload;
                } else if (payload && Array.isArray(payload.data)) {
                    this.products = payload.data;
                } else {
                    this.products = [];
                }
            } catch (e) {
                console.error('[POS] search error', e);
                this.showMessage('error', axiosErrorMessage(e, 'Search failed.'));
            } finally {
                this.isSearching = false;
            }
        },

        addProduct(product) {
            if (!product) return;

            const id = product.id ?? product.product_id;
            if (!id) return;

            const existing = (this.cart || []).find((i) => i.product_id === id);
            if (existing) {
                existing.qty = (parseFloat(existing.qty) || 0) + 1;
                this.persistCart();
                return;
            }

            const price =
                product.default_price ??
                product.price ??
                product.sale_price ??
                product.unit_price ??
                0;

            this.cart.push({
                product_id: id,
                name: product.name ?? product.label ?? 'Item',
                qty: 1,
                price: parseFloat(price) || 0,
                discount: 0,
                percent: true,
                tax_id: product.tax_id ?? null,
            });

            this.persistCart();
        },

        removeItem(index) {
            if (!Array.isArray(this.cart)) return;
            if (index < 0 || index >= this.cart.length) return;
            this.cart.splice(index, 1);
            this.persistCart();
        },

        updateQty(index, qty) {
            if (!Array.isArray(this.cart)) return;
            if (index < 0 || index >= this.cart.length) return;

            const v = parseFloat(qty);
            if (!Number.isFinite(v) || v <= 0) {
                this.cart[index].qty = 1;
            } else {
                this.cart[index].qty = Math.min(v, 999999);
            }

            this.persistCart();
        },

        updatePrice(index, price) {
            if (!Array.isArray(this.cart)) return;
            if (index < 0 || index >= this.cart.length) return;

            const v = parseFloat(price);
            if (!Number.isFinite(v) || v < 0) {
                this.cart[index].price = 0;
            } else {
                // keep 2 decimals for UI; backend stores 4 decimals and rounds
                this.cart[index].price = Math.round(v * 100) / 100;
            }

            this.persistCart();
        },

        // ---------- Payments UI ----------
        openPaymentModal() {
            if (!this.cart?.length) {
                this.showMessage('info', 'Cart is empty.');
                return;
            }
            this.payments = [{ method: 'cash', amount: this.total }];
            this.showPaymentModal = true;
        },

        addPayment() {
            this.payments.push({ method: 'cash', amount: 0 });
        },

        removePayment(index) {
            if (this.payments.length <= 1) return;
            this.payments.splice(index, 1);
        },

        // ---------- Checkout / Offline queue ----------
        enqueueOfflineSale(payload) {
            this.offlineQueue = this.offlineQueue || [];
            this.offlineQueue.push({
                payload,
                queued_at: new Date().toISOString(),
            });
            this.persistOfflineQueue();
        },

        async checkout() {
            if (!this.cart?.length) {
                this.showMessage('info', 'Cart is empty.');
                return;
            }

            if (!this.warehouseId) {
                // Backend requires it (and now request validation too)
                this.showMessage('error', 'Warehouse is required. Please select a warehouse for this terminal.');
                return;
            }

            if (this.isCheckingOut) return;
            this.isCheckingOut = true;
            this.clearMessage();

            // Build payload (base currency)
            const items = this.cart.map((item) => ({
                product_id: item.product_id,
                qty: parseFloat(item.qty) || 1,
                price: parseFloat(item.price) || 0,
                discount: parseFloat(item.discount) || 0,
                percent: !!item.percent,
                tax_id: item.tax_id ?? null,
            }));

            const payments = (this.payments || [])
                .filter((p) => (parseFloat(p.amount) || 0) > 0)
                .map((p) => {
                    const meta = {
                        // card
                        card_type: p.card_type || null,
                        card_last_four: p.card_last_four || null,
                        // transfer
                        bank_name: p.bank_name || null,
                        reference_number: p.reference_number || p.reference_no || null,
                        // cheque
                        cheque_number: p.cheque_number || null,
                        cheque_date: p.cheque_date || null,
                    };

                    // Remove empty keys to keep payload tidy
                    Object.keys(meta).forEach((k) => {
                        if (!meta[k]) delete meta[k];
                    });

                    return {
                        method: p.method || 'cash',
                        amount: parseFloat(p.amount) || 0,
                        currency: this.baseCurrency,
                        ...(Object.keys(meta).length ? { meta } : {}),
                    };
                });

            const payload = {
                client_uuid: uuidv4(),
                warehouse_id: this.warehouseId,
                channel: 'pos',
                currency: this.baseCurrency,
                items,
                payments,
                // Keep business date explicit (helps offline/backdated flows)
                sale_date: new Date().toISOString().slice(0, 10),
            };

            // Offline -> queue and clear
            if (this.offline) {
                this.enqueueOfflineSale(payload);
                this.cart = [];
                this.persistCart();
                this.showPaymentModal = false;
                this.payments = [{ method: 'cash', amount: 0 }];
                this.showMessage('info', 'Saved offline. Will sync when online.');
                this.isCheckingOut = false;
                return;
            }

            try {
                const res = await window.axios.post(`${apiBase}/pos/checkout`, payload, {
                    timeout: TIMEOUTS.checkout,
                });

                // success
                const msg = res.data?.message || 'Checkout completed.';

                this.cart = [];
                this.persistCart();
                this.showPaymentModal = false;
                this.payments = [{ method: 'cash', amount: 0 }];

                this.showMessage('success', msg);
            } catch (e) {
                console.error('[POS] checkout error', e);
                this.showMessage('error', axiosErrorMessage(e, 'Checkout failed.'));
            } finally {
                this.isCheckingOut = false;
            }
        },

        async syncOfflineQueue() {
            if (!this.offlineQueue?.length) {
                this.showMessage('info', 'No offline orders to sync.');
                return;
            }

            if (this.offline) {
                this.showMessage('info', 'Still offline. Sync will start when online.');
                return;
            }

            // Process sequentially to keep things deterministic (and avoid server overload)
            const queue = [...this.offlineQueue];
            for (const entry of queue) {
                try {
                    await window.axios.post(`${apiBase}/pos/checkout`, entry.payload ?? {}, {
                        timeout: TIMEOUTS.sync,
                    });

                    // Remove the first item (FIFO)
                    this.offlineQueue.shift();
                    this.persistOfflineQueue();
                } catch (e) {
                    console.error('[POS] offline sync error', e);
                    this.showMessage('error', axiosErrorMessage(e, 'Failed to sync offline orders.'));
                    return;
                }
            }

            this.showMessage('success', 'Offline orders synced successfully.');
        },

        // ---------- POS Session ----------
        openSessionModal() {
            this.showSessionModal = true;
        },

        async checkSession() {
            if (!branchId) return;

            try {
                const res = await window.axios.get(`${apiBase}/pos/session`, { timeout: TIMEOUTS.session });
                const data = unwrapApiResponse(res.data);
                this.currentSession = data || null;
            } catch (e) {
                // Do not block the POS UI if session endpoint is unavailable
                console.warn('[POS] Session check failed', e);
                this.currentSession = null;
            }
        },

        async openSession() {
            try {
                const res = await window.axios.post(
                    `${apiBase}/pos/session/open`,
                    { opening_cash: parseFloat(this.sessionOpeningCash) || 0 },
                    { timeout: TIMEOUTS.session }
                );
                const data = unwrapApiResponse(res.data);
                this.currentSession = data || null;
                this.sessionOpeningCash = 0;
                this.showSessionModal = false;
                this.showMessage('success', res.data?.message || 'Session opened.');
            } catch (e) {
                console.error('[POS] open session error', e);
                this.showMessage('error', axiosErrorMessage(e, 'Failed to open session.'));
            }
        },

        async closeSession() {
            if (!this.currentSession?.id) return;

            try {
                const res = await window.axios.post(
                    `${apiBase}/pos/session/${this.currentSession.id}/close`,
                    {
                        closing_cash: parseFloat(this.sessionClosingCash) || 0,
                        notes: this.sessionNotes || null,
                    },
                    { timeout: TIMEOUTS.session }
                );
                this.currentSession = null;
                this.sessionClosingCash = 0;
                this.sessionNotes = '';
                this.showSessionModal = false;
                this.showMessage('success', res.data?.message || 'Session closed.');
            } catch (e) {
                console.error('[POS] close session error', e);
                this.showMessage('error', axiosErrorMessage(e, 'Failed to close session.'));
            }
        },
    };
}

// Make it available to inline x-data="erpPosTerminal(...)"
window.erpPosTerminal = erpPosTerminal;
