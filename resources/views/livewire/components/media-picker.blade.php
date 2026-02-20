<div class="inline-block">
    {{-- Preview/Trigger Button --}}
    <div class="relative">
        @if($selectedMedia && $selectedMedia['is_image'])
            <div class="relative group">
                <img 
                    src="{{ $previewUrl }}" 
                    alt="{{ $previewName }}" 
                    class="h-20 w-auto object-contain rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700"
                >
                <div class="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition-opacity rounded-lg flex items-center justify-center gap-2">
                    <button 
                        type="button" 
                        wire:click="openModal"
                        class="p-1.5 bg-white rounded-full hover:bg-gray-100 transition"
                        title="{{ __('Change') }}"
                    >
                        <svg class="w-4 h-4 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </button>
                    <button 
                        type="button" 
                        wire:click="clearSelection"
                        class="p-1.5 bg-white rounded-full hover:bg-red-100 transition"
                        title="{{ __('Remove') }}"
                    >
                        <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        @elseif($selectedMedia)
            {{-- File (non-image) preview --}}
            <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 max-w-xs">
                <div class="flex-shrink-0 w-10 h-10 flex items-center justify-center bg-gray-200 dark:bg-gray-600 rounded-lg">
                    @php
                        $ext = strtolower($selectedMedia['extension'] ?? pathinfo($previewName ?? '', PATHINFO_EXTENSION));
                    @endphp
                    @if(in_array($ext, ['pdf']))
                        <svg class="h-6 w-6 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M9.5,16V18H8V16H9.5M11,18V16H11.5A1.5,1.5 0 0,0 13,14.5V14.5A1.5,1.5 0 0,0 11.5,13H10V18H11M15,18V13H16V18H15M11.5,14H11V15.5H11.5A0.5,0.5 0 0,0 12,15V14.5A0.5,0.5 0 0,0 11.5,14M13,9V3.5L18.5,9H13Z"/>
                        </svg>
                    @elseif(in_array($ext, ['doc', 'docx']))
                        <svg class="h-6 w-6 text-blue-500" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M15.2,20H13.8L12,13.2L10.2,20H8.8L6.6,11H8.1L9.5,17.8L11.3,11H12.6L14.4,17.8L15.8,11H17.3L15.2,20M13,9V3.5L18.5,9H13Z"/>
                        </svg>
                    @elseif(in_array($ext, ['xls', 'xlsx', 'csv']))
                        <svg class="h-6 w-6 text-green-500" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M10,19H8V14H10V19M14,19H12V14H14V19M16,11H8V9H16V11M13,9V3.5L18.5,9H13Z"/>
                        </svg>
                    @else
                        <svg class="h-6 w-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $previewName }}</p>
                    <p class="text-xs text-gray-500">{{ $selectedMedia['human_size'] ?? '' }}</p>
                </div>
                <div class="flex gap-1">
                    <button 
                        type="button" 
                        wire:click="openModal"
                        class="p-1.5 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
                        title="{{ __('Change') }}"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                        </svg>
                    </button>
                    <button 
                        type="button" 
                        wire:click="clearSelection"
                        class="p-1.5 text-red-500 hover:text-red-700"
                        title="{{ __('Remove') }}"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        @else
            <button 
                type="button" 
                wire:click="openModal"
                class="flex items-center gap-2 px-4 py-2 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg hover:border-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors"
            >
                @if($acceptMode === 'image')
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('Select Image') }}</span>
                @elseif($acceptMode === 'file')
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('Select File') }}</span>
                @else
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('Select from Media Library') }}</span>
                @endif
            </button>
        @endif
    </div>

    {{-- Modal - Improved non-blocking popup --}}
    @if($showModal)
    <div 
        class="fixed inset-0 pointer-events-none flex items-center justify-center p-4"
        style="z-index: 9000;"
        role="dialog"
        aria-modal="true"
        aria-labelledby="media-picker-title-{{ $fieldId }}"
        x-data="{ 
            modalId: '{{ $fieldId }}-modal'
        }"
        @keydown.escape.window="$wire.closeModal();"
    >
        {{-- Modal Content - Card Style without backdrop --}}
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden pointer-events-auto border-2 border-emerald-500/30"
            style="z-index: 9001;">
            {{-- Header (Sticky) --}}
            <div class="flex-shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 sticky top-0 z-10">
                <div>
                    <h2 id="media-picker-title-{{ $fieldId }}" class="text-xl font-bold text-gray-900 dark:text-white">{{ __('Media Library') }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        @if($acceptMode === 'image')
                            {{ __('Select or upload an image') }}
                        @elseif($acceptMode === 'file')
                            {{ __('Select or upload a document') }}
                        @else
                            {{ __('Select or upload a file') }}
                        @endif
                        @if(!$isDirectMode && count($loadedMedia) > 0)
                            <span class="text-xs text-gray-400 ml-2" aria-live="polite">{{ count($loadedMedia) }} {{ __('items loaded') }}</span>
                        @endif
                    </p>
                </div>
                <button 
                    type="button" 
                    wire:click="closeModal"
                    aria-label="{{ __('Close modal') }}"
                    class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Alerts --}}
            @if(session()->has('error'))
                <div class="mx-6 mt-4 p-3 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 rounded-lg text-sm">
                    {{ session('error') }}
                </div>
            @endif
            @if(session()->has('upload-success'))
                <div class="mx-6 mt-4 p-3 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300 rounded-lg text-sm">
                    {{ session('upload-success') }}
                </div>
            @endif

            {{-- Upload Section (Sticky with Header) --}}
            @if($isDirectMode || auth()->user()?->can('media.upload'))
            <div class="flex-shrink-0 px-6 pt-4 pb-3 bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
                <div 
                    class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 text-center hover:border-emerald-500 transition-colors cursor-pointer"
                    x-data="{ dragging: false }"
                    @dragover.prevent="dragging = true"
                    @dragleave.prevent="dragging = false"
                    @drop.prevent="dragging = false; $refs.fileInput.files = $event.dataTransfer.files; $refs.fileInput.dispatchEvent(new Event('change'));"
                    :class="{ 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20': dragging }"
                >
                    <input 
                        type="file" 
                        wire:model="uploadFile" 
                        class="hidden" 
                        id="media-upload-{{ $fieldId }}"
                        accept="{{ $acceptAttribute }}"
                        x-ref="fileInput"
                    >
                    <label for="media-upload-{{ $fieldId }}" class="cursor-pointer block">
                        <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            {{ __('Click to upload or drag and drop') }}
                        </p>
                        <p class="text-xs text-gray-500">
                            {{ $allowedTypesDescription }} · {{ __('Max') }}: {{ round($maxSize / 1024, 1) }} MB
                        </p>
                    </label>
                </div>
                <div wire:loading wire:target="uploadFile" class="mt-2 text-center">
                    <div class="inline-flex items-center gap-2 text-emerald-600">
                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-sm">{{ __('Uploading...') }}</span>
                    </div>
                </div>
            </div>
            @endif

            {{-- Search & Filter (Sticky with Header) --}}
            <div class="flex-shrink-0 px-6 py-3 flex flex-wrap gap-3 bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
                <div class="flex-1 min-w-[200px] relative">
                    <label for="media-search-{{ $fieldId }}" class="sr-only">{{ __('Search files') }}</label>
                    <input 
                        type="text" 
                        id="media-search-{{ $fieldId }}"
                        wire:model.live.debounce.300ms="search" 
                        placeholder="{{ __('Search files...') }}"
                        aria-label="{{ __('Search files') }}"
                        class="w-full px-3 py-2 pr-8 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                    >
                    @if($search)
                    <button 
                        type="button"
                        wire:click="$set('search', '')"
                        aria-label="{{ __('Clear search') }}"
                        class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-gray-400 hover:text-gray-600 rounded"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                    @endif
                </div>
                
                @if($canSwitchFilter)
                <label for="media-filter-{{ $fieldId }}" class="sr-only">{{ __('Filter by type') }}</label>
                <select 
                    id="media-filter-{{ $fieldId }}"
                    wire:model.live="filterType" 
                    aria-label="{{ __('Filter by type') }}"
                    class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                >
                    <option value="all">{{ __('All Files') }}</option>
                    <option value="images">{{ __('Images') }}</option>
                    <option value="documents">{{ __('Documents') }}</option>
                </select>
                @else
                {{-- Show disabled filter indicator for type-locked modes --}}
                <div class="px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg text-sm bg-gray-50 dark:bg-gray-700 text-gray-500 flex items-center gap-2" role="status">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    @if($acceptMode === 'image')
                        {{ __('Images Only') }}
                    @elseif($acceptMode === 'file')
                        {{ __('Documents Only') }}
                    @endif
                </div>
                @endif
                
                <label for="media-sort-{{ $fieldId }}" class="sr-only">{{ __('Sort by') }}</label>
                <select 
                    id="media-sort-{{ $fieldId }}"
                    wire:model.live="sortBy" 
                    aria-label="{{ __('Sort by') }}"
                    class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                >
                    <option value="newest">{{ __('Newest First') }}</option>
                    <option value="oldest">{{ __('Oldest First') }}</option>
                    <option value="name_asc">{{ __('Name A→Z') }}</option>
                    <option value="name_desc">{{ __('Name Z→A') }}</option>
                </select>
            </div>

            {{-- Media Grid (Scrollable Area) --}}
            <div class="flex-1 overflow-y-auto px-6 pb-4 scroll-smooth min-h-0" id="media-grid-scroll-{{ $fieldId }}" x-data="{ 
                showBackToTop: false,
                checkScroll() {
                    this.showBackToTop = this.$el.scrollTop > 300;
                },
                scrollToTop() {
                    this.$el.scrollTo({ top: 0, behavior: 'smooth' });
                }
            }" @scroll.debounce.100ms="checkScroll()">
                {{-- Loading skeleton --}}
                <div wire:loading.delay wire:target="loadMedia, loadMore, loadExistingFiles" class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3 mt-4" role="status" aria-live="polite" aria-label="{{ __('Loading media items') }}">
                    @for($i = 0; $i < 10; $i++)
                    <div class="aspect-square rounded-lg bg-gray-200 dark:bg-gray-700 animate-pulse" aria-hidden="true"></div>
                    @endfor
                </div>

                <div wire:loading.remove wire:target="loadMedia, loadMore, loadExistingFiles" class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3 mt-4" role="list" aria-label="{{ __('Media items') }}">
                    @forelse($media as $item)
                        <button
                            type="button"
                            role="listitem"
                            @if($isDirectMode && isset($item['path']))
                                wire:click="selectFile(@js($item['path']))"
                            @else
                                wire:click="selectMedia({{ $item['id'] }})"
                            @endif
                            aria-label="{{ __('Select') }} {{ $item['original_name'] }}"
                            aria-pressed="{{ ($isDirectMode ? $selectedFilePath === ($item['path'] ?? '') : $selectedMediaId === $item['id']) ? 'true' : 'false' }}"
                            class="group relative aspect-square rounded-lg overflow-hidden border-2 transition-all
                                {{ ($isDirectMode ? $selectedFilePath === ($item['path'] ?? '') : $selectedMediaId === $item['id'])
                                    ? 'border-emerald-500 ring-2 ring-emerald-500/30' 
                                    : 'border-gray-200 dark:border-gray-600 hover:border-emerald-400' }}"
                        >
                            @if($item['is_image'] && ($item['thumbnail_url'] ?? $item['url']))
                                <img 
                                    src="{{ $item['thumbnail_url'] ?? $item['url'] }}" 
                                    alt="{{ $item['name'] }}" 
                                    class="w-full h-full object-cover"
                                    loading="lazy"
                                >
                            @elseif($item['is_image'])
                                <img 
                                    src="{{ $item['url'] }}" 
                                    alt="{{ $item['name'] }}" 
                                    class="w-full h-full object-cover"
                                    loading="lazy"
                                >
                            @else
                                {{-- File card view for non-images --}}
                                <div class="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800 p-3">
                                    @php
                                        $ext = strtolower($item['extension'] ?? '');
                                        $extColor = match($ext) {
                                            'pdf' => 'text-red-500',
                                            'doc', 'docx' => 'text-blue-500',
                                            'xls', 'xlsx', 'csv' => 'text-green-500',
                                            'ppt', 'pptx' => 'text-orange-500',
                                            'txt' => 'text-gray-500',
                                            default => 'text-gray-400'
                                        };
                                    @endphp
                                    @if(in_array($ext, ['pdf']))
                                        <svg class="h-12 w-12 {{ $extColor }}" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M9.5,16V18H8V16H9.5M11,18V16H11.5A1.5,1.5 0 0,0 13,14.5V14.5A1.5,1.5 0 0,0 11.5,13H10V18H11M15,18V13H16V18H15M11.5,14H11V15.5H11.5A0.5,0.5 0 0,0 12,15V14.5A0.5,0.5 0 0,0 11.5,14M13,9V3.5L18.5,9H13Z"/>
                                        </svg>
                                    @elseif(in_array($ext, ['doc', 'docx']))
                                        <svg class="h-12 w-12 {{ $extColor }}" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M15.2,20H13.8L12,13.2L10.2,20H8.8L6.6,11H8.1L9.5,17.8L11.3,11H12.6L14.4,17.8L15.8,11H17.3L15.2,20M13,9V3.5L18.5,9H13Z"/>
                                        </svg>
                                    @elseif(in_array($ext, ['xls', 'xlsx', 'csv']))
                                        <svg class="h-12 w-12 {{ $extColor }}" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M10,19H8V14H10V19M14,19H12V14H14V19M16,11H8V9H16V11M13,9V3.5L18.5,9H13Z"/>
                                        </svg>
                                    @elseif(in_array($ext, ['ppt', 'pptx']))
                                        <svg class="h-12 w-12 {{ $extColor }}" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M9.5,11.5C9.5,10.12 10.62,9 12,9C13.38,9 14.5,10.12 14.5,11.5C14.5,12.88 13.38,14 12,14H10V18H8V9H10V11.5H11C11,10.95 11.45,10.5 12,10.5C12.55,10.5 13,10.95 13,11.5C13,12.05 12.55,12.5 12,12.5H10V11.5H9.5M13,9V3.5L18.5,9H13Z"/>
                                        </svg>
                                    @else
                                        <svg class="h-12 w-12 {{ $extColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                        </svg>
                                    @endif
                                    <div class="mt-2 px-2 py-1 bg-white dark:bg-gray-900 rounded text-xs font-semibold {{ $extColor }} uppercase">
                                        {{ $ext }}
                                    </div>
                                </div>
                            @endif
                            
                            {{-- Overlay with info --}}
                            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex flex-col justify-end p-2">
                                <p class="text-xs text-white truncate font-medium">{{ $item['original_name'] }}</p>
                                <div class="flex items-center gap-2 text-xs text-gray-300">
                                    <span>{{ $item['human_size'] }}</span>
                                    @if($item['created_at'] ?? null)
                                        <span>·</span>
                                        <span>{{ $item['created_at'] }}</span>
                                    @endif
                                </div>
                            </div>

                            {{-- Selected checkmark --}}
                            @if(($isDirectMode ? $selectedFilePath === ($item['path'] ?? '') : $selectedMediaId === $item['id']))
                                <div class="absolute top-2 right-2 w-6 h-6 bg-emerald-500 rounded-full flex items-center justify-center shadow-lg">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                            @endif
                        </button>
                    @empty
                        <div class="col-span-full text-center py-12">
                            @if($acceptMode === 'image')
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <p class="mt-2 text-sm text-gray-500">{{ __('No images found') }}</p>
                            @elseif($acceptMode === 'file')
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                                <p class="mt-2 text-sm text-gray-500">{{ __('No documents found') }}</p>
                            @else
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <p class="mt-2 text-sm text-gray-500">{{ __('No media files found') }}</p>
                            @endif
                            <p class="mt-1 text-xs text-gray-400">{{ __('Upload a file to get started') }}</p>
                        </div>
                    @endforelse
                </div>

                {{-- Load More Button (only for media mode) --}}
                @if(!$isDirectMode && $hasMorePages)
                    <div class="mt-6 text-center">
                        <button 
                            type="button"
                            wire:click="loadMore"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-50 cursor-wait"
                            class="inline-flex items-center gap-2 px-6 py-2 text-sm font-medium text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20 hover:bg-emerald-100 dark:hover:bg-emerald-900/30 rounded-lg transition"
                        >
                            <span wire:loading.remove wire:target="loadMore">{{ __('Load More') }}</span>
                            <span wire:loading wire:target="loadMore" class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ __('Loading...') }}
                            </span>
                        </button>
                    </div>
                @endif
                
                {{-- Back to Top Button --}}
                <button
                    type="button"
                    x-show="showBackToTop"
                    x-transition
                    @click="scrollToTop()"
                    aria-label="{{ __('Back to top') }}"
                    class="fixed bottom-24 right-8 p-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-full shadow-lg transition-all z-20"
                    style="display: none;"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                    </svg>
                </button>
            </div>

            {{-- Footer (Sticky) --}}
            <div class="flex-shrink-0 flex items-center justify-between gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 sticky bottom-0 z-10">
                <div class="text-sm text-gray-600 dark:text-gray-400" role="status" aria-live="polite">
                    @if($selectedMediaId || $selectedFilePath)
                        <span class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            {{ __('1 item selected') }}
                        </span>
                    @else
                        {{ __('No item selected') }}
                    @endif
                </div>
                <div class="flex gap-3">
                    <button 
                        type="button"
                        wire:click="closeModal"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition"
                    >
                        {{ __('Cancel') }}
                    </button>
                    <button 
                        type="button"
                        wire:click="confirmSelection"
                        class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed"
                        {{ ($selectedMediaId || $selectedFilePath) ? '' : 'disabled' }}
                        aria-disabled="{{ ($selectedMediaId || $selectedFilePath) ? 'false' : 'true' }}"
                    >
                        {{ __('Select') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
