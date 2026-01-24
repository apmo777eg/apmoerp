/**
 * HugousERP Service Worker
 * Provides offline support and caching for the ERP system
 * 
 * Features:
 * - Cache static assets for offline access
 * - Network-first strategy for API calls
 * - Offline fallback pages
 * - Background sync for offline operations
 * - Push notification support
 * - IndexedDB for offline data storage
 */

const CACHE_VERSION = 'v1.2.0';
const CACHE_NAME = `hugouserp-${CACHE_VERSION}`;
const API_CACHE_NAME = `hugouserp-api-${CACHE_VERSION}`;
const DB_NAME = 'hugouserp-offline';
const DB_VERSION = 1;

// Assets to cache on install
const STATIC_ASSETS = [
    '/',
    '/offline.html',
    '/manifest.json',
    '/favicon.ico',
    '/sounds/notification.mp3',
];

// API endpoints that should be cached with network-first strategy
const CACHEABLE_API_PATTERNS = [
    /\/api\/products/,
    /\/api\/customers/,
    /\/api\/categories/,
    /\/api\/settings/,
];

// ===========================================
// IndexedDB for Offline Data Storage
// ===========================================
let db = null;

function openDatabase() {
    return new Promise((resolve, reject) => {
        if (db) {
            resolve(db);
            return;
        }
        
        const request = indexedDB.open(DB_NAME, DB_VERSION);
        
        request.onerror = () => reject(request.error);
        request.onsuccess = () => {
            db = request.result;
            resolve(db);
        };
        
        request.onupgradeneeded = (event) => {
            const database = event.target.result;
            
            // Store for offline sales
            if (!database.objectStoreNames.contains('offline_sales')) {
                const salesStore = database.createObjectStore('offline_sales', { 
                    keyPath: 'id', 
                    autoIncrement: true 
                });
                salesStore.createIndex('created_at', 'created_at', { unique: false });
                salesStore.createIndex('synced', 'synced', { unique: false });
            }
            
            // Store for offline products (for POS)
            if (!database.objectStoreNames.contains('offline_products')) {
                const productsStore = database.createObjectStore('offline_products', { 
                    keyPath: 'id' 
                });
                productsStore.createIndex('sku', 'sku', { unique: false });
                productsStore.createIndex('barcode', 'barcode', { unique: false });
            }
            
            // Store for offline customers
            if (!database.objectStoreNames.contains('offline_customers')) {
                const customersStore = database.createObjectStore('offline_customers', { 
                    keyPath: 'id' 
                });
                customersStore.createIndex('name', 'name', { unique: false });
            }
            
            // Store for pending sync operations
            if (!database.objectStoreNames.contains('sync_queue')) {
                const syncStore = database.createObjectStore('sync_queue', { 
                    keyPath: 'id', 
                    autoIncrement: true 
                });
                syncStore.createIndex('type', 'type', { unique: false });
                syncStore.createIndex('created_at', 'created_at', { unique: false });
            }
        };
    });
}

// Store data for offline use
async function storeOfflineData(storeName, data) {
    try {
        const database = await openDatabase();
        const tx = database.transaction(storeName, 'readwrite');
        const store = tx.objectStore(storeName);
        
        if (Array.isArray(data)) {
            data.forEach(item => store.put(item));
        } else {
            store.put(data);
        }
        
        return new Promise((resolve, reject) => {
            tx.oncomplete = () => resolve(true);
            tx.onerror = () => reject(tx.error);
        });
    } catch (error) {
        console.error('[SW] Store offline data failed:', error);
        return false;
    }
}

