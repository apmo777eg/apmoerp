@extends('layouts.guest')

@section('title', __('My Profile'))

@section('content')
@include('portal.partials.nav')

<div class="max-w-4xl mx-auto p-4 space-y-6">
    <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100">{{ __('My Profile') }}</h2>

    @if (session('success'))
        <div class="p-3 rounded-lg bg-emerald-50 text-emerald-900 dark:bg-emerald-900/30 dark:text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="p-3 rounded-lg bg-red-50 text-red-800 dark:bg-red-900/30 dark:text-red-200">
            <ul class="text-sm list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="erp-card p-4">
        <h3 class="font-semibold text-slate-900 dark:text-slate-100 mb-3">{{ __('Profile Details') }}</h3>

        <form method="POST" action="{{ route('portal.profile.update') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">{{ __('Name') }}</label>
                <input name="name" value="{{ old('name', $customer->name) }}" required
                    class="mt-1 w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">{{ __('Email') }}</label>
                <input type="email" name="email" value="{{ old('email', $customer->email) }}" required
                    class="mt-1 w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">{{ __('Phone') }}</label>
                <input name="phone" value="{{ old('phone', $customer->phone) }}"
                    class="mt-1 w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">{{ __('Address') }}</label>
                <input name="address" value="{{ old('address', $customer->address) }}"
                    class="mt-1 w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
            </div>

            <div class="md:col-span-2">
                <button type="submit" class="erp-btn-primary">{{ __('Save') }}</button>
            </div>
        </form>
    </div>

    <div class="erp-card p-4">
        <h3 class="font-semibold text-slate-900 dark:text-slate-100 mb-3">{{ __('Change Password') }}</h3>

        <form method="POST" action="{{ route('portal.password.change') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">{{ __('Current Password') }}</label>
                <input type="password" name="current_password" required
                    class="mt-1 w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">{{ __('New Password') }}</label>
                <input type="password" name="new_password" required
                    class="mt-1 w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">{{ __('Confirm New Password') }}</label>
                <input type="password" name="new_password_confirmation" required
                    class="mt-1 w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
            </div>

            <div class="md:col-span-2">
                <button type="submit" class="erp-btn-primary">{{ __('Update Password') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
