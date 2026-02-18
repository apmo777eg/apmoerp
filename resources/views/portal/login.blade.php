@extends('layouts.guest')

@section('title', __('Customer Portal'))

@section('content')
<div class="w-full max-w-md mx-auto">
    <div class="text-center mb-6">
        <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ __('Customer Portal') }}</h1>
        <p class="text-sm text-slate-600 dark:text-slate-300">{{ __('Sign in to view your orders and invoices.') }}</p>
    </div>

    @if ($errors->any())
        <div class="mb-4 p-3 rounded-lg bg-red-50 text-red-800 dark:bg-red-900/30 dark:text-red-200">
            <ul class="text-sm list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('success'))
        <div class="mb-4 p-3 rounded-lg bg-emerald-50 text-emerald-900 dark:bg-emerald-900/30 dark:text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="erp-card p-6">
        <form method="POST" action="{{ route('portal.authenticate') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">{{ __('Email') }}</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                    class="mt-1 w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">{{ __('Password') }}</label>
                <input type="password" name="password" required
                    class="mt-1 w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
            </div>

            <button type="submit" class="w-full erp-btn-primary">
                {{ __('Sign in') }}
            </button>
        </form>
    </div>
</div>
@endsection
