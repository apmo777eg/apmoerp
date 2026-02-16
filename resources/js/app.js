import './bootstrap';
import { erpPosTerminal } from './pos';
import Swal from 'sweetalert2';
import Chart from 'chart.js/auto';
import Sortable from 'sortablejs';

// Livewire 4 uses wire:navigate for SPA-like navigation
// No need for Turbo.js - Livewire handles navigation natively

window.erpPosTerminal = erpPosTerminal;
window.Swal = Swal;
window.Chart = Chart;
window.Sortable = Sortable;

const NotificationSound = {
    audio: null,
    enabled: true,
    
    init() {
        try {
            this.audio = new Audio('/sounds/notification.mp3');
            this.audio.preload = 'auto';
            this.audio.volume = 0.5;
            const savedPref = localStorage.getItem('erp_notification_sound');
            this.enabled = savedPref !== '0';
        } catch (e) {
            console.warn('Notification sound initialization failed:', e);
        }
    },
    
    play() {
        if (!this.enabled || !this.audio) return;
        try {
            this.audio.currentTime = 0;
            this.audio.play().catch(() => {});
        } catch (e) {}
    },
    
    toggle() {
        this.enabled = !this.enabled;
        localStorage.setItem('erp_notification_sound', this.enabled ? '1' : '0');
        return this.enabled;
    },
    
    setVolume(vol) {
        if (this.audio) {
            this.audio.volume = Math.max(0, Math.min(1, vol));
        }
    },
    
    isEnabled() {
        return this.enabled;
    }
};

NotificationSound.init();
window.erpNotificationSound = NotificationSound;

if (window.Echo && window.Laravel && window.Laravel.userId) {
    window.Echo.private(`App.Models.User.${window.Laravel.userId}`)
        .listen('.notification.created', (e) => {
            if (window.Livewire) {
                window.Livewire.dispatch('notification-received', {
                    type: e.type ?? 'info',
                    message: e.message ?? '',
                });
            }
            window.erpShowNotification(e.message, e.type, true);
        });
}

if (typeof window !== 'undefined') {
    window.erpApplyTheme = function () {
        try {
            const saved = localStorage.getItem('theme');
            if (saved === 'dark') {
                document.documentElement.classList.add('dark');
            } else if (saved === 'system') {
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                document.documentElement.classList.toggle('dark', prefersDark);
            } else {
                document.documentElement.classList.remove('dark');
            }
        } catch (e) {}
    };

    window.erpToggleDarkMode = function () {
        try {
            const isDark = document.documentElement.classList.contains('dark');
            const next = !isDark;
            document.documentElement.classList.toggle('dark', next);
            localStorage.setItem('theme', next ? 'dark' : 'light');
        } catch (e) {}
    };

    document.addEventListener('DOMContentLoaded', () => {
        window.erpApplyTheme();
    });
}

window.erpShowToast = function (message, type = 'success') {
    try {
        const root = document.getElementById('erp-toast-root');
        if (!root) return;
        const el = document.createElement('div');
        el.className = 'pointer-events-auto mb-2 inline-flex items-center rounded-2xl px-4 py-2 text-sm shadow-lg bg-white/90 text-slate-900 border border-slate-200';
        if (type === 'success') {
            el.className += ' border-emerald-300 shadow-emerald-200';
        } else if (type === 'error') {
            el.className += ' border-rose-300 shadow-rose-200';
        }
        el.innerText = message || 'Saved';
        root.appendChild(el);
        setTimeout(() => {
            el.classList.add('opacity-0', 'translate-y-1');
            setTimeout(() => el.remove(), 200);
        }, 2200);
    } catch (e) {}
};

window.erpShowNotification = function (message, type = 'info', playSound = false) {
    if (playSound && window.erpNotificationSound) {
        window.erpNotificationSound.play();
    }
    
    const Toast = Swal.mixin({
        toast: true,
        position: document.documentElement.dir === 'rtl' ? 'top-start' : 'top-end',
        showConfirmButton: false,
        timer: 4000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.onmouseenter = Swal.stopTimer;
            toast.onmouseleave = Swal.resumeTimer;
        }
    });
    
    const icons = {
        success: 'success',
        error: 'error',
        warning: 'warning',
        info: 'info'
    };
    
    Toast.fire({
        icon: icons[type] || 'info',
        title: message
    });
};