// Get data from offline store
async function getOfflineData(storeName, key = null) {
    try {
        const database = await openDatabase();
        const tx = database.transaction(storeName, 'readonly');
        const store = tx.objectStore(storeName);
        
        const request = key ? store.get(key) : store.getAll();
        
        return new Promise((resolve, reject) => {
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    } catch (error) {
        console.error('[SW] Get offline data failed:', error);
        return null;
    }
}

// Add to sync queue
async function addToSyncQueue(type, data) {
    try {
        const database = await openDatabase();
        const tx = database.transaction('sync_queue', 'readwrite');
        const store = tx.objectStore('sync_queue');
        
        store.add({
            type,
            data,
            created_at: new Date().toISOString(),
            attempts: 0
        });
        
        return new Promise((resolve, reject) => {
            tx.oncomplete = () => resolve(true);
            tx.onerror = () => reject(tx.error);
        });
    } catch (error) {
        console.error('[SW] Add to sync queue failed:', error);
        return false;
    }
}

// Get pending sync items
async function getPendingSyncItems() {
    try {
        return await getOfflineData('sync_queue');
    } catch (error) {
        return [];
    }
}

// Remove from sync queue
async function removeFromSyncQueue(id) {
    try {
        const database = await openDatabase();
        const tx = database.transaction('sync_queue', 'readwrite');
        const store = tx.objectStore('sync_queue');
        store.delete(id);
        
        return new Promise((resolve, reject) => {
            tx.oncomplete = () => resolve(true);
            tx.onerror = () => reject(tx.error);
        });
    } catch (error) {
        return false;
    }
}

// Install event - cache static assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('[SW] Caching static assets');
                // Only cache assets that exist and can be fetched
                return Promise.all(
                    STATIC_ASSETS.map(url => {
                        return fetch(url)
                            .then(response => {
                                if (response.ok && response.status === 200) {
                                    return cache.put(url, response);
                                }
                                return Promise.resolve();
                            })
                            .catch(() => Promise.resolve());
                    })
                );
            })
            .then(() => self.skipWaiting())
            .catch((error) => {
                console.warn('[SW] Cache install failed:', error);
            })
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames
                        .filter((name) => name.startsWith('hugouserp-') && name !== CACHE_NAME && name !== API_CACHE_NAME)
                        .map((name) => {
                            console.log('[SW] Deleting old cache:', name);
                            return caches.delete(name);
                        })
                );
            })
            .then(() => self.clients.claim())
    );
});

// Fetch event - handle requests with appropriate caching strategies
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Skip non-GET requests (Livewire uses POST for updates)
    if (request.method !== 'GET') {
        return;
    }

    // Skip browser extensions and external resources
    if (!url.origin.includes(self.location.origin)) {
        return;
    }

    // Skip livewire internal requests (both URL and header-based detection)
    if (url.pathname.includes('/livewire/') || request.headers.get('X-Livewire')) {
        return;
    }

    // API requests - Network first, fallback to cache
    if (url.pathname.startsWith('/api/')) {
        event.respondWith(networkFirstWithCache(request, API_CACHE_NAME));
        return;
    }

    // Static assets - Cache first, fallback to network
    if (isStaticAsset(url.pathname)) {
        event.respondWith(cacheFirstWithNetwork(request, CACHE_NAME));
        return;
    }

    // HTML pages - Network first, fallback to offline page
    if (request.headers.get('accept')?.includes('text/html')) {
        event.respondWith(networkFirstWithOffline(request));
        return;
    }

    // Default - Network with cache fallback
    event.respondWith(networkFirstWithCache(request, CACHE_NAME));
});

/**
 * Check if URL is a static asset
 */
function isStaticAsset(pathname) {
    const staticExtensions = ['.js', '.css', '.png', '.jpg', '.jpeg', '.gif', '.svg', '.woff', '.woff2', '.ttf', '.ico'];
    return staticExtensions.some(ext => pathname.endsWith(ext)) || pathname.startsWith('/build/');
}

/**
 * Cache-first strategy with network fallback
 */
