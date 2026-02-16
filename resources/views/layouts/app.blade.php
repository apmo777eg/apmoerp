{{-- resources/views/layouts/app.blade.php --}}
@php
    $locale = app()->getLocale();
    $dir = $locale === 'ar' ? 'rtl' : 'ltr';
    // CRIT-UI-01 FIX: Use data_get() instead of object access since preferences is cast as 'array'
    $userTheme = auth()->check()
        ? data_get(auth()->user()->preferences, 'theme', 'light')
        : 'light';
    $isDark = $userTheme === 'dark' || ($userTheme === 'system' && request()->cookie('theme') === 'dark');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $dir }}" class="h-full antialiased {{ $isDark ? 'dark' : '' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', config('app.name', 'Ghanem ERP'))</title>

    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    {{-- PWA Meta Tags --}}
    <meta name="theme-color" content="#10b981">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="{{ config('app.name', 'HugousERP') }}">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/icons/icon-192x192.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    {{-- Livewire 4 handles SPA-like navigation natively with wire:navigate --}}

    <style>
        * { font-family: 'Cairo', 'Segoe UI', Tahoma, sans-serif !important; }

        html { scroll-behavior: smooth; }
        body {
            min-height: 100vh;
            overflow-x: hidden;
            padding: env(safe-area-inset-top) 0 env(safe-area-inset-bottom);
        }

        img, svg, video, canvas { max-width: 100%; height: auto; object-fit: contain; }
        main, .content-container, .erp-card { width: 100%; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.25rem; }
        .toolbar-wrap { flex-wrap: wrap; row-gap: 0.5rem; }
        button, input, select, textarea { max-width: 100%; }

        /* Performance optimizations */
        /* NOTE: Avoid paint containment on `.erp-card` to prevent clipping dropdowns/tooltips (e.g. icon pickers). */
        .sidebar-link, table {
            contain: content;
        }
        .erp-card {
            contain: layout style;
        }
        
        /* Hardware acceleration for animations */
        .sidebar-link, .erp-card, button, a {
            transform: translateZ(0);
            will-change: transform, opacity;
        }
        
        /* Smooth transitions */
        * {
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        /* Livewire Navigate loading indicator */
        .livewire-progress-bar {
            height: 3px;
            background: linear-gradient(to right, #10b981, #3b82f6);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 9999;
            transform-origin: left;
            transition: transform 0.3s ease;
        }
        
        /* Responsive improvements */
        @media (max-width: 768px) {
            .responsive-table {
                overflow-x-auto;
                -webkit-overflow-scrolling: touch;
            }
        }
    </style>

    <script>
        // Theme initialization
        (function() {
            const theme = localStorage.getItem('theme') || '{{ $userTheme }}';
            if (theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        })();
        
        window.Laravel = {
            @if(auth()->check())
                userId: {{ auth()->id() }},
            @else
                userId: null,
            @endif
        };
    </script>

    @livewireStyles
</head>
<body class="h-full text-[15px] sm:text-base"
      x-data="{ sidebarOpen: false }"
      @keydown.escape.window="sidebarOpen = false">

{{-- Main Layout Container with new sidebar --}}
<div class="erp-layout">
    {{-- New Sidebar (includes overlay) --}}
    {{-- NOTE: We intentionally do NOT persist the sidebar across navigation.
         Persisting can cause stale/incorrect menus after branch switches and
         can break Alpine state on some browsers/PWA contexts.
         Keeping it server-rendered guarantees correct permissions + branch menus. --}}
    @includeIf('layouts.sidebar-new')

    {{-- Main Content Wrapper --}}
    <div class="erp-main-wrapper">

        {{-- Navbar --}}
        @includeIf('layouts.navbar')

        <main class="flex-1 w-full overflow-x-hidden">
            <div class="content-container mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8 py-4 space-y-4">

                @hasSection('page-header')
                    @php
                        $routeName = \Illuminate\Support\Facades\Route::currentRouteName();
                        $routePermissions = [
                            'dashboard'                 => config('screen_permissions.dashboard', 'dashboard.view'),
                            'pos.terminal'              => config('screen_permissions.pos.terminal', 'pos.use'),
                            'pos.offline.report'        => 'pos.offline.report.view',
                            'admin.users.index'         => config('screen_permissions.admin.users.index', 'users.manage'),
                            'admin.users.create'        => config('screen_permissions.admin.users.index', 'users.manage'),
                            'admin.users.edit'          => config('screen_permissions.admin.users.index', 'users.manage'),
                            'admin.branches.index'      => config('screen_permissions.admin.branches.index', 'branches.view'),
                            'admin.branches.create'     => config('screen_permissions.admin.branches.index', 'branches.view'),
                            'admin.branches.edit'       => config('screen_permissions.admin.branches.index', 'branches.view'),
                            'admin.settings.system'     => config('screen_permissions.admin.settings.system', 'settings.view'),
                            'admin.settings.branch'     => config('screen_permissions.admin.settings.branch', 'settings.branch'),
                            'notifications.center'      => config('screen_permissions.notifications.center', 'system.view-notifications'),
                            'inventory.products.index'  => config('screen_permissions.inventory.products.index', 'inventory.products.view'),
                            'inventory.products.create' => config('screen_permissions.inventory.products.index', 'inventory.products.view'),
                            'inventory.products.edit'   => config('screen_permissions.inventory.products.index', 'inventory.products.view'),
                            'hrm.reports.dashboard'     => config('screen_permissions.hrm.reports.dashboard', 'hr.view-reports'),
                            'rental.reports.dashboard'  => config('screen_permissions.rental.reports.dashboard', 'rental.view-reports'),
                            'admin.logs.audit'          => config('screen_permissions.logs.audit', 'logs.audit.view'),
                        ];
                        $requiredPermission = $routePermissions[$routeName] ?? null;
                    @endphp
                    <div class="flex items-center justify-between gap-3 toolbar-wrap w-full">
                        <div class="flex flex-col gap-1 w-full sm:w-auto">
                            @yield('page-header')
                        </div>
                        <div class="flex items-center gap-2 toolbar-wrap justify-end">
                            @if ($requiredPermission)
                                <span class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-[11px] font-medium text-slate-600">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                    <span>can:{{ $requiredPermission }}</span>
                                </span>
                            @endif
                            @yield('page-actions')
                        </div>
                    </div>
                @else

                    <div class="flex items-center justify-between gap-3 toolbar-wrap w-full">
                        <div class="flex flex-col gap-1 w-full sm:w-auto">
                            @yield('page-header')
                        </div>
                        @yield('page-actions')
                    </div>
                @endif

                @if (session('status'))
                    <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800 shadow-sm shadow-emerald-500/20">
                        {{ session('status') }}
                    </div>
                @endif

                @if (session('success'))
                    <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800 shadow-sm shadow-emerald-500/20">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800 shadow-sm">
                        <ul class="list-disc ms-4 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="erp-card p-4 sm:p-6">
                    {{ $slot ?? '' }}
                    @yield('content')
                </div>
            </div>
        </main>

        <footer class="border-t border-emerald-100/60 dark:border-slate-700/60 bg-white/80 dark:bg-slate-900/80 backdrop-blur py-3 text-xs text-slate-500 dark:text-slate-400">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 flex items-center justify-between gap-2 flex-wrap w-full">
                <span>&copy; {{ date('Y') }} {{ config('app.name', 'Ghanem ERP') }}</span>
                <span class="hidden sm:inline">
                    {{ __('Powered by Laravel & Livewire') }}
                </span>
            </div>
        </footer>
    </div>
</div>

@livewireScripts
@stack('scripts')

<script>
    // Unified Session Expired Handler
    // Single source of truth for 419/401 error handling across Livewire, Axios, and fetch
    (function() {
        // Global handler for session expired errors
        window.erpHandleSessionExpired = function(status) {
            if (status === 419) {
                // Use Livewire.navigate for SPA-friendly refresh if available
                if (window.Livewire && typeof Livewire.navigate === 'function') {
                    Livewire.navigate(window.location.href);
                } else {
                    window.location.reload();
                }
            } else if (status === 401) {
                window.location.href = '/login';
            }
        };
        
        const updateCsrfToken = (token) => {
            // Update meta tag
            const metaTag = document.querySelector('meta[name="csrf-token"]');
            if (metaTag) {
                metaTag.setAttribute('content', token);
            }
            
            // Update axios default header if available
            if (window.axios) {
                window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
            }
        };
        
        const refreshCsrfToken = async () => {
            try {
                const response = await fetch('/csrf-token', {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin'
                });
                
                if (response.ok) {
                    const data = await response.json();
                    if (data.csrf_token) {
                        updateCsrfToken(data.csrf_token);
                        @if(config('app.debug'))
                        console.log('[CSRF] Token refreshed successfully');
                        @endif
                    }
                } else if (response.status === 401) {
                    window.erpHandleSessionExpired(401);
                }
            } catch (error) {
                @if(config('app.debug'))
                console.error('[CSRF] Error refreshing token:', error);
                @endif
            }
        };
        
        // Refresh token every 30 minutes (1800000 ms)
        setInterval(refreshCsrfToken, 30 * 60 * 1000);
        
        // Also refresh on page visibility change (user comes back to tab)
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                refreshCsrfToken();
            }
        });
        
        // Axios interceptor for 419/401 handling
        if (window.axios) {
            window.axios.interceptors.response.use(
                response => response,
                error => {
                    if (error.response && (error.response.status === 419 || error.response.status === 401)) {
                        window.erpHandleSessionExpired(error.response.status);
                        return Promise.reject(error);
                    }
                    return Promise.reject(error);
                }
            );
        }
        
        // Livewire 4: Unified error handling using commit hooks
        document.addEventListener('livewire:init', () => {
            // Track 500 errors to prevent infinite refresh loops
            let serverErrorCount = 0;
            const MAX_SERVER_ERRORS = 3;
            const ERROR_RESET_MS = 30000; // Reset counter after 30 seconds
            let lastServerErrorToastAt = 0;
            const ERROR_TOAST_THROTTLE_MS = 8000; // Avoid spamming the user
            
            Livewire.hook('commit', ({ fail }) => {
                fail(({ status, preventDefault }) => {
                    if (status === 419 || status === 401) {
                        preventDefault();
                        window.erpHandleSessionExpired(status);
                    }
                    // Handle 403 Forbidden - redirect to dashboard with message
                    if (status === 403) {
                        preventDefault();
                        if (window.erpShowNotification) {
                            window.erpShowNotification('{{ __("You do not have permission to perform this action.") }}', 'error');
                        }
                        // Redirect to dashboard after a short delay
                        setTimeout(() => {
                            if (window.Livewire && typeof Livewire.navigate === 'function') {
                                Livewire.navigate('{{ route("dashboard") }}');
                            } else {
                                window.location.href = '{{ route("dashboard") }}';
                            }
                        }, 1500);
                    }
                    // Handle 500 Server Error - prevent infinite refresh loops
                    if (status === 500) {
                        preventDefault();
                        serverErrorCount++;
                        clearTimeout(window.__erpErrorResetTimer);
                        window.__erpErrorResetTimer = setTimeout(() => { serverErrorCount = 0; }, ERROR_RESET_MS);

                        // Notify user (throttled)
                        const now = Date.now();
                        if (now - lastServerErrorToastAt > ERROR_TOAST_THROTTLE_MS) {
                            lastServerErrorToastAt = now;
                            if (window.erpShowNotification) {
                                window.erpShowNotification('{{ __("Server error. Please try again.") }}', 'error');
                            } else if (window.erpShowToast) {
                                window.erpShowToast('{{ __("Server error. Please try again.") }}', { type: 'error' });
                            }
                        }

                        if (serverErrorCount < MAX_SERVER_ERRORS) {
                            @if(config('app.debug'))
                            console.error('[ERP] Server error (500) on Livewire request. Error count:', serverErrorCount);
                            @endif
                        } else {
                            // After repeated failures, suggest a full reload.
                            if (window.erpShowNotification) {
                                window.erpShowNotification('{{ __("Multiple server errors detected. Please reload the page.") }}', 'error');
                            } else if (window.erpShowToast) {
                                window.erpShowToast('{{ __("Multiple server errors detected. Please reload the page.") }}', { type: 'error' });
                            }
                            @if(config('app.debug'))
                            console.error('[ERP] Too many server errors. Stopping automatic retries.');
                            @endif
                        }
                    }
                    }
                });
            });
        });
    })();
    
    // Handle export downloads - triggered from Livewire components
    document.addEventListener('livewire:init', () => {
        Livewire.on('trigger-download', (params) => {
            // Extract URL from various possible formats
            // Livewire v3/v4 sends named parameters as object properties
            let url = null;

            if (typeof params === 'string') {
                url = params;
            } else if (params && typeof params === 'object') {
                url = params.url || params[0]?.url || params[0];
            }

            if (!url) {
                @if(config('app.debug'))
                console.error('[ERP] Export download failed: no URL found in event payload', params);
                @endif
                return;
            }

            // Trigger download without leaving the current page.
            // Using a hidden iframe is generally more reliable across browsers for programmatic downloads
            // (and avoids popup blockers compared to window.open).
            const iframe = document.createElement('iframe');
            iframe.style.display = 'none';
            iframe.src = url;
            document.body.appendChild(iframe);

            // Cleanup after a minute (download may take time on slow connections)
            setTimeout(() => {
                try {
                    iframe.remove();
                } catch (e) {}
            }, 60000);
        });
    });

// Handle theme changes from UserPreferences
    document.addEventListener('livewire:init', () => {
        Livewire.on('theme-changed', (event) => {
            const theme = event.theme || event[0]?.theme || event[0];
            if (theme) {
                localStorage.setItem('theme', theme);
                
                if (theme === 'dark') {
                    document.documentElement.classList.add('dark');
                } else if (theme === 'light') {
                    document.documentElement.classList.remove('dark');
                } else if (theme === 'system') {
                    if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                        document.documentElement.classList.add('dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                    }
                }
            }
        });
    });

    // Note: 419 session expiry is handled in the Unified Session Expired Handler section above.
</script>

    <div id="erp-toast-root" class="toast-container flex flex-col items-end justify-start px-4 py-6 space-y-2 inset-inline-end-0 inset-block-start-0 inset-inline-start-auto inset-block-end-auto"></div>

{{-- Loading indicator for Livewire Navigate --}}
<div id="page-loading" class="livewire-progress-bar" style="display:none;transform:scaleX(0);"></div>

<script>
    // Intelligent prefetching - preload likely-next pages on hover/touch.
    // IMPORTANT:
    // - Prefetching *all* internal links can create unnecessary load on the server.
    // - We only prefetch links that use Livewire navigation (wire:navigate).
    // - We also respect network conditions (save-data / slow connections).
    (function() {
        const prefetchedUrls = new Set();
        const MAX_PREFETCHES = 20; // Limit to prevent memory issues
        const SKIP_PREFIXES = [
            // Avoid prefetching heavy/side-effect pages
            '/pos',
            '/admin/backup',
            '/admin/reports/export',
            '/admin/exports',
            '/download',
        ];

        function canPrefetch() {
            if (!navigator.onLine) return false;
            const conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
            if (!conn) return true;
            if (conn.saveData) return false;
            const type = (conn.effectiveType || '').toLowerCase();
            if (type.includes('2g')) return false;
            return true;
        }

        function shouldSkip(href) {
            if (!href) return true;
            if (href.includes('#')) return true;
            if (prefetchedUrls.has(href)) return true;
            if (prefetchedUrls.size >= MAX_PREFETCHES) return true;
            return SKIP_PREFIXES.some((p) => href.startsWith(p));
        }

        function doPrefetch(href) {
            if (!canPrefetch() || shouldSkip(href)) return;

            prefetchedUrls.add(href);
            const prefetch = document.createElement('link');
            prefetch.rel = 'prefetch';
            // Hint to the browser what we are prefetching (safe even if ignored)
            prefetch.as = 'document';
            prefetch.href = href;
            document.head.appendChild(prefetch);

            // Remove prefetch link after 30 seconds to free memory
            setTimeout(() => {
                if (prefetch.parentNode) {
                    prefetch.remove();
                }
            }, 30000);
        }
        
        function initPrefetching() {
            // Only prefetch Livewire-navigated links.
            // (The attribute name contains ':' which needs escaping in querySelector)
            document.querySelectorAll('a[wire\\:navigate][href^="/"]').forEach(link => {
                // Skip links that already have prefetch listener
                if (link.dataset.prefetchInit) return;
                link.dataset.prefetchInit = 'true';

                const handler = function() {
                    const href = this.getAttribute('href');
                    // Defer work to idle time to keep hover interactions snappy
                    if (window.requestIdleCallback) {
                        requestIdleCallback(() => doPrefetch(href), { timeout: 500 });
                    } else {
                        setTimeout(() => doPrefetch(href), 0);
                    }
                };

                link.addEventListener('mouseenter', handler, { once: true, passive: true });
                // Mobile: prefetch on touchstart
                link.addEventListener('touchstart', handler, { once: true, passive: true });
            });
        }
        
        // Initialize on DOMContentLoaded
        document.addEventListener('DOMContentLoaded', initPrefetching);
        
        // Re-initialize after Livewire Navigate completes
        document.addEventListener('livewire:navigated', initPrefetching);
    })();
    
    // Livewire Navigate loading indicator
    document.addEventListener('livewire:navigating', () => {
        const loader = document.getElementById('page-loading');
        if (loader) {
            loader.style.display = 'block';
            loader.style.transform = 'scaleX(0.3)';
        }
    });
    
    document.addEventListener('livewire:navigated', () => {
        const loader = document.getElementById('page-loading');
        if (loader) {
            loader.style.transform = 'scaleX(1)';
            setTimeout(() => {
                loader.style.display = 'none';
                loader.style.transform = 'scaleX(0)';
            }, 200);
        }
        
        // Re-initialize UI scripts on navigation
        if (window.erpApplyTheme) {
            window.erpApplyTheme();
        }
    });
</script>
    
</body>
</html>