window.erpPlayNotificationSound = function () {
    if (window.erpNotificationSound) {
        window.erpNotificationSound.play();
    }
};

window.erpToggleNotificationSound = function () {
    if (window.erpNotificationSound) {
        return window.erpNotificationSound.toggle();
    }
    return false;
};

window.erpSetNotificationVolume = function (volume) {
    if (window.erpNotificationSound) {
        window.erpNotificationSound.setVolume(volume);
    }
};

window.erpConfirm = function (options = {}) {
    const defaults = {
        title: 'Are you sure?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#ef4444',
        confirmButtonText: 'Yes, proceed',
        cancelButtonText: 'Cancel'
    };
    
    return Swal.fire({ ...defaults, ...options });
};

window.erpAlert = function (title, text = '', icon = 'info') {
    return Swal.fire({
        title,
        text,
        icon,
        confirmButtonColor: '#10b981'
    });
};

window.erpLoading = function (title = 'Loading...') {
    Swal.fire({
        title,
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
};

window.erpCloseLoading = function () {
    Swal.close();
};

window.erpCreateChart = function (ctx, config) {
    return new Chart(ctx, config);
};

document.addEventListener('livewire:navigated', () => {
    window.erpApplyTheme && window.erpApplyTheme();
});


// Livewire -> Frontend notifications (used across forms/components)
document.addEventListener('livewire:init', () => {
    if (!window.Livewire) return;

    const normalizePayload = (payload, defaultType = 'info') => {
        // Livewire dispatch can arrive as:
        // 1) dispatch('notify', type: 'success', message: '...') => payload is object
        // 2) dispatch('notify', [ 'type' => 'success', 'message' => '...' ]) => payload is array with object at index 0
        // 3) dispatch('notify', 'message') => payload is string
        let data = payload;

        if (Array.isArray(payload) && payload.length && typeof payload[0] === 'object') {
            data = payload[0];
        }

        if (typeof data === 'string') {
            return { type: defaultType, message: data };
        }

        if (typeof data !== 'object' || data === null) {
            return { type: defaultType, message: '' };
        }

        return {
            type: data.type || defaultType,
            message: data.message || data.text || '',
            playSound: data.playSound ?? data.sound ?? false,
        };
    };

    const show = (payload, defaultType = 'info') => {
        const { type, message, playSound } = normalizePayload(payload, defaultType);
        if (!message) return;

        if (typeof window.erpShowNotification === 'function') {
            window.erpShowNotification(message, type, playSound);
        } else if (typeof window.erpShowToast === 'function') {
            window.erpShowToast(message, type);
        } else {
            // Ultimate fallback
            alert(message);
        }
    };

    // Unified notification event (recommended)
    window.Livewire.on('notify', (payload) => show(payload, 'info'));

    // Backwards-compatible common events
    window.Livewire.on('success', (payload) => show(payload, 'success'));
    window.Livewire.on('error', (payload) => show(payload, 'error'));
    window.Livewire.on('warning', (payload) => show(payload, 'warning'));
    window.Livewire.on('info', (payload) => show(payload, 'info'));
});

window.addEventListener('swal:success', event => {
    const playSound = event.detail.playSound ?? false;
    window.erpShowNotification(event.detail.message || 'Success!', 'success', playSound);
});

window.addEventListener('swal:error', event => {
    const playSound = event.detail.playSound ?? false;
    window.erpShowNotification(event.detail.message || 'Error occurred!', 'error', playSound);
});

window.addEventListener('play-notification-sound', () => {
    window.erpPlayNotificationSound();
});

window.addEventListener('swal:confirm', event => {
    window.erpConfirm({
        title: event.detail.title || 'Are you sure?',
        text: event.detail.text || '',
        confirmButtonText: event.detail.confirmText || 'Yes',
        cancelButtonText: event.detail.cancelText || 'Cancel'
    }).then((result) => {
        if (result.isConfirmed && event.detail.callback) {
            window.Livewire.dispatch(event.detail.callback, event.detail.params || {});
        }
    });
});

// Service Worker Registration for Offline Support
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then((registration) => {
                console.log('[ERP] Service Worker registered:', registration.scope);
                
                // Listen for updates
                registration.addEventListener('updatefound', () => {
                    const newWorker = registration.installing;
                    if (newWorker) {
                        newWorker.addEventListener('statechange', () => {
                            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                // New content available, prompt user to refresh
                                if (window.Swal) {
                                    Swal.fire({
                                        title: 'Update Available',
                                        text: 'A new version of HugousERP is available. Would you like to refresh?',
                                        icon: 'info',
                                        showCancelButton: true,
                                        confirmButtonText: 'Refresh',
                                        cancelButtonText: 'Later',
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            newWorker.postMessage({ type: 'SKIP_WAITING' });
                                            window.location.reload();
                                        }
                                    });
                                }
                            }
                        });
                    }
                });
            })
            .catch((error) => {
                console.warn('[ERP] Service Worker registration failed:', error);
            });
        
        // Handle messages from service worker
        navigator.serviceWorker.addEventListener('message', (event) => {
            const { type, timestamp, url } = event.data || {};
            
            if (type === 'SYNC_OFFLINE_SALES') {
                // Trigger offline sales sync
                if (window.Livewire) {
                    window.Livewire.dispatch('sync-offline-sales');
                }
            }
            
            if (type === 'SYNC_OFFLINE_DATA') {
                // Trigger general offline data sync
                if (window.Livewire) {
                    window.Livewire.dispatch('sync-offline-data');
                }
            }
            
            if (type === 'NAVIGATE' && url) {
                // Handle navigation request from service worker
                window.location.href = url;
            }
        });
    });
}

