<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">{{ __('Role Management') }}</h1>
            <p class="text-sm text-slate-500">{{ __('Manage user roles and permissions') }}</p>
        </div>
        <a href="{{ route('admin.roles.create') }}" class="erp-btn erp-btn-primary">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            {{ __('Add Role') }}
        </a>
    </div>

    {{-- Role Hierarchy Info Card --}}
    <div class="erp-card p-4 bg-gradient-to-r from-slate-50 to-blue-50 border-l-4 border-blue-500">
        <h3 class="font-semibold text-slate-800 mb-2">{{ __('Role Hierarchy') }}</h3>
        <div class="flex flex-wrap gap-4 text-sm">
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-purple-500"></span>
                <span>{{ __('Super Admin') }} - {{ __('Full system access') }}</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-blue-500"></span>
                <span>{{ __('Branch Admin') }} - {{ __('Branch-level management') }}</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                <span>{{ __('Branch Employee') }} - {{ __('Module-specific access') }}</span>
            </div>
        </div>
    </div>

    <div class="erp-card p-4">
        <div class="mb-6">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search roles...') }}" class="erp-input max-w-md">
        </div>

        @if(session()->has('success'))
            <div class="mb-4 p-3 bg-emerald-50 text-emerald-700 rounded-lg">{{ session('success') }}</div>
        @endif
        @if(session()->has('error'))
            <div class="mb-4 p-3 bg-red-50 text-red-700 rounded-lg">{{ session('error') }}</div>
        @endif

        <div class="overflow-x-auto">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th wire:click="sortBy('name')" class="cursor-pointer hover:bg-slate-100">{{ __('Role Name') }}</th>
                        <th>{{ __('Permissions') }}</th>
                        <th>{{ __('Users') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($roles as $role)
                        <tr wire:key="role-{{ $role->id }}">
                            <td class="font-medium text-slate-800">
                                <div class="flex items-center gap-2">
                                    {{-- Role Type Indicator --}}
                                    @if($role->name === 'Super Admin')
                                        <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                                    @elseif(str_contains(strtolower($role->name), 'admin'))
                                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                    @else
                                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    @endif
                                    {{ __('role.' . $role->name, [], 'ar') !== 'role.' . $role->name ? __('role.' . $role->name) : $role->name }}
                                </div>
                            </td>
                            <td>
                                <span class="px-2 py-1 text-xs bg-blue-100 text-blue-700 rounded-full">
                                    {{ $role->permissions_count }} {{ __('permissions') }}
                                </span>
                            </td>
                            <td>
                                <span class="px-2 py-1 text-xs bg-slate-100 text-slate-700 rounded-full">
                                    {{ $role->users_count }} {{ __('users') }}
                                </span>
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    @if($role->name !== 'Super Admin')
                                        <a href="{{ route('admin.roles.edit', $role) }}" class="text-blue-600 hover:text-blue-800">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </a>
                                        <button type="button" wire:click="delete({{ $role->id }})" wire:confirm="{{ __('Are you sure you want to delete this role?') }}" class="text-red-600 hover:text-red-800">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    @else
                                        <span class="text-xs text-slate-400">{{ __('Protected') }}</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-8 text-slate-500">{{ __('No roles found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $roles->links() }}
        </div>
    </div>
</div>
