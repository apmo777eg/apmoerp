<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col gap-1">
        <h1 class="text-lg sm:text-xl font-semibold text-slate-800 dark:text-slate-100">
            {{ __('System Settings') }}
        </h1>
        <p class="text-sm text-slate-500 dark:text-slate-400">
            {{ __('Global settings, permissions overview & access map.') }}
        </p>
    </div>

    {{-- Content --}}
    <div class="grid gap-6 lg:grid-cols-2">

        {{-- Left: Key/Value system settings --}}
        <div class="space-y-4">
            {{-- Developer Warning --}}
            <div class="rounded-2xl border border-amber-300 bg-amber-50 dark:bg-amber-900/30 dark:border-amber-600/50 p-4">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div>
                        <h3 class="text-sm font-semibold text-amber-800 dark:text-amber-200">{{ __('Developer/Super-Admin Only') }}</h3>
                        <p class="text-xs text-amber-700 dark:text-amber-300 mt-1">
                            {{ __('This page allows direct key/value editing of system settings. For most configuration needs, please use the') }}
                            <a href="{{ route('admin.settings') }}" class="underline font-medium hover:text-amber-900 dark:hover:text-amber-100">{{ __('Unified Settings') }}</a>
                            {{ __('page instead which provides a user-friendly interface.') }}
                        </p>
                        <p class="text-xs text-amber-600 dark:text-amber-400 mt-2">
                            {{ __('Incorrect key names or values here may cause unexpected system behavior.') }}
                        </p>
                    </div>
                </div>
            </div>

            <div
                class="relative rounded-2xl border border-slate-200/80 dark:border-slate-700/80 bg-gradient-to-br from-white via-slate-50 to-slate-100 dark:from-slate-900 dark:via-slate-900/90 dark:to-slate-950 shadow-sm">
                <div class="border-b border-slate-200/70 dark:border-slate-700/70 px-4 py-3 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-800 dark:text-slate-100">
                            {{ __('Key/value settings') }}
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            {{ __('Simple global configuration entries.') }}
                        </p>
                    </div>
                    <button type="button"
                            wire:click="addRow"
                            aria-label="{{ __('Add new setting row') }}"
                            class="inline-flex items-center rounded-full border border-emerald-500/70 bg-emerald-500/90 px-3 py-1 text-xs font-medium text-white shadow hover:bg-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:ring-offset-2 focus:ring-offset-slate-100 dark:focus:ring-offset-slate-900">
                        <span class="me-1">+</span>
                        <span>{{ __('Add row') }}</span>
                    </button>
                </div>

                <div class="px-4 py-3">
                    <form wire:submit.prevent="save" class="space-y-3">

                        @if($errors->any())
                            <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700 shadow-sm dark:border-red-700/70 dark:bg-red-900/40 dark:text-red-50">
                                {{ __('Please fix the highlighted setting keys before saving.') }}
                            </div>
                        @endif

                        <div class="border border-slate-200/70 dark:border-slate-700/70 rounded-xl overflow-hidden bg-white/80 dark:bg-slate-900/80">
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700 text-xs sm:text-sm">
                                <thead class="bg-slate-50/90 dark:bg-slate-800/70">
                                    <tr>
                                        <th class="px-3 py-2 text-start font-semibold text-slate-500 dark:text-slate-300">
                                            {{ __('Key') }}
                                        </th>
                                        <th class="px-3 py-2 text-start font-semibold text-slate-500 dark:text-slate-300">
                                            {{ __('Value') }}
                                        </th>
                                        <th class="px-3 py-2 text-start font-semibold text-slate-500 dark:text-slate-300">
                                            {{ __('Group') }}
                                        </th>
                                        <th class="px-3 py-2 text-center font-semibold text-slate-500 dark:text-slate-300">
                                            {{ __('Public?') }}
                                        </th>
                                        <th class="px-3 py-2 text-end font-semibold text-slate-500 dark:text-slate-300">
                                            {{ __('Actions') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white/80 dark:bg-slate-950/40">
                                    @forelse ($rows as $index => $row)
                                        <tr class="hover:bg-emerald-50/40 dark:hover:bg-slate-800/50 transition-colors">
                                            <td class="px-3 py-2 align-top">
                                                <input type="text"
                                                       wire:model="rows.{{ $index }}.key"
                                                       placeholder="app.locale"
                                                       class="w-full rounded-md border border-slate-200/80 dark:border-slate-700/70 bg-white/80 dark:bg-slate-900/80 px-2 py-1 text-xs sm:text-sm text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                                                @error("rows.$index.key")
                                                    <p class="mt-1 text-[11px] font-medium text-red-600 dark:text-red-300">{{ $message }}</p>
                                                @enderror
                                            </td>
                                            <td class="px-3 py-2 align-top">
                                                <input type="text"
                                                       wire:model="rows.{{ $index }}.value"
                                                       placeholder="value"
                                                       class="w-full rounded-md border border-slate-200/80 dark:border-slate-700/70 bg-white/80 dark:bg-slate-900/80 px-2 py-1 text-xs sm:text-sm text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                                            </td>
                                            <td class="px-3 py-2 align-top">
                                                <input type="text"
                                                       wire:model="rows.{{ $index }}.group"
                                                       placeholder="general"
                                                       class="w-full rounded-md border border-slate-200/80 dark:border-slate-700/70 bg-white/80 dark:bg-slate-900/80 px-2 py-1 text-xs sm:text-sm text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                                            </td>
                                            <td class="px-3 py-2 text-center align-top">
                                                <input type="checkbox"
                                                       wire:model="rows.{{ $index }}.is_public"
                                                       class="h-3 w-3 sm:h-4 sm:w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                            </td>
                                            <td class="px-3 py-2 align-top text-end">
                                                <button type="button"
                                                        wire:click="removeRow({{ $index }})"
                                                        class="inline-flex items-center rounded-full border border-red-500/70 bg-red-50 px-2 py-0.5 text-[11px] font-medium text-red-600 hover:bg-red-100 dark:bg-red-900/40 dark:border-red-600/70 dark:text-red-200 dark:hover:bg-red-900/70">
                                                    {{ __('Remove') }}
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-3 py-4 text-center text-xs text-slate-500 dark:text-slate-400">
                                                {{ __('No settings yet. Add your first row.') }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="flex items-center justify-between pt-2">
                            <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400">
                                {{ __('These values are stored in the system_settings table.') }}
                            </p>
                            <button type="submit"
                                    class="inline-flex items-center rounded-full bg-emerald-600 px-4 py-1.5 text-xs sm:text-sm font-semibold text-white shadow hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:ring-offset-2 focus:ring-offset-slate-100 dark:focus:ring-offset-slate-900">
                                {{ __('Save settings') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs sm:text-sm text-emerald-800 shadow-sm dark:border-emerald-700/70 dark:bg-emerald-900/40 dark:text-emerald-50">
                    {{ session('status') }}
                </div>
            @endif
        </div>

        {{-- Right: Roles & Permissions overview + screen map --}}
        <div class="space-y-4">

            {{-- Roles & permissions matrix --}}
            <div class="rounded-2xl border border-slate-200/80 dark:border-slate-700/80 bg-gradient-to-br from-white via-slate-50 to-emerald-50/60 dark:from-slate-900 dark:via-slate-900/90 dark:to-slate-900/90 shadow-sm">
                <div class="border-b border-slate-200/70 dark:border-slate-700/70 px-4 py-3 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-800 dark:text-slate-100">
                            {{ __('Roles & permissions') }}
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            {{ __('Read-only matrix for current roles and their permissions.') }}
                        </p>
                    </div>
                    <span class="inline-flex items-center rounded-full bg-slate-900/90 px-2.5 py-1 text-[11px] font-medium text-slate-50 shadow-sm dark:bg-slate-50/95 dark:text-slate-900">
                        {{ count($rolesMatrix) }} {{ __('roles') }}
                    </span>
                </div>

                <div class="px-4 py-3 max-h-[420px] overflow-y-auto thin-scrollbar">
                    @forelse ($rolesMatrix as $role)
                        <div class="mb-3 rounded-xl border border-slate-200/70 dark:border-slate-700/70 bg-white/80 dark:bg-slate-950/40 px-3 py-2.5 shadow-sm">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-emerald-500/90 text-[11px] font-semibold text-white shadow">
                                        {{ strtoupper(mb_substr($role['name'], 0, 2)) }}
                                    </span>
                                    <div>
                                        <div class="text-xs sm:text-sm font-semibold text-slate-800 dark:text-slate-100">
                                            {{ $role['name'] }}
                                        </div>
                                        <div class="text-[11px] text-slate-500 dark:text-slate-400">
                                            guard: <span class="font-mono">{{ $role['guard'] }}</span> ·
                                            {{ $role['permissions_count'] }} {{ __('permissions') }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if (!empty($role['permissions_by_module']))
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach ($role['permissions_by_module'] as $module => $actions)
                                        <div class="rounded-lg border border-slate-200/70 dark:border-slate-700/70 bg-slate-50/80 dark:bg-slate-900/80 px-2.5 py-1.5">
                                            <div class="text-[11px] font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">
                                                {{ $module }}
                                            </div>
                                            <div class="mt-1 flex flex-wrap gap-1.5">
                                                @foreach ($actions as $action)
                                                    <span class="inline-flex items-center rounded-full bg-emerald-500/10 dark:bg-emerald-500/15 px-2 py-0.5 text-[10px] font-medium text-emerald-700 dark:text-emerald-200">
                                                        {{ $action !== '' ? $action : '—' }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="mt-2 text-[11px] text-slate-500 dark:text-slate-400">
                                    {{ __('No permissions attached to this role yet.') }}
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            {{ __('No roles found yet. Seed or create roles via the API first.') }}
                        </p>
                    @endforelse
                </div>
            </div>

            {{-- Screen → permission map --}}
            <div class="rounded-2xl border border-slate-200/80 dark:border-slate-700/80 bg-gradient-to-r from-slate-900 via-slate-800 to-emerald-700 text-slate-50 shadow-sm">
                <div class="px-4 py-3 border-b border-white/10 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold">
                            {{ __('Screen access map') }}
                        </h2>
                        <p class="text-[11px] text-slate-200/80">
                            {{ __('Each main screen is gated by a single can:{module}.{action} permission.') }}
                        </p>
                    </div>
                </div>
                <div class="px-4 py-3 space-y-2 text-xs">
                    @foreach ($screenPermissions as $screen)
                        <div class="flex items-start justify-between gap-2 rounded-lg bg-white/5 px-3 py-2">
                            <div>
                                <div class="font-medium">
                                    {{ $screen['label'] }}
                                </div>
                                <div class="font-mono text-[11px] text-emerald-200/90">
                                    route: {{ $screen['route_name'] }}
                                </div>
                            </div>
                            <div class="flex items-center">
                                <span class="inline-flex items-center rounded-full bg-emerald-500/90 px-2.5 py-0.5 text-[11px] font-semibold text-white shadow">
                                    can:{{ $screen['permission'] }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>
</div>
