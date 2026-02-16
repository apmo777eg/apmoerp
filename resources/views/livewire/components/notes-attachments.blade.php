<div class="space-y-6">
    @if (session('success'))
        <div class="p-3 bg-green-50 border border-green-200 rounded-xl text-green-700 text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    {{ __('Notes') }}
                    <span class="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full">{{ count($notes) }}</span>
                </h3>
                <button type="button" wire:click="openNoteModal" class="text-emerald-600 hover:text-emerald-700 text-sm font-medium flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ __('Add Note') }}
                </button>
            </div>

            <div class="space-y-3 max-h-96 overflow-y-auto">
                @forelse ($notes as $note)
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 group {{ $note['is_pinned'] ? 'ring-2 ring-amber-200' : '' }}">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex-1 min-w-0">
                                @if ($note['is_pinned'])
                                    <span class="inline-flex items-center gap-1 text-xs text-amber-600 mb-1">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 2a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 2zM10 15a.75.75 0 01.75.75v1.5a.75.75 0 01-1.5 0v-1.5A.75.75 0 0110 15z"/>
                                        </svg>
                                        {{ __('Pinned') }}
                                    </span>
                                @endif
                                <p class="text-sm text-slate-700 whitespace-pre-wrap">{{ $note['content'] }}</p>
                                <div class="mt-2 flex items-center gap-3 text-xs text-slate-500">
                                    <span>{{ $note['creator']['name'] ?? __('Unknown') }}</span>
                                    <span>{{ \Carbon\Carbon::parse($note['created_at'])->diffForHumans() }}</span>
                                    @if ($note['type'] !== 'general')
                                        <span class="px-1.5 py-0.5 bg-slate-200 rounded text-slate-600">{{ $note['type'] }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button type="button" wire:click="togglePin({{ $note['id'] }})" class="p-1 text-slate-400 hover:text-amber-500" title="{{ __('Pin') }}">
                                    <svg class="w-4 h-4" fill="{{ $note['is_pinned'] ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                                    </svg>
                                </button>
                                <button type="button" wire:click="editNote({{ $note['id'] }})" class="p-1 text-slate-400 hover:text-blue-500" title="{{ __('Edit') }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <button type="button" wire:click="deleteNote({{ $note['id'] }})" wire:confirm="{{ __('Are you sure you want to delete this note?') }}" class="p-1 text-slate-400 hover:text-red-500" title="{{ __('Delete') }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8 text-slate-400">
                        <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        <p class="text-sm">{{ __('No notes yet') }}</p>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                    </svg>
                    {{ __('Attachments') }}
                    <span class="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full">{{ count($attachments) }}</span>
                </h3>
                <button type="button" wire:click="openFileModal" class="text-emerald-600 hover:text-emerald-700 text-sm font-medium flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    {{ __('Upload') }}
                </button>
            </div>

            <div class="space-y-2 max-h-96 overflow-y-auto">
                @forelse ($attachments as $attachment)
                    <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl border border-slate-100 group hover:bg-slate-100 transition-colors">
                        <div class="flex-shrink-0">
                            @if ($attachment['is_image'])
                                <img src="{{ $attachment['url'] }}" alt="{{ $attachment['original_filename'] }}" class="w-12 h-12 rounded-lg object-cover">
                            @else
                                <div class="w-12 h-12 rounded-lg bg-slate-200 flex items-center justify-center">
                                    @if ($attachment['type'] === 'pdf')
                                        <svg class="w-6 h-6 text-red-500" fill="currentColor" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 2l5 5h-5V4zm-3.5 8.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3z"/></svg>
                                    @elseif ($attachment['type'] === 'spreadsheet')
                                        <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zM6 20V4h7v5h5v11H6z"/></svg>
                                    @else
                                        <svg class="w-6 h-6 text-blue-500" fill="currentColor" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zM6 20V4h7v5h5v11H6z"/></svg>
                                    @endif
                                </div>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-700 truncate">{{ $attachment['original_filename'] }}</p>
                            <div class="flex items-center gap-2 text-xs text-slate-500">
                                <span>{{ $attachment['human_size'] }}</span>
                                <span>&bull;</span>
                                <span>{{ \Carbon\Carbon::parse($attachment['created_at'])->diffForHumans() }}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            <a href="{{ $attachment['url'] }}" class="p-1.5 text-slate-400 hover:text-blue-500" title="{{ __('Download') }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                            </a>
                            <button type="button" wire:click="deleteAttachment({{ $attachment['id'] }})" wire:confirm="{{ __('Are you sure you want to delete this file?') }}" class="p-1.5 text-slate-400 hover:text-red-500" title="{{ __('Delete') }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8 text-slate-400">
                        <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                        </svg>
                        <p class="text-sm">{{ __('No files attached') }}</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    @if ($showNoteModal)
        <div class="fixed inset-0 flex items-center justify-center p-4 pointer-events-none" style="z-index: 9000;" wire:click.self="closeNoteModal">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden pointer-events-auto border-2 border-emerald-500/30" style="z-index: 9001;">
                <div class="px-6 py-4 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white">
                    <h3 class="text-lg font-semibold">
                        {{ $editingNoteId ? __('Edit Note') : __('Add Note') }}
                    </h3>
                </div>
                <form wire:submit="saveNote" class="p-6 space-y-4">
                    <div>
                        <label class="erp-label">{{ __('Note Type') }}</label>
                        <select wire:model="noteType" class="erp-input w-full mt-1">
                            <option value="general">{{ __('General') }}</option>
                            <option value="important">{{ __('Important') }}</option>
                            <option value="followup">{{ __('Follow-up') }}</option>
                            <option value="internal">{{ __('Internal') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="erp-label">{{ __('Note Content') }} *</label>
                        <textarea wire:model="newNote" rows="4" class="erp-input w-full mt-1" placeholder="{{ __('Enter your note here...') }}"></textarea>
                        @error('newNote') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                        <button type="button" wire:click="closeNoteModal" class="erp-btn-secondary">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit" class="erp-btn-primary">
                            {{ $editingNoteId ? __('Update Note') : __('Add Note') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($showFileModal)
        <div class="fixed inset-0 flex items-center justify-center p-4 pointer-events-none" style="z-index: 9000;" wire:click.self="closeFileModal">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden pointer-events-auto border-2 border-emerald-500/30" style="z-index: 9001;">
                <div class="px-6 py-4 bg-gradient-to-r from-blue-500 to-blue-600 text-white">
                    <h3 class="text-lg font-semibold">{{ __('Upload Files') }}</h3>
                </div>
                <form wire:submit="uploadFiles" class="p-6 space-y-4">
                    <div>
                        <label class="erp-label">{{ __('Select Files') }} *</label>
                        <div class="mt-1 border-2 border-dashed border-slate-300 rounded-xl p-6 text-center hover:border-emerald-400 transition-colors">
                            <input type="file" wire:model="newFiles" multiple class="hidden" id="file-upload">
                            <label for="file-upload" class="cursor-pointer">
                                <svg class="w-10 h-10 mx-auto text-slate-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                <p class="text-sm text-slate-600">{{ __('Click to select or drag files here') }}</p>
                                <p class="text-xs text-slate-400 mt-1">{{ __('Max 10MB per file') }}</p>
                            </label>
                        </div>
                        @if ($newFiles)
                            <div class="mt-3 space-y-1">
                                @foreach ($newFiles as $file)
                                    <div class="text-sm text-slate-600 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                        {{ $file->getClientOriginalName() }}
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        @error('newFiles') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        @error('newFiles.*') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="erp-label">{{ __('Description (optional)') }}</label>
                        <input type="text" wire:model="fileDescription" class="erp-input w-full mt-1" placeholder="{{ __('Brief description of the files...') }}">
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                        <button type="button" wire:click="closeFileModal" class="erp-btn-secondary">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit" class="erp-btn-primary" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="uploadFiles">{{ __('Upload') }}</span>
                            <span wire:loading wire:target="uploadFiles">{{ __('Uploading...') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