// Offline/Online status indicators
window.addEventListener('online', () => {
    document.body.classList.remove('is-offline');
    if (window.erpShowNotification) {
        window.erpShowNotification('Connection restored', 'success');
    }
    // Trigger sync if registration supports it
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.ready.then((registration) => {
            if (registration.sync) {
                registration.sync.register('sync-offline-data').catch(() => {});
            }
        });
    }
});

window.addEventListener('offline', () => {
    document.body.classList.add('is-offline');
    if (window.erpShowNotification) {
        window.erpShowNotification('You are offline. Some features may be limited.', 'warning');
    }
});

// Utility function to clear service worker cache (for debugging/troubleshooting)
window.erpClearServiceWorkerCache = async function() {
    if ('serviceWorker' in navigator) {
        try {
            const registration = await navigator.serviceWorker.ready;
            if (registration.active) {
                registration.active.postMessage({ type: 'CLEAR_CACHE' });
                console.log('[ERP] Service worker cache cleared');
                if (window.erpShowNotification) {
                    window.erpShowNotification('Cache cleared. Please refresh the page.', 'success');
                }
                // Auto-refresh after a short delay
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            }
        } catch (error) {
            console.error('[ERP] Failed to clear service worker cache:', error);
        }
    }
};

// Global Keyboard Shortcuts
const KeyboardShortcuts = {
    shortcuts: {},
    enabled: true,
    
    init() {
        // Default shortcuts
        this.register('ctrl+s', (e) => {
            e.preventDefault();
            // Find and click the first save button using data attributes or type
            const saveBtn = document.querySelector('button[type="submit"]') || 
                           document.querySelector('[data-action="save"]');
            if (saveBtn) saveBtn.click();
        });
        
        this.register('ctrl+f', (e) => {
            // Focus search input using data attributes or type
            const searchInput = document.querySelector('[data-search-input]') || 
                               document.querySelector('input[type="search"]') ||
                               document.querySelector('[role="searchbox"]');
            if (searchInput) {
                e.preventDefault();
                searchInput.focus();
            }
        });
        
        this.register('ctrl+n', (e) => {
            // New item - find create/add button using data attributes
            const createBtn = document.querySelector('[data-action="create"]') || 
                             document.querySelector('[data-create-button]');
            if (createBtn) {
                e.preventDefault();
                createBtn.click();
            }
        });
        
        this.register('escape', () => {
            // Close modals
            if (window.Swal && Swal.isVisible()) {
                Swal.close();
            }
            // Dispatch to Livewire to close modals
            if (window.Livewire) {
                window.Livewire.dispatch('close-modal');
            }
        });
        
        this.register('f1', (e) => {
            e.preventDefault();
            // Show help dialog
            this.showHelp();
        });
        
        // Listen for keydown events
        document.addEventListener('keydown', (e) => this.handleKeydown(e));
        
        // Load user preferences
        const savedPref = localStorage.getItem('erp_keyboard_shortcuts');
        this.enabled = savedPref !== '0';
    },
    
    register(shortcut, callback) {
        this.shortcuts[shortcut.toLowerCase()] = callback;
    },
    
    unregister(shortcut) {
        delete this.shortcuts[shortcut.toLowerCase()];
    },
    
    handleKeydown(e) {
        if (!this.enabled) return;
        
        // Safety check: ensure e.key exists
        if (!e || !e.key) return;
        
        // Don't intercept when typing in inputs/textareas
        const activeEl = document.activeElement;
        const isEditing = activeEl && (
            activeEl.tagName === 'INPUT' || 
            activeEl.tagName === 'TEXTAREA' || 
            activeEl.isContentEditable
        );
        
        // Build shortcut string
        let shortcut = '';
        if (e.ctrlKey || e.metaKey) shortcut += 'ctrl+';
        if (e.altKey) shortcut += 'alt+';
        if (e.shiftKey) shortcut += 'shift+';
        shortcut += e.key.toLowerCase();
        
        // Allow escape even when editing
        if (shortcut === 'escape') {
            const callback = this.shortcuts[shortcut];
            if (callback) callback(e);
            return;
        }
        
        // Don't process other shortcuts when editing (except ctrl+s)
        if (isEditing && shortcut !== 'ctrl+s') return;
        
        const callback = this.shortcuts[shortcut];
        if (callback) {
            callback(e);
        }
    },
    
    toggle() {
        this.enabled = !this.enabled;
        localStorage.setItem('erp_keyboard_shortcuts', this.enabled ? '1' : '0');
        return this.enabled;
    },
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },
    
    showHelp() {
        // Create container for better organization
        const container = document.createElement('div');
        container.className = 'text-left';
        
        // Active shortcuts section
        const activeSection = document.createElement('div');
        activeSection.className = 'mb-4';
        const activeTitle = document.createElement('h4');
        activeTitle.className = 'text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2';
        activeTitle.textContent = 'Active Shortcuts / اختصارات نشطة';
        activeSection.appendChild(activeTitle);
        
        const activeTable = document.createElement('table');
        activeTable.className = 'w-full text-sm';
        const activeTbody = document.createElement('tbody');
        
        const activeShortcuts = [
            { key: 'Ctrl + S', action: 'Save / حفظ' },
            { key: 'Ctrl + F', action: 'Search / بحث' },
            { key: 'Ctrl + N', action: 'New Item / إضافة جديد' },
            { key: 'Ctrl + K', action: 'Command Palette / لوحة الأوامر' },
            { key: 'Escape', action: 'Close Modal / إغلاق' },
            { key: 'F1', action: 'Help / مساعدة' }
        ];
        
        activeShortcuts.forEach(s => {
            const tr = document.createElement('tr');
            tr.className = 'border-b border-gray-200 dark:border-gray-700';
            
            const tdKey = document.createElement('td');
            tdKey.className = 'py-2 px-3';
            const kbd = document.createElement('kbd');
            kbd.className = 'px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono';
            kbd.textContent = s.key;
            tdKey.appendChild(kbd);
            
            const tdAction = document.createElement('td');
            tdAction.className = 'py-2 px-3 text-gray-600 dark:text-gray-400';
            tdAction.textContent = s.action;
            
            tr.appendChild(tdKey);
            tr.appendChild(tdAction);
            activeTbody.appendChild(tr);
        });
        
        activeTable.appendChild(activeTbody);
        activeSection.appendChild(activeTable);
        container.appendChild(activeSection);
        
        // POS shortcuts section (only active in POS terminal)
        const posSection = document.createElement('div');
        const posTitle = document.createElement('h4');
        posTitle.className = 'text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2';
        posTitle.textContent = 'POS Terminal Only / فقط في نقطة البيع';
        posSection.appendChild(posTitle);
        
        const posTable = document.createElement('table');
        posTable.className = 'w-full text-sm';
        const posTbody = document.createElement('tbody');
        
        const posShortcuts = [
            { key: 'F2', action: 'Focus Quantity / الكمية' },
            { key: 'F4', action: 'Apply Discount / خصم' },
            { key: 'F8', action: 'Clear Cart / مسح' },
            { key: 'F12', action: 'Complete Sale / إتمام البيع' }
        ];
        
        posShortcuts.forEach(s => {
            const tr = document.createElement('tr');
            tr.className = 'border-b border-gray-200 dark:border-gray-700';
            
            const tdKey = document.createElement('td');
            tdKey.className = 'py-2 px-3';
            const kbd = document.createElement('kbd');
            kbd.className = 'px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono';
            kbd.textContent = s.key;
            tdKey.appendChild(kbd);
            
            const tdAction = document.createElement('td');
            tdAction.className = 'py-2 px-3 text-gray-600 dark:text-gray-400';
            tdAction.textContent = s.action;
            
            tr.appendChild(tdKey);
            tr.appendChild(tdAction);
            posTbody.appendChild(tr);
        });
        
        posTable.appendChild(posTbody);
        posSection.appendChild(posTable);
        container.appendChild(posSection);
        
        Swal.fire({
            title: 'Keyboard Shortcuts / اختصارات لوحة المفاتيح',
            html: container,
            icon: 'info',
            confirmButtonText: 'OK',
            width: '450px'
        });
    }
};

