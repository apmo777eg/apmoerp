<div class="space-y-6">
    {{-- Page Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('My Leaves') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('View and request leaves') }}</p>
        </div>
        <button type="button" wire:click="openRequestModal" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
            {{ __('Request Leave') }}
        </button>
    </div>

    {{-- Flash Messages --}}
    @if(session()->has('success'))
        <div class="rounded-md bg-green-50 p-4 dark:bg-green-900/50">
            <p class="text-sm text-green-700 dark:text-green-200">{{ session('success') }}</p>
        </div>
    @endif

    {{-- Leave Balance Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="rounded-lg bg-white p-4 shadow dark:bg-gray-800">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ __('Annual Leave') }}</h3>
            <div class="mt-2 flex items-end justify-between">
                <div>
                    <p class="text-3xl font-bold text-blue-600">{{ $leaveBalance['annual']['remaining'] }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Days remaining') }}</p>
                </div>
                <div class="text-right text-sm text-gray-500 dark:text-gray-400">
                    <p>{{ __('Total') }}: {{ $leaveBalance['annual']['total'] }}</p>
                    <p>{{ __('Used') }}: {{ $leaveBalance['annual']['used'] }}</p>
                </div>
            </div>
        </div>
        <div class="rounded-lg bg-white p-4 shadow dark:bg-gray-800">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ __('Sick Leave') }}</h3>
            <div class="mt-2 flex items-end justify-between">
                <div>
                    <p class="text-3xl font-bold text-green-600">{{ $leaveBalance['sick']['remaining'] }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Days remaining') }}</p>
                </div>
                <div class="text-right text-sm text-gray-500 dark:text-gray-400">
                    <p>{{ __('Total') }}: {{ $leaveBalance['sick']['total'] }}</p>
                    <p>{{ __('Used') }}: {{ $leaveBalance['sick']['used'] }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex flex-wrap gap-4 rounded-lg bg-white p-4 shadow dark:bg-gray-800">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Year') }}</label>
            <select wire:model.live="year" class="mt-1 rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700">
                @foreach(range(now()->year, now()->year - 3) as $y)
                    <option value="{{ $y }}">{{ $y }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Status') }}</label>
            <select wire:model.live="status" class="mt-1 rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700">
                <option value="">{{ __('All') }}</option>
                <option value="pending">{{ __('Pending') }}</option>
                <option value="approved">{{ __('Approved') }}</option>
                <option value="rejected">{{ __('Rejected') }}</option>
                <option value="cancelled">{{ __('Cancelled') }}</option>
            </select>
        </div>
    </div>

    {{-- Leave Requests Table --}}
    <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Type') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Period') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Days') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Status') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                @forelse($records as $record)
                    <tr>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-white">
                            {{ __(ucfirst($record->leave_type)) }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                            {{ $record->start_date?->format('Y-m-d') }} - {{ $record->end_date?->format('Y-m-d') }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                            {{ $record->days_count ?? ($record->start_date && $record->end_date ? $record->start_date->diffInDays($record->end_date) + 1 : '-') }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 
                                @if($record->status === 'approved') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                @elseif($record->status === 'rejected') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                @elseif($record->status === 'cancelled') bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200
                                @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                @endif">
                                {{ __(ucfirst($record->status)) }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm">
                            @if($record->status === 'pending')
                                <button type="button" wire:click="cancelRequest({{ $record->id }})" 
                                    wire:confirm="{{ __('Are you sure you want to cancel this request?') }}"
                                    class="text-red-600 hover:text-red-900 dark:text-red-400">
                                    {{ __('Cancel') }}
                                </button>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                            {{ __('No leave requests found.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($records instanceof \Illuminate\Pagination\LengthAwarePaginator && $records->hasPages())
        <div class="mt-4">
            {{ $records->links() }}
        </div>
    @endif

    {{-- Leave Request Modal --}}
    @if($showRequestModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeRequestModal"></div>
            <div class="inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all dark:bg-gray-800 sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
                <form wire:submit="submitRequest">
                    <div class="bg-white px-4 pt-5 pb-4 dark:bg-gray-800 sm:p-6 sm:pb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ __('Request Leave') }}</h3>
                        <div class="mt-4 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Leave Type') }}</label>
                                <select wire:model="leaveType" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700">
                                    <option value="">{{ __('Select Type') }}</option>
                                    @foreach($leaveTypes as $type)
                                        <option value="{{ $type }}">{{ __(ucfirst($type)) }}</option>
                                    @endforeach
                                </select>
                                @error('leaveType') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Start Date') }}</label>
                                    <input type="date" wire:model="startDate" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700">
                                    @error('startDate') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('End Date') }}</label>
                                    <input type="date" wire:model="endDate" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700">
                                    @error('endDate') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Reason') }}</label>
                                <textarea wire:model="reason" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700"></textarea>
                                @error('reason') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 dark:bg-gray-900 sm:flex sm:flex-row-reverse sm:px-6">
                        <button type="submit" class="inline-flex w-full justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 sm:ml-3 sm:w-auto">
                            {{ __('Submit') }}
                        </button>
                        <button type="button" wire:click="closeRequestModal" class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 sm:mt-0 sm:w-auto">
                            {{ __('Cancel') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
