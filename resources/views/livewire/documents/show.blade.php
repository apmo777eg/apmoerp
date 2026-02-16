<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">{{ $document->title }}</h1>
            <p class="text-sm text-slate-500">{{ __('Document Details') }}</p>
        </div>
        <div class="flex items-center gap-2">
            @can('documents.download')
                <button type="button" wire:click="download" class="erp-btn erp-btn-primary">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    {{ __('Download') }}
                </button>
            @endcan
            @can('documents.edit')
                @if($document->uploaded_by === auth()->id())
                    <a href="{{ route('app.documents.edit', $document->id) }}" class="erp-btn erp-btn-secondary">{{ __('Edit') }}</a>
                @endif
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="text-lg font-semibold text-slate-800 mb-4">{{ __('Preview') }}</h2>
                <div class="flex items-center justify-center h-96 bg-slate-100 rounded-lg">
                    @php
                        $previewUrl = route('app.documents.download', ['document' => $document->id, 'inline' => true]);
                    @endphp
                    @if(str_contains($document->mime_type, 'image'))
                        <img src="{{ $previewUrl }}" alt="{{ $document->title }}" class="max-h-full max-w-full object-contain">
                    @elseif(str_contains($document->mime_type, 'pdf'))
                        <iframe src="{{ $previewUrl }}" class="w-full h-full"></iframe>
                    @else
                        <div class="text-center">
                            <svg class="mx-auto w-24 h-24 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            <p class="mt-4 text-slate-600">{{ __('Preview not available') }}</p>
                            <p class="text-sm text-slate-500">{{ __('Click download to view this file') }}</p>
                        </div>
                    @endif
                </div>
            </div>

            @if($document->description)
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="font-semibold text-slate-800 mb-3">{{ __('Description') }}</h3>
                    <p class="text-slate-700 whitespace-pre-wrap">{{ $document->description }}</p>
                </div>
            @endif

            {{-- Versions --}}
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-slate-800">{{ __('Version History') }}</h3>
                    @can('documents.versions.manage')
                        <a href="{{ route('app.documents.versions', $document->id) }}" class="text-sm text-blue-600 hover:text-blue-900">{{ __('Manage Versions') }}</a>
                    @endcan
                </div>
                <div class="space-y-3">
                    @foreach($document->versions->take(5) as $version)
                        <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
                            <div>
                                <p class="text-sm font-medium text-slate-900">{{ __('Version') }} {{ $version->version_number }}</p>
                                <p class="text-xs text-slate-500">{{ $version->uploader->name }} • {{ $version->created_at->diffForHumans() }}</p>
                                @if($version->change_notes)
                                    <p class="text-xs text-slate-600 mt-1">{{ $version->change_notes }}</p>
                                @endif
                            </div>
                            <span class="text-xs text-slate-500">{{ $version->getFileSizeFormatted() }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="font-semibold text-slate-800 mb-4">{{ __('Document Information') }}</h3>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-slate-500">{{ __('File Name') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $document->file_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-slate-500">{{ __('File Size') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $document->getFileSizeFormatted() }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-slate-500">{{ __('File Type') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ strtoupper($document->file_type) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-slate-500">{{ __('Version') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $document->version }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-slate-500">{{ __('Uploaded By') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $document->uploader->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-slate-500">{{ __('Uploaded At') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $document->created_at?->format('Y-m-d H:i') }}</dd>
                    </div>
                    @if($document->folder)
                        <div>
                            <dt class="text-sm font-medium text-slate-500">{{ __('Folder') }}</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $document->folder }}</dd>
                        </div>
                    @endif
                    @if($document->category)
                        <div>
                            <dt class="text-sm font-medium text-slate-500">{{ __('Category') }}</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $document->category }}</dd>
                        </div>
                    @endif
                    @if($document->tags->count() > 0)
                        <div>
                            <dt class="text-sm font-medium text-slate-500">{{ __('Tags') }}</dt>
                            <dd class="mt-1 flex flex-wrap gap-1">
                                @foreach($document->tags as $tag)
                                    <span class="px-2 py-0.5 text-xs rounded" style="background-color: {{ $tag->color }}20; color: {{ $tag->color }}">
                                        {{ $tag->name }}
                                    </span>
                                @endforeach
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>

            {{-- Share Document --}}
            @can('documents.share')
                @if($document->uploaded_by === auth()->id())
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="font-semibold text-slate-800 mb-4">{{ __('Share Document') }}</h3>
                        <div class="space-y-3">
                            <select wire:model="shareUserId" class="erp-input w-full">
                                <option value="0">{{ __('Select User') }}</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                            <select wire:model="sharePermission" class="erp-input w-full">
                                <option value="view">{{ __('View Only') }}</option>
                                <option value="download">{{ __('Can Download') }}</option>
                                <option value="edit">{{ __('Can Edit') }}</option>
                                <option value="manage">{{ __('Full Access') }}</option>
                            </select>
                            <input type="datetime-local" wire:model="shareExpiresAt" class="erp-input w-full" placeholder="{{ __('Expires At (Optional)') }}">
                            <button type="button" wire:click="shareDocument" class="erp-btn erp-btn-primary w-full">{{ __('Share') }}</button>
                        </div>

                        @if($document->shares->count() > 0)
                            <div class="mt-4 pt-4 border-t">
                                <h4 class="text-sm font-medium text-slate-700 mb-2">{{ __('Shared With') }}</h4>
                                <div class="space-y-2">
                                    @foreach($document->shares as $share)
                                        <div class="flex items-center justify-between p-2 bg-slate-50 rounded">
                                            <div>
                                                <p class="text-sm text-slate-900">{{ $share->user?->name ?? __('Unknown') }}</p>
                                                <p class="text-xs text-slate-500">{{ ucfirst($share->permission) }}</p>
                                            </div>
                                            <button type="button" wire:click="unshare({{ $share->shared_with_user_id }})" class="text-xs text-red-600 hover:text-red-900">{{ __('Revoke') }}</button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            @endcan
        </div>
    </div>
</div>
