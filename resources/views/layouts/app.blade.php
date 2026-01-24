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
            background-color: #f8fafc;
            overflow-x: hidden;
            padding: env(safe-area-inset-top) 0 env(safe-area-inset-bottom);
        }

        img, svg, video, canvas { max-width: 100%; height: auto; object-fit: contain; }
        main, .content-container, .erp-card { width: 100%; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.25rem; }
        .toolbar-wrap { flex-wrap: wrap; row-gap: 0.5rem; }
        button, input, select, textarea { max-width: 100%; }

        /* Performance optimizations */
        .erp-card, .sidebar-link, table {
            contain: content;
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
    {{-- New Sidebar (includes overlay) - Persist across navigation for smoother UX --}}
    {{-- Dynamic persist key includes locale and branch context to ensure sidebar refreshes when these change --}}
    @persist('sidebar-'.app()->getLocale().'-'.(session('admin_branch_context') ?? session('selected_branch_id') ?? 'default'))
    @includeIf('layouts.sidebar-new')
    @endpersist

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

        <footer class="border-t border-emerald-100/60 bg-white/80 backdrop-blur py-3 text-xs text-slate-500">
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
                });
            });
        });
    })();
    
    // Handle export downloads - triggered from Livewire components
    document.addEventListener('livewire:init', () => {
        Livewire.on('trigger-download', (params) => {
            console.log('Export download event received:', params);
            
            // Extract URL from various possible formats
            // Livewire v3 sends named parameters as object properties
            let url = null;
            if (typeof params === 'string') {
                url = params;
            } else if (params && typeof params === 'object') {
                // Try different possible formats
                url = params.url || params[0]?.url || params[0];
            }
            
            console.log('Extracted URL:', url);
            
            if (url) {
                // Create a temporary anchor element to trigger download
                // This method is more reliable than iframe for downloads
                const link = document.createElement('a');
                link.href = url;
                link.style.display = 'none';
                // Browser will use the filename from the Content-Disposition header
                document.body.appendChild(link);
                
                // Trigger the download
                link.click();
                
                // Clean up after a short delay
                setTimeout(() => {
                    if (document.body.contains(link)) {
                        document.body.removeChild(link);
                    }
                }, 100);
                
                console.log('Export download triggered successfully');
            } else {
                console.error('No URL found in export download event:', params);
            }
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
    // Intelligent prefetching - preload links on hover
    // This function is called on initial load and after Livewire Navigate
    (function() {
        const prefetchedUrls = new Set();
        const MAX_PREFETCHES = 20; // Limit to prevent memory issues
        
        function initPrefetching() {
            document.querySelectorAll('a[href^="/"]').forEach(link => {
                // Skip links that already have prefetch listener
                if (link.dataset.prefetchInit) return;
                link.dataset.prefetchInit = 'true';
                
                link.addEventListener('mouseenter', function() {
                    const href = this.getAttribute('href');
                    if (href && !prefetchedUrls.has(href) && !href.includes('#') && prefetchedUrls.size < MAX_PREFETCHES) {
                        prefetchedUrls.add(href);
                        const prefetch = document.createElement('link');
                        prefetch.rel = 'prefetch';
                        prefetch.href = href;
                        document.head.appendChild(prefetch);
                        
                        // Remove prefetch link after 30 seconds to free memory
                        setTimeout(() => {
                            if (prefetch.parentNode) {
                                prefetch.remove();
                            }
                        }, 30000);
                    }
                }, { once: true, passive: true });
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
