<div class="w-full flex items-center justify-between px-4 py-3 border-b border-slate-200 dark:border-slate-700 bg-white/80 dark:bg-slate-900/80 backdrop-blur">
    <div class="font-semibold text-slate-800 dark:text-slate-100">
        {{ __('Customer Portal') }}
    </div>

    <div class="flex items-center gap-3 text-sm">
        <a href="{{ route('portal.dashboard') }}" class="text-slate-700 dark:text-slate-200 hover:underline">{{ __('Dashboard') }}</a>
        <a href="{{ route('portal.orders') }}" class="text-slate-700 dark:text-slate-200 hover:underline">{{ __('Orders') }}</a>
        <a href="{{ route('portal.profile') }}" class="text-slate-700 dark:text-slate-200 hover:underline">{{ __('Profile') }}</a>
        <a href="{{ route('portal.loyalty') }}" class="text-slate-700 dark:text-slate-200 hover:underline">{{ __('Loyalty') }}</a>

        <form method="POST" action="{{ route('portal.logout') }}">
            @csrf
            <button type="submit" class="px-3 py-1 rounded-lg bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900">
                {{ __('Logout') }}
            </button>
        </form>
    </div>
</div>
