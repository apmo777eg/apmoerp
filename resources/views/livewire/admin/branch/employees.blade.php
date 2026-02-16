<div class="space-y-6">
    {{-- Page Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Branch Employees') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Manage employees for') }}: {{ $branch?->name }}</p>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session()->has('success'))
        <div class="rounded-md bg-green-50 p-4 dark:bg-green-900/50">
            <p class="text-sm text-green-700 dark:text-green-200">{{ session('success') }}</p>
        </div>
    @endif

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-lg bg-white p-4 shadow dark:bg-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Total Employees') }}</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $statistics['total'] }}</p>
        </div>
        <div class="rounded-lg bg-white p-4 shadow dark:bg-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Active') }}</p>
            <p class="text-2xl font-bold text-green-600">{{ $statistics['active'] }}</p>
        </div>
        <div class="rounded-lg bg-white p-4 shadow dark:bg-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Inactive') }}</p>
            <p class="text-2xl font-bold text-red-600">{{ $statistics['inactive'] }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex flex-wrap gap-4 rounded-lg bg-white p-4 shadow dark:bg-gray-800">
        <div class="flex-1">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Search') }}</label>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by name, email or phone...') }}"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Status') }}</label>
            <select wire:model.live="status" class="mt-1 rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700">
                <option value="">{{ __('All') }}</option>
                <option value="active">{{ __('Active') }}</option>
                <option value="inactive">{{ __('Inactive') }}</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Role') }}</label>
            <select wire:model.live="role" class="mt-1 rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700">
                <option value="">{{ __('All Roles') }}</option>
                @foreach($roleOptions as $roleValue => $roleLabel)
                    <option value="{{ $roleValue }}">{{ $roleLabel }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Employees Table --}}
    <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Employee') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Contact') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Role') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Status') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                @forelse($employees as $employee)
                    <tr>
                        <td class="whitespace-nowrap px-6 py-4">
                            <div class="flex items-center">
                                <div class="h-10 w-10 flex-shrink-0">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                        {{ strtoupper(substr($employee->name, 0, 2)) }}
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $employee->name }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $employee->username }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <div class="text-sm text-gray-900 dark:text-white">{{ $employee->email }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ $employee->phone }}</div>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                            @foreach($employee->roles as $employeeRole)
                                <span class="inline-flex rounded-full bg-blue-100 px-2 text-xs font-semibold leading-5 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    {{ $employeeRole->name }}
                                </span>
                            @endforeach
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 
                                @if($employee->is_active) bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                @else bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                @endif">
                                {{ $employee->is_active ? __('Active') : __('Inactive') }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm">
                            @if($employee->id !== auth()->id())
                                <button type="button" wire:click="toggleStatus({{ $employee->id }})" 
                                    wire:confirm="{{ $employee->is_active ? __('Are you sure you want to deactivate this employee?') : __('Are you sure you want to activate this employee?') }}"
                                    class="{{ $employee->is_active ? 'text-red-600 hover:text-red-900' : 'text-green-600 hover:text-green-900' }}">
                                    {{ $employee->is_active ? __('Deactivate') : __('Activate') }}
                                </button>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                            {{ __('No employees found.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($employees->hasPages())
        <div class="mt-4">
            {{ $employees->links() }}
        </div>
    @endif
</div>
