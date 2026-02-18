<div class="space-y-4" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold text-slate-800">{{ __('Project Tasks') }}</h3>
        <button type="button" wire:click="createTask" class="erp-btn erp-btn-sm erp-btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            {{ __('Add Task') }}
        </button>
    </div>

    {{-- Tasks List --}}
    <div class="space-y-2">
        @forelse($tasks as $task)
        <div class="bg-slate-50 rounded-lg p-4 hover:bg-slate-100 transition-colors">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" 
                               wire:click="toggleStatus({{ $task->id }})"
                               {{ $task->status === 'completed' ? 'checked' : '' }}
                               class="rounded border-slate-300 text-emerald-600">
                        <h4 class="font-medium text-slate-900 {{ $task->status === 'completed' ? 'line-through' : '' }}">
                            {{ $task->title }}
                        </h4>
                    </div>
                    @if($task->description)
                    <p class="text-sm text-slate-600 mt-1">{{ $task->description }}</p>
                    @endif
                    <div class="flex items-center gap-3 mt-2 text-xs text-slate-500">
                        @if($task->assigned_to)
                        <span>{{ __('Assigned to') }}: {{ $task->assignedTo?->name }}</span>
                        @endif
                        @if($task->due_date)
                        <span>{{ __('Due') }}: {{ $task->due_date?->format('Y-m-d') }}</span>
                        @endif
                    </div>
                </div>
                <div class="flex gap-2 ms-4">
                    <button type="button" wire:click="editTask({{ $task->id }})" class="text-blue-600 hover:text-blue-800">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </button>
                    <button type="button" wire:click="deleteTask({{ $task->id }})" wire:confirm="{{ __('Are you sure?') }}" class="text-red-600 hover:text-red-800">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        @empty
        <div class="text-center py-12 text-slate-500">
            <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <p>{{ __('No tasks yet') }}</p>
        </div>
        @endforelse
    </div>

    {{-- Add/Edit Modal --}}
    @if($showModal)
    <div class="z-modal fixed inset-0 bg-slate-900/50 flex items-center justify-center" wire:click.self="closeModal">
        <div class="bg-white rounded-xl p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">
                {{ $editingTaskId ? __('Edit Task') : __('Add Task') }}
            </h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Title') }}</label>
                    <input type="text" wire:model="title" class="erp-input">
                    @error('title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Description') }}</label>
                    <textarea wire:model="description" rows="3" class="erp-input"></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="button" wire:click="closeModal" class="erp-btn erp-btn-secondary flex-1">{{ __('Cancel') }}</button>
                    <button type="button" wire:click="save" class="erp-btn erp-btn-primary flex-1">{{ __('Save') }}</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
