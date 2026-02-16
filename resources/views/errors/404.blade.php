<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Page Not Found') }} - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="h-screen flex items-center justify-center bg-gradient-to-br from-slate-50 to-slate-100">
    <div class="max-w-md w-full px-6">
        <div class="text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-amber-100 mb-4">
                <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            
            <h1 class="text-6xl font-bold text-slate-800 mb-2">404</h1>
            <h2 class="text-2xl font-semibold text-slate-700 mb-4">{{ __('Page Not Found') }}</h2>
            <p class="text-slate-600 mb-8">
                {{ __('The page you\'re looking for doesn\'t exist or has been moved.') }}
            </p>

            <div class="space-y-3">
                @if(Route::has('dashboard'))
                    <a href="{{ route('dashboard') }}" 
                   class="inline-flex items-center justify-center w-full px-6 py-3 text-base font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors shadow-lg shadow-emerald-500/30">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    {{ __('Back to Dashboard') }}
                    </a>
                @endif
                
                <button type="button" onclick="history.back()" 
                        class="inline-flex items-center justify-center w-full px-6 py-3 text-base font-medium text-slate-700 bg-white hover:bg-slate-50 rounded-lg transition-colors border border-slate-200">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    {{ __('Go Back') }}
                </button>
            </div>

            @php
                $requestId = app()->bound('req.id') ? app('req.id') : request()->headers->get('X-Request-Id');
                $correlationId = app()->bound('req.correlation_id') ? app('req.correlation_id') : request()->headers->get('X-Correlation-Id');
            @endphp
            @if($requestId || $correlationId)
                <p class="mt-6 text-xs text-slate-500">
                    @if($requestId)
                        <span>{{ __('Request ID') }}: <span class="font-mono">{{ $requestId }}</span></span>
                    @endif
                    @if($correlationId)
                        <span class="ms-2">{{ __('Correlation ID') }}: <span class="font-mono">{{ $correlationId }}</span></span>
                    @endif
                </p>
            @endif
        </div>
    </div>
</body>
</html>
