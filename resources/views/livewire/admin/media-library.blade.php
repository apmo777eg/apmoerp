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
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">{{ __('Media Library') }}</h1>
            <p class="text-sm text-slate-500">{{ __('Manage your uploaded files and images') }}</p>
        </div>
        @can('documents.view')
            <a href="{{ route('app.documents.index') }}" class="erp-btn erp-btn-secondary flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
                {{ __('Documents') }}
            </a>
        @endcan
    </div>

    @if(session()->has('success'))
        <div class="p-3 bg-emerald-50 text-emerald-700 rounded-lg">{{ session('success') }}</div>
    @endif
    @if(session()->has('error'))
        <div class="p-3 bg-red-50 text-red-700 rounded-lg">{{ session('error') }}</div>
    @endif

    <!-- Upload Section -->
    @can('media.upload')
    <div class="erp-card p-6">
        <h2 class="text-lg font-semibold text-slate-800 mb-4">{{ __('Upload Images') }}</h2>
        <div class="border-2 border-dashed border-slate-300 rounded-lg p-8 text-center hover:border-emerald-500 transition-colors"
             x-data="{ dragging: false }"
             @dragover.prevent="dragging = true"
             @dragleave.prevent="dragging = false"
             @drop.prevent="dragging = false"
             :class="{ 'border-emerald-500 bg-emerald-50': dragging }">
            <input type="file" wire:model="files" multiple class="hidden" id="file-upload" accept="image/*">
            <label for="file-upload" class="cursor-pointer">
                <svg class="mx-auto h-12 w-12 text-slate-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <p class="mt-4 text-sm text-slate-600">{{ __('Drop images here or click to upload') }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ __('Supported formats') }}: JPG, PNG, GIF, WebP, ICO</p>
                <p class="mt-1 text-xs text-slate-500">{{ __('Maximum file size') }}: 10 {{ __('MB') }}</p>
            </label>
        </div>
        <div wire:loading wire:target="files" class="mt-4 text-center">
            <div class="inline-flex items-center gap-2 text-emerald-600">
                <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>{{ __('Uploading...') }}</span>
            </div>
        </div>
    </div>
    @endcan

    <!-- Filters -->
    <div class="erp-card p-4">
        <div class="flex flex-wrap items-center gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search images...') }}" class="erp-input">
            </div>
            @can('media.view-others')
            <div>
                <select wire:model.live="filterOwner" class="erp-input">
                    <option value="all">{{ __('All Users Images') }}</option>
                    <option value="mine">{{ __('My Images') }}</option>
                </select>
            </div>
            @endcan
        </div>
    </div>

    <!-- Media Grid -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
        @forelse($media as $item)
            <div class="erp-card overflow-hidden group">
                <div class="aspect-square bg-slate-100 relative">
                    @if($item->isImage() && $item->thumbnail_path)
                        <img src="{{ $item->thumbnail_url }}" alt="{{ $item->name }}" class="w-full h-full object-cover">
                    @elseif($item->isImage())
                        <img src="{{ $item->url }}" alt="{{ $item->name }}" class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full flex items-center justify-center">
                            <svg class="h-16 w-16 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                    @endif
                    
                    <!-- Actions Overlay -->
                    <div class="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                        <button 
                            type="button"
                            wire:click="viewImage({{ $item->id }})"
                            class="p-2 bg-white rounded-full hover:bg-slate-100" 
                            title="{{ __('View') }}"
                        >
                            <svg class="h-5 w-5 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                        <a 
                            href="{{ $item->url }}" 
                            download="{{ $item->original_name }}"
                            class="p-2 bg-white rounded-full hover:bg-slate-100" 
                            title="{{ __('Download') }}"
                        >
                            <svg class="h-5 w-5 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                        </a>
                        <button 
                            type="button"
                            @click="copyToClipboard(@js($item->url))"
                            class="p-2 bg-white rounded-full hover:bg-slate-100"
                            title="{{ __('Copy Link') }}"
                        >
                            <svg class="h-5 w-5 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                            </svg>
                        </button>
                        @if(auth()->user()->can('media.manage') || (auth()->user()->can('media.delete') && $item->user_id === auth()->id()))
                        <button type="button" wire:click="delete({{ $item->id }})" wire:confirm="{{ __('Are you sure?') }}" class="p-2 bg-white rounded-full hover:bg-red-100" title="{{ __('Delete') }}">
                            <svg class="h-5 w-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                        @endif
                    </div>
                </div>
                
                <div class="p-3">
                    <p class="text-sm font-medium text-slate-800 truncate" title="{{ $item->original_name }}">{{ $item->name }}</p>
                    <div class="flex items-center justify-between mt-1">
                        <span class="text-xs text-slate-500">{{ $item->human_size }}</span>
                        @if($item->compression_ratio)
                            <span class="text-xs text-emerald-600" title="{{ __('Compression ratio') }}">-{{ $item->compression_ratio }}%</span>
                        @endif
                    </div>
                    <p class="text-xs text-slate-400 mt-1">{{ $item->user?->name ?? __('Unknown') }}</p>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-12">
                <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <p class="mt-2 text-sm text-slate-500">{{ __('No images found') }}</p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($media->hasPages())
        <div class="mt-6">
            {{ $media->links() }}
        </div>
    @endif

    <!-- Image Preview Modal -->
    @if($showPreview && $previewImage)
        <div 
            class="fixed inset-0 pointer-events-none flex items-center justify-center p-4"
            style="z-index: 9000;"
            role="dialog"
            aria-modal="true"
            aria-labelledby="image-preview-title"
            x-data="{ scale: 1 }"
            @keydown.escape.window="$wire.closePreview()"
        >
            {{-- Modal Content - Card Style without backdrop --}}
            <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-6xl max-h-[90vh] flex flex-col overflow-hidden pointer-events-auto border-2 border-emerald-500/30"
                style="z-index: 9001;"
                @click.stop>
                
                {{-- Header (Sticky) --}}
                <div class="flex-shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 sticky top-0 z-10">
                    <div>
                        <h2 id="image-preview-title" class="text-xl font-bold text-gray-900 dark:text-white">{{ $previewImage['name'] }}</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $previewImage['size'] }}
                            @if($previewImage['width'] && $previewImage['height'])
                                • {{ $previewImage['width'] }} × {{ $previewImage['height'] }}px
                            @endif
                        </p>
                    </div>
                    <button 
                        type="button" 
                        wire:click="closePreview"
                        aria-label="{{ __('Close modal') }}"
                        class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Image Preview Area (Scrollable) --}}
                <div class="flex-1 overflow-y-auto px-6 pb-4 scroll-smooth min-h-0 bg-gray-50 dark:bg-gray-900">
                    <div class="flex items-center justify-center min-h-full py-4">
                        <img 
                            src="{{ $previewImage['url'] }}" 
                            alt="{{ $previewImage['name'] }}"
                            class="max-h-[60vh] max-w-full object-contain rounded-lg shadow-lg transition-transform duration-200"
                            :style="'transform: scale(' + scale + ')'"
                        >
                    </div>
                </div>

                {{-- Footer with Actions (Sticky) --}}
                <div class="flex-shrink-0 px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 sticky bottom-0 z-10">
                    <div class="flex flex-col gap-4">
                        {{-- Zoom Controls --}}
                        <div class="flex items-center justify-center gap-3">
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('Zoom') }}:</span>
                            <button 
                                type="button"
                                @click="scale = Math.max(0.5, scale - 0.25)"
                                class="p-2 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition"
                                title="{{ __('Zoom Out') }}"
                                :disabled="scale <= 0.5"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM13 10H7"/>
                                </svg>
                            </button>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300 min-w-[60px] text-center" x-text="Math.round(scale * 100) + '%'"></span>
                            <button 
                                type="button"
                                @click="scale = 1"
                                class="p-2 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition"
                                title="{{ __('Reset Zoom') }}"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                                </svg>
                            </button>
                            <button 
                                type="button"
                                @click="scale = Math.min(3, scale + 0.25)"
                                class="p-2 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition"
                                title="{{ __('Zoom In') }}"
                                :disabled="scale >= 3"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/>
                                </svg>
                            </button>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="flex items-center justify-between gap-3">
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                {{ __('Uploaded by') }}: <span class="font-medium">{{ $previewImage['uploaded_by'] }}</span>
                                <span class="mx-2">•</span>
                                {{ $previewImage['created_at'] }}
                            </div>
                            <div class="flex gap-3">
                                <a 
                                    href="{{ $previewImage['url'] }}" 
                                    download="{{ $previewImage['name'] }}"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition font-medium"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                    {{ __('Download') }}
                                </a>
                                <button 
                                    type="button"
                                    @click="copyToClipboard(@js($previewImage['url']))"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                    </svg>
                                    {{ __('Copy Link') }}
                                </button>
                                <button 
                                    type="button"
                                    wire:click="closePreview"
                                    class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition font-medium"
                                >
                                    {{ __('Close') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
