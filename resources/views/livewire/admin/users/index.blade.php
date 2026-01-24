<div class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
        <div>
            <h1 class="text-lg font-semibold text-slate-800">
                {{ __('Users') }}
            </h1>
            <p class="text-sm text-slate-500">
                {{ __('Manage application users.') }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            <div class="w-full sm:w-64">
                <input type="search" wire:model.live.debounce.500ms="search"
                       placeholder="{{ __('Search users...') }}"
                       class="erp-input rounded-full">
            </div>
            <a href="{{ route('admin.users.create') }}"
               class="erp-btn-primary text-xs px-3 py-2">
                {{ __('Add user') }}
            </a>
        </div>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-slate-200 shadow-sm shadow-emerald-500/10">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-3 py-2 text-start text-xs font-semibold text-slate-500">
                        #
                    </th>
                    <th class="px-3 py-2 text-start text-xs font-semibold text-slate-500">
                        {{ __('Name') }}
                    </th>
                    <th class="px-3 py-2 text-start text-xs font-semibold text-slate-500">
                        {{ __('Email') }}
                    </th>
                    <th class="px-3 py-2 text-start text-xs font-semibold text-slate-500">
                        {{ __('Branch') }}
                    </th>
                    <th class="px-3 py-2 text-start text-xs font-semibold text-slate-500">
                        {{ __('Active') }}
                    </th>
                    <th class="px-3 py-2 text-end text-xs font-semibold text-slate-500">
                        {{ __('Actions') }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse ($users as $user)
                    <tr class="hover:bg-emerald-50/40">
                        <td class="px-3 py-2 text-xs text-slate-500">
                            {{ $user->id }}
                        </td>
                        <td class="px-3 py-2 text-xs text-slate-800">
                            {{ $user->name }}
                        </td>
                        <td class="px-3 py-2 text-xs text-slate-700">
                            {{ $user->email }}
                        </td>
                        <td class="px-3 py-2 text-xs text-slate-700">
                            @if(method_exists($user, 'branch') && $user->relationLoaded('branch'))
                                {{ optional($user->branch)->name }}
                            @else
                                {{-- branch --}}
                            @endif
                        </td>
                        <td class="px-3 py-2 text-xs text-slate-700">
                            @if(isset($user->is_active) && $user->is_active)
                                <span class="erp-badge">
                                    {{ __('Active') }}
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[0.7rem] font-medium text-slate-600">
                                    {{ __('Inactive') }}
                                </span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-xs text-end">
                            <a href="{{ route('admin.users.edit', $user->id) }}"
                               class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-1 text-[0.7rem] font-semibold text-emerald-700 hover:bg-emerald-100">
                                {{ __('Edit') }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-3 py-4 text-center text-xs text-slate-500">
                            {{ __('No users found.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $users->links() }}
    </div>
</div>