KeyboardShortcuts.init();
window.erpKeyboardShortcuts = KeyboardShortcuts;


// ------------------------------------------------------------
// Global JS error boundary (helps catch silent frontend bugs)
// ------------------------------------------------------------
(function () {
    const THROTTLE_MS = 8000;
    let lastAt = 0;

    const defaultMsg = (() => {
        const lang = (document.documentElement.getAttribute('lang') || '').toLowerCase();
        // Simple bilingual default (system supports i18n but this runs before translations sometimes)
        if (lang.startsWith('ar')) {
            return 'حدث خطأ غير متوقع. حاول تحديث الصفحة.';
        }
        return 'An unexpected error occurred. Please refresh the page.';
    })();

    const notify = (userMessage, debugMessage = null) => {
        const now = Date.now();
        if (now - lastAt < THROTTLE_MS) return;
        lastAt = now;

        // Show user-friendly message
        if (window.erpShowToast) {
            window.erpShowToast(userMessage, { type: 'error' });
        } else if (window.erpShowNotification) {
            window.erpShowNotification(userMessage, 'error');
        } else {
            // Fallback: keep console signal even if toast system isn't ready
            console.error('[ERP] ' + userMessage);
        }

        // Always log the technical detail for developers
        if (debugMessage) {
            console.error('[ERP] Frontend error detail:', debugMessage);
        }
    };

    // Capture synchronous errors
    window.addEventListener('error', (event) => {
        // Ignore resource loading errors (<img>, <script>, <link>) to avoid noisy toasts
        const target = event?.target;
        const tag = target?.tagName;
        if (tag && ['IMG', 'SCRIPT', 'LINK'].includes(tag)) return;

        notify(defaultMsg, event?.error || event?.message || event);
    }, true);

    // Capture unhandled promise rejections
    window.addEventListener('unhandledrejection', (event) => {
        notify(defaultMsg, event?.reason || event);
    });
})();
