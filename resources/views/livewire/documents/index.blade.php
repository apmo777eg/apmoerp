<div class="space-y-6" x-data="{
    copyToClipboard(url) {
        navigator.clipboard.writeText(url).then(() => {
            const toast = document.createElement('div');
            toast.className = 'fixed top-4 right-4 bg-emerald-500 text-white px-4 py-2 rounded-lg shadow-lg z-50';
            toast.textContent = @js(__('Link copied!'));
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 2000);
        }).catch(() => {
            alert(@js(__('Failed to copy link')));
        });
    }
}">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">{{ __('Documents') }}</h1>
            <p class="text-sm text-slate-500">{{ __('Manage documents and files (non-images)') }}</p>
        </div>
        <div class="flex items-center gap-2">
            @can('media.view')
                <a href="{{ route('admin.media.index') }}" class="erp-btn erp-btn-secondary flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    {{ __('Media Library') }}
                </a>
            @endcan
            @can('documents.create')
            <a href="{{ route('app.documents.create') }}" class="erp-btn erp-btn-primary">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                {{ __('Upload Document') }}
            </a>
            @endcan
        </div>
    </div>

    {{-- Statistics --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm">{{ __('Total Documents') }}</p>
                    <p class="text-2xl font-bold">{{ number_format($stats['total_documents']) }}</p>
                </div>
                <svg class="w-8 h-8 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            </div>
        </div>
        <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-emerald-100 text-sm">{{ __('Total Size') }}</p>
                    <p class="text-2xl font-bold">{{ $stats['total_size_formatted'] }}</p>
                </div>
                <svg class="w-8 h-8 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/></svg>
            </div>
        </div>
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm">{{ __('My Documents') }}</p>
                    <p class="text-2xl font-bold">{{ $documents->where('uploaded_by', auth()->id())->count() }}</p>
                </div>
                <svg class="w-8 h-8 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Search') }}</label>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search documents...') }}" class="erp-input w-full">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Category') }}</label>
                <select wire:model.live="category" class="erp-input w-full">
                    <option value="">{{ __('All Categories') }}</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}">{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Folder') }}</label>
                <select wire:model.live="folder" class="erp-input w-full">
                    <option value="">{{ __('All Folders') }}</option>
                    @foreach($folders as $f)
                        <option value="{{ $f }}">{{ $f }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Tag') }}</label>
                <select wire:model.live="tag" class="erp-input w-full">
                    <option value="">{{ __('All Tags') }}</option>
                    @foreach($tags as $t)
                        <option value="{{ $t->id }}">{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- Documents Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @forelse($documents as $doc)
            <div wire:key="doc-{{ $doc->id }}" class="bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-lg transition-all group">
                <!-- Document Preview -->
                <div class="relative">
                    <a href="{{ route('app.documents.show', $doc->id) }}" class="block">
                        <div class="flex items-center justify-center h-40 bg-gradient-to-br from-slate-50 to-slate-100">
                            @php
                                $ext = strtolower($doc->file_type);
                                $iconColor = match($ext) {
                                    'pdf' => 'text-red-500',
                                    'doc', 'docx' => 'text-blue-500',
                                    'xls', 'xlsx', 'csv' => 'text-green-500',
                                    'ppt', 'pptx' => 'text-orange-500',
                                    'txt' => 'text-gray-500',
                                    'zip', 'rar' => 'text-purple-500',
                                    default => 'text-slate-400'
                                };
                            @endphp
                            
                            <div class="text-center">
                                @if($ext === 'pdf')
                                    <svg class="w-20 h-20 {{ $iconColor }} mx-auto" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M9.5,16V18H8V16H9.5M11,18V16H11.5A1.5,1.5 0 0,0 13,14.5V14.5A1.5,1.5 0 0,0 11.5,13H10V18H11M15,18V13H16V18H15M11.5,14H11V15.5H11.5A0.5,0.5 0 0,0 12,15V14.5A0.5,0.5 0 0,0 11.5,14M13,9V3.5L18.5,9H13Z"/>
                                    </svg>
                                @elseif(in_array($ext, ['doc', 'docx']))
                                    <svg class="w-20 h-20 {{ $iconColor }} mx-auto" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M15.2,20H13.8L12,13.2L10.2,20H8.8L6.6,11H8.1L9.5,17.8L11.3,11H12.6L14.4,17.8L15.8,11H17.3L15.2,20M13,9V3.5L18.5,9H13Z"/>
                                    </svg>
                                @elseif(in_array($ext, ['xls', 'xlsx', 'csv']))
                                    <svg class="w-20 h-20 {{ $iconColor }} mx-auto" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M10,19H8V14H10V19M14,19H12V14H14V19M16,11H8V9H16V11M13,9V3.5L18.5,9H13Z"/>
                                    </svg>
                                @elseif(in_array($ext, ['zip', 'rar']))
                                    <svg class="w-20 h-20 {{ $iconColor }} mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                    </svg>
                                @else
                                    <svg class="w-20 h-20 {{ $iconColor }} mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                @endif
                                <div class="mt-2 px-3 py-1 bg-white rounded text-xs font-semibold {{ $iconColor }} uppercase shadow">
                                    {{ $ext }}
                                </div>
                            </div>
                        </div>
                    </a>
                    
                    <!-- Quick Actions Overlay -->
                    <div class="absolute top-2 right-2 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                        <a 
                            href="{{ route('app.documents.download', $doc->id) }}" 
                            class="p-2 bg-white rounded-full shadow-lg hover:bg-emerald-50"
                            title="{{ __('Download') }}"
                        >
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                        </a>
                        <button 
                            type="button"
                            @click="copyToClipboard('{{ route('app.documents.download', $doc->id) }}')"
                            class="p-2 bg-white rounded-full shadow-lg hover:bg-blue-50"
                            title="{{ __('Copy Link') }}"
                        >
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <!-- Document Info -->
                <div class="p-4">
                    <a href="{{ route('app.documents.show', $doc->id) }}" class="block">
                        <h3 class="font-semibold text-slate-900 truncate mb-1 hover:text-emerald-600 transition">
                            {{ $doc->title }}
                        </h3>
                        <p class="text-xs text-slate-500 mb-2">
                            {{ $doc->getFileSizeFormatted() }} • {{ strtoupper($doc->file_type) }}
                        </p>
                    </a>
                    
                    <!-- Tags -->
                    @if($doc->tags->count() > 0)
                        <div class="flex items-center gap-1 mb-2 flex-wrap">
                            @foreach($doc->tags->take(2) as $tag)
                                <span class="px-2 py-0.5 text-xs rounded" style="background-color: {{ $tag->color }}20; color: {{ $tag->color }}">
                                    {{ $tag->name }}
                                </span>
                            @endforeach
                            @if($doc->tags->count() > 2)
                                <span class="px-2 py-0.5 text-xs rounded bg-slate-100 text-slate-600">
                                    +{{ $doc->tags->count() - 2 }}
                                </span>
                            @endif
                        </div>
                    @endif
                    
                    <p class="text-xs text-slate-400 mb-3">
                        {{ $doc->uploader->name }} • {{ $doc->created_at->diffForHumans() }}
                    </p>
                    
                    <!-- Actions -->
                    <div class="flex items-center gap-2 pt-3 border-t border-slate-100">
                        <a href="{{ route('app.documents.show', $doc->id) }}" class="flex-1 text-center text-xs py-2 px-3 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition font-medium">
                            {{ __('View') }}
                        </a>
                        @can('documents.edit')
                            @if($doc->uploaded_by === auth()->id())
                                <a href="{{ route('app.documents.edit', $doc->id) }}" class="flex-1 text-center text-xs py-2 px-3 bg-emerald-50 text-emerald-700 rounded-lg hover:bg-emerald-100 transition font-medium">
                                    {{ __('Edit') }}
                                </a>
                            @endif
                        @endcan
                        @can('documents.delete')
                            @if($doc->uploaded_by === auth()->id())
                                <button type="button" 
                                    wire:click="delete({{ $doc->id }})" 
                                    wire:confirm="{{ __('Are you sure?') }}" 
                                    class="flex-1 text-center text-xs py-2 px-3 bg-red-50 text-red-700 rounded-lg hover:bg-red-100 transition font-medium"
                                >
                                    {{ __('Delete') }}
                                </button>
                            @endif
                        @endcan
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-12">
                <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                <p class="mt-2 text-slate-500">{{ __('No documents found') }}</p>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    <div class="mt-6">
        {{ $documents->links() }}
    </div>
</div>