async function cacheFirstWithNetwork(request, cacheName) {
    try {
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            // Validate cached response - don't serve HTML for JS/CSS requests
            const contentType = cachedResponse.headers.get('content-type') || '';
            const requestUrl = new URL(request.url);
            
            // If requesting JS but cached response is HTML, invalidate cache
            if (requestUrl.pathname.endsWith('.js') && contentType.includes('text/html')) {
                console.warn('[SW] Invalid cache: HTML cached for JS file', requestUrl.pathname);
                const cache = await caches.open(cacheName);
                await cache.delete(request);
                // Fall through to network request
            } else if (requestUrl.pathname.endsWith('.css') && contentType.includes('text/html')) {
                console.warn('[SW] Invalid cache: HTML cached for CSS file', requestUrl.pathname);
                const cache = await caches.open(cacheName);
                await cache.delete(request);
                // Fall through to network request
            } else {
                // Update cache in background
                updateCache(request, cacheName);
                return cachedResponse;
            }
        }

        const networkResponse = await fetch(request);
        if (networkResponse.ok && networkResponse.status === 200) {
            const contentType = networkResponse.headers.get('content-type') || '';
            const requestUrl = new URL(request.url);
            
            // Only cache if content-type matches the request
            let shouldCache = true;
            if (requestUrl.pathname.endsWith('.js')) {
                // Check for valid JavaScript MIME types
                const isValidJs = contentType.includes('application/javascript') || 
                                 contentType.includes('text/javascript') || 
                                 contentType.includes('application/ecmascript');
                if (!isValidJs) {
                    shouldCache = false;
                    console.warn('[SW] Not caching: Invalid content-type for JS file', requestUrl.pathname, contentType);
                }
            } else if (requestUrl.pathname.endsWith('.css') && !contentType.includes('css')) {
                shouldCache = false;
                console.warn('[SW] Not caching: Invalid content-type for CSS file', requestUrl.pathname, contentType);
            }
            
            if (shouldCache) {
                const cache = await caches.open(cacheName);
                cache.put(request, networkResponse.clone());
            }
        }
        return networkResponse;
    } catch (error) {
        console.warn('[SW] Cache-first failed:', error);
        if (request.headers.get('accept')?.includes('text/html')) {
            const offlineResponse = await caches.match('/offline.html');
            if (offlineResponse) {
                return offlineResponse;
            }
        }
        return new Response('Offline', { status: 504, statusText: 'Offline' });
    }
}

/**
 * Network-first strategy with cache fallback
 */
async function networkFirstWithCache(request, cacheName) {
    try {
        const networkResponse = await fetch(request);
        if (networkResponse.ok && networkResponse.status === 200) {
            const cache = await caches.open(cacheName);
            cache.put(request, networkResponse.clone());
        }
        return networkResponse;
    } catch (error) {
        console.warn('[SW] Network-first falling back to cache:', error);
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        
        // Return offline response for API calls
        if (request.url.includes('/api/')) {
            return new Response(JSON.stringify({
                success: false,
                offline: true,
                message: 'You are currently offline. Data will sync when connection is restored.'
            }), {
                headers: { 'Content-Type': 'application/json' }
            });
        }
        
        throw error;
    }
}

/**
 * Network-first strategy with offline page fallback
 */
async function networkFirstWithOffline(request) {
    try {
        const networkResponse = await fetch(request);
        return networkResponse;
    } catch (error) {
        console.warn('[SW] Network failed, showing offline page');
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        return caches.match('/offline.html');
    }
}

/**
 * Update cache in background
 */
async function updateCache(request, cacheName) {
    try {
        const networkResponse = await fetch(request);
        if (networkResponse.ok && networkResponse.status === 200) {
            const cache = await caches.open(cacheName);
            cache.put(request, networkResponse);
        }
    } catch (error) {
        // Silently fail - network might be unavailable
    }
}

// Push notification support
self.addEventListener('push', (event) => {
    if (!event.data) return;

    try {
        const data = event.data.json();
        const options = {
            body: data.body || data.message || 'New notification',
            icon: '/favicon.ico',
            badge: '/favicon.ico',
            vibrate: [100, 50, 100],
            data: {
                url: data.url || '/',
                ...data
            },
            actions: data.actions || []
        };

        event.waitUntil(
            self.registration.showNotification(data.title || 'HugousERP', options)
        );
    } catch (error) {
        console.error('[SW] Push notification error:', error);
    }
});

// Notification click handler
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const url = event.notification.data?.url || '/';
    
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then((clientList) => {
                // Focus existing window if open
                for (const client of clientList) {
                    if (client.url.includes(self.location.origin) && 'focus' in client) {
                        client.focus();
                        // Use postMessage for navigation if navigate is not available
                        if ('navigate' in client) {
                            client.navigate(url);
                        } else {
                            client.postMessage({ type: 'NAVIGATE', url: url });
                        }
                        return;
                    }
                }
                // Open new window
                if (clients.openWindow) {
                    return clients.openWindow(url);
                }
            })
    );
});

// Background sync for offline operations
self.addEventListener('sync', (event) => {
    if (event.tag === 'sync-offline-sales') {
        event.waitUntil(syncOfflineSales());
    }
    if (event.tag === 'sync-offline-data') {
        event.waitUntil(syncOfflineData());
    }
});

/**
 * Sync offline sales when connection is restored
 */
async function syncOfflineSales() {
    try {
        // Get offline sales from IndexedDB via message to client
        const clients = await self.clients.matchAll();
        for (const client of clients) {
            client.postMessage({
                type: 'SYNC_OFFLINE_SALES',
                timestamp: Date.now()
            });
        }
    } catch (error) {
        console.error('[SW] Sync offline sales failed:', error);
    }
}

/**
 * Generic sync for offline data
 */
async function syncOfflineData() {
    try {
        const clients = await self.clients.matchAll();
        for (const client of clients) {
            client.postMessage({
                type: 'SYNC_OFFLINE_DATA',
                timestamp: Date.now()
            });
        }
    } catch (error) {
        console.error('[SW] Sync offline data failed:', error);
    }
}

// Message handler for communication with main app
self.addEventListener('message', (event) => {
    const { type, data } = event.data || {};

    switch (type) {
        case 'SKIP_WAITING':
            self.skipWaiting();
            break;
        case 'CACHE_URLS':
            if (Array.isArray(data)) {
                caches.open(CACHE_NAME).then(cache => {
                    cache.addAll(data).catch(() => {});
                });
            }
            break;
        case 'CLEAR_CACHE':
            caches.keys().then(names => {
                names.forEach(name => {
                    if (name.startsWith('hugouserp-')) {
                        caches.delete(name);
                    }
                });
            });
            break;
        case 'STORE_OFFLINE_DATA':
            if (data && data.storeName && data.items) {
                storeOfflineData(data.storeName, data.items)
                    .then(() => {
                        event.source?.postMessage({ 
                            type: 'OFFLINE_DATA_STORED', 
                            storeName: data.storeName 
                        });
                    });
            }
            break;
        case 'GET_OFFLINE_DATA':
            if (data && data.storeName) {
                getOfflineData(data.storeName, data.key)
                    .then(result => {
                        event.source?.postMessage({ 
                            type: 'OFFLINE_DATA_RESULT', 
                            storeName: data.storeName,
                            data: result 
                        });
                    });
            }
            break;
        case 'ADD_TO_SYNC_QUEUE':
            if (data && data.type && data.payload) {
                addToSyncQueue(data.type, data.payload)
                    .then(() => {
                        event.source?.postMessage({ 
                            type: 'SYNC_QUEUE_UPDATED' 
                        });
                    });
            }
            break;
        case 'PROCESS_SYNC_QUEUE':
            processSyncQueue()
                .then(results => {
                    event.source?.postMessage({ 
                        type: 'SYNC_COMPLETE', 
                        results 
                    });
                });
            break;
        default:
            break;
    }
});

// Process sync queue when online
async function processSyncQueue() {
    const results = { success: 0, failed: 0, items: [] };
    
    try {
        const pendingItems = await getPendingSyncItems();
        
        for (const item of pendingItems) {
            try {
                // Try to sync the item
                const response = await fetch('/api/sync', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Sync-Type': item.type
                    },
                    body: JSON.stringify(item.data)
                });
                
                if (response.ok) {
                    await removeFromSyncQueue(item.id);
                    results.success++;
                    results.items.push({ id: item.id, status: 'success' });
                } else {
                    results.failed++;
                    results.items.push({ id: item.id, status: 'failed', error: 'Server error' });
                }
            } catch (error) {
                results.failed++;
                results.items.push({ id: item.id, status: 'failed', error: error.message });
            }
        }
    } catch (error) {
        console.error('[SW] Process sync queue failed:', error);
    }
    
    return results;
}
