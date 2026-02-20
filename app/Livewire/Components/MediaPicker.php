<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\Models\Media;
use App\Services\ImageOptimizationService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use App\Livewire\BaseComponent as Component;
use Livewire\WithFileUploads;

/**
 * Reusable Media Library Picker Component with Type-Scoping and Storage Scoping
 *
 * Usage in Blade (listen for events in parent component):
 * <livewire:components.media-picker
 *     :value="$branding_logo_id"
 *     accept-mode="image"
 *     storage-scope="media"
 *     :max-size="2048"
 *     :constraints="['maxWidth' => 400, 'maxHeight' => 100]"
 *     field-id="logo-picker"
 * />
 *
 * Accept modes:
 * - "image": Only show/accept images (jpg, png, gif, webp, ico)
 * - "file": Only show/accept non-image files (pdf, doc, xls, etc.)
 * - "mixed": Show and accept both images and files
 *
 * Storage scopes (determines where files are stored and listed from):
 * - "media": Global Media Library (default) - stored in media/, listed from Media model
 * - "direct": Direct upload mode - files uploaded directly, returns path instead of media_id
 *   Use for: incomes, expenses, avatars, contracts, documents, etc.
 *   Pass storage-path to specify the folder (e.g., "incomes", "expenses", "avatars")
 *
 * Parent component should listen for events:
 * #[On('media-selected')] public function handleMediaSelected(string $fieldId, int $mediaId, array $media)
 * #[On('media-cleared')] public function handleMediaCleared(string $fieldId)
 * #[On('file-uploaded')] public function handleFileUploaded(string $fieldId, string $path, array $fileInfo)
 */
class MediaPicker extends Component
{
    use WithFileUploads;

    // Modal state
    public bool $showModal = false;

    // Selected media
    public ?int $selectedMediaId = null;

    public ?array $selectedMedia = null;

    // For direct upload mode - stores the file path instead of media_id
    public ?string $selectedFilePath = null;

    // Upload
    public $uploadFile = null;

    // Search and filters
    public string $search = '';

    public string $filterType = 'all';

    public string $sortBy = 'newest'; // newest, oldest, name_asc, name_desc

    // Accept mode: 'image' | 'file' | 'mixed'
    // This is the PRIMARY configuration that controls type-scoping
    #[Locked]
    public string $acceptMode = 'mixed';

    // Storage scope: 'media' | 'direct'
    // - 'media': Use global Media Library (saves to media table)
    // - 'direct': Direct file upload (saves to specified path, no media record)
    #[Locked]
    public string $storageScope = 'media';

    // Storage path for direct mode (e.g., 'incomes', 'expenses', 'avatars')
    #[Locked]
    public string $storagePath = '';

    // Storage disk for direct mode
    #[Locked]
    public string $storageDisk = 'local';

    // Legacy support - will be converted to acceptMode
    #[Locked]
    public array $acceptTypes = ['image']; // ['image', 'document', 'all']

    #[Locked]
    public int $maxSize = 10240; // KB

    #[Locked]
    public array $constraints = []; // ['maxWidth' => 400, 'maxHeight' => 100, 'aspectRatio' => '16:9']

    // Optional: specific allowed mimes/extensions (overrides default for acceptMode)
    #[Locked]
    public array $allowedMimes = [];

    // Field identification
    #[Locked]
    public string $fieldId = 'media-picker';

    // Current preview URL (for display outside modal)
    public ?string $previewUrl = null;

    public ?string $previewName = null;

    // Load more pagination
    public int $perPage = 12;

    public int $page = 1;

    public bool $hasMorePages = false;

    public array $loadedMedia = [];

    public bool $isLoadingMore = false;

    // For direct mode - list of existing files in the storage path
    public array $existingFiles = [];

    // Whitelist of allowed storage disks for security
    private array $allowedDisks = ['local', 'private', 'public'];

    /**
     * Guard media access - ensures user has permission to view media
     */
    private function guardMediaAccess(): void
    {
        $user = auth()->user();
        abort_if(! $user || ! $user->can('media.view'), 403);
    }

    /**
     * Get a scoped media query that respects branch and user permissions
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function scopedMediaQuery()
    {
        $user = auth()->user();
        $query = Media::query();

        // Branch scoping: don't bypass just because branch_id is null
        // Only bypass if user has explicit permission
        if ($user?->branch_id && ! $user->can('media.manage-all')) {
            $query->forBranch($user->branch_id);
        }

        // User scoping: restrict to own files if no view-others permission
        if (! $user?->can('media.view-others')) {
            $query->forUser($user->id);
        }

        return $query;
    }

    /**
     * Validate that a path is safe for direct mode operations
     * Prevents directory traversal and ensures path is within storagePath
     */
    private function isValidDirectPath(string $path): bool
    {
        // Reject null bytes and other dangerous characters
        if (str_contains($path, "\0") || str_contains($path, '\\')) {
            return false;
        }

        // Reject path traversal attempts
        if (str_contains($path, '..')) {
            return false;
        }

        // Ensure disk is in the allowed list
        if (! in_array($this->storageDisk, $this->allowedDisks, true)) {
            return false;
        }

        // If storagePath is set, ensure the path starts with it
        if ($this->storagePath !== '' && ! str_starts_with($path, $this->storagePath)) {
            return false;
        }

        return true;
    }

    /**
     * Get image extensions from config or fallback to defaults
     */
    protected function getImageExtensions(): array
    {
        return config('media.image_extensions', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'ico']);
    }

    /**
     * Get document extensions from config or fallback to defaults
     */
    protected function getDocumentExtensions(): array
    {
        return config('media.document_extensions', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'csv', 'txt']);
    }

    /**
     * Get image MIME types from config or fallback to defaults
     */
    protected function getImageMimeTypes(): array
    {
        return config('media.image_mimes', [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/x-icon',
            'image/vnd.microsoft.icon',
        ]);
    }

    /**
     * Get document MIME types from config or fallback to defaults
     */
    protected function getDocumentMimeTypes(): array
    {
        return config('media.document_mimes', [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/csv',
            'text/plain',
        ]);
    }

    public function mount(
        ?int $value = null,
        ?string $filePath = null, // For direct mode - existing file path
        string $acceptMode = 'mixed',
        string $storageScope = 'media',
        string $storagePath = '',
        string $storageDisk = 'local',
        array $acceptTypes = ['image'], // Legacy - will be converted to acceptMode
        int $maxSize = 10240,
        array $constraints = [],
        array $allowedMimes = [],
        string $fieldId = 'media-picker'
    ): void {
        $this->selectedMediaId = $value;
        $this->selectedFilePath = $filePath;
        $this->maxSize = $maxSize;
        $this->constraints = $constraints;
        $this->fieldId = $fieldId;
        $this->allowedMimes = $allowedMimes;
        $this->storageScope = $storageScope;
        $this->storagePath = $storagePath;
        $this->storageDisk = $storageDisk ?: 'local';

        // Convert legacy acceptTypes to new acceptMode if acceptMode wasn't explicitly set
        // This maintains backward compatibility while preferring the new acceptMode
        if ($acceptMode === 'mixed') {
            // Check if acceptTypes was explicitly passed (non-default value)
            if ($acceptTypes === ['all'] || (in_array('image', $acceptTypes) && in_array('document', $acceptTypes))) {
                $this->acceptMode = 'mixed';
            } elseif (in_array('document', $acceptTypes) && ! in_array('image', $acceptTypes)) {
                $this->acceptMode = 'file';
            } elseif (in_array('image', $acceptTypes)) {
                $this->acceptMode = 'image';
            } else {
                $this->acceptMode = 'mixed';
            }
        } else {
            $this->acceptMode = $acceptMode;
        }

        // Store for legacy compatibility
        $this->acceptTypes = $acceptTypes;

        // Set initial filter based on acceptMode
        $this->filterType = $this->getDefaultFilterType();

        // Load existing media/file based on storage scope
        if ($this->storageScope === 'media' && $this->selectedMediaId) {
            $this->loadSelectedMedia();
        } elseif ($this->storageScope === 'direct' && $this->selectedFilePath) {
            $this->loadSelectedFile();
        }
    }

    /**
     * Load selected file info for direct mode
     */
    protected function loadSelectedFile(): void
    {
        if (! $this->selectedFilePath) {
            $this->selectedMedia = null;
            $this->previewUrl = null;
            $this->previewName = null;

            return;
        }

        // C1 FIX: Validate path is within allowed boundaries
        if (! $this->isValidDirectPath($this->selectedFilePath)) {
            $this->selectedFilePath = null;
            $this->selectedMedia = null;
            $this->previewUrl = null;
            $this->previewName = null;

            return;
        }

        $disk = Storage::disk($this->storageDisk);
        if (! $disk->exists($this->selectedFilePath)) {
            $this->selectedFilePath = null;

            return;
        }

        $fileName = basename($this->selectedFilePath);
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $mimeType = $disk->mimeType($this->selectedFilePath) ?? 'application/octet-stream';
        $size = $disk->size($this->selectedFilePath);
        $isImage = str_starts_with($mimeType, 'image/');

        $this->selectedMedia = [
            'id' => null, // No ID in direct mode
            'name' => pathinfo($fileName, PATHINFO_FILENAME),
            'original_name' => $fileName,
            'path' => $this->selectedFilePath,
            'url' => $disk->url($this->selectedFilePath),
            'thumbnail_url' => $isImage ? $disk->url($this->selectedFilePath) : null,
            'mime_type' => $mimeType,
            'extension' => $extension,
            'size' => $size,
            'human_size' => $this->formatFileSize($size),
            'width' => null,
            'height' => null,
            'is_image' => $isImage,
        ];

        $this->previewUrl = $isImage ? $disk->url($this->selectedFilePath) : null;
        $this->previewName = $fileName;
    }

    /**
     * Format file size to human readable
     */
    protected function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen((string) $bytes) - 1) / 3);

        return sprintf('%.1f %s', $bytes / pow(1024, $factor), $units[$factor] ?? 'B');
    }

    /**
     * Get the default filter type based on acceptMode
     */
    protected function getDefaultFilterType(): string
    {
        return match ($this->acceptMode) {
            'image' => 'images',
            'file' => 'documents',
            default => 'all',
        };
    }

    /**
     * Check if filter type switching is allowed
     */
    public function canSwitchFilterType(): bool
    {
        return $this->acceptMode === 'mixed';
    }

    public function loadSelectedMedia(): void
    {
        if (! $this->selectedMediaId) {
            $this->selectedMedia = null;
            $this->previewUrl = null;
            $this->previewName = null;

            return;
        }

        // C1 FIX: Use proper scoping instead of unscoped Media::find()
        $this->guardMediaAccess();
        $media = $this->scopedMediaQuery()->find($this->selectedMediaId);

        if ($media) {
            $this->selectedMedia = [
                'id' => $media->id,
                'name' => $media->name,
                'original_name' => $media->original_name,
                'url' => $media->url,
                'thumbnail_url' => $media->thumbnail_url,
                'mime_type' => $media->mime_type,
                'extension' => $media->extension,
                'size' => $media->size,
                'human_size' => $media->human_size,
                'width' => $media->width,
                'height' => $media->height,
                'is_image' => $media->isImage(),
            ];
            $this->previewUrl = $media->isImage() ? ($media->thumbnail_url ?? $media->url) : null;
            $this->previewName = $media->original_name;
        } else {
            // Reset if media not found or not accessible
            $this->selectedMediaId = null;
            $this->selectedMedia = null;
            $this->previewUrl = null;
            $this->previewName = null;
        }
    }

    #[On('openMediaPicker')]
    public function openModal(): void
    {
        $user = auth()->user();

        // For direct mode, we don't require media.view permission
        // The permission is controlled by the parent form's permission
        if ($this->storageScope === 'media') {
            if (! $user || ! $user->can('media.view')) {
                session()->flash('error', __('You do not have permission to access the media library'));

                return;
            }
        }

        $this->showModal = true;
        $this->search = '';
        $this->filterType = $this->getDefaultFilterType();
        $this->page = 1;
        $this->loadedMedia = [];
        $this->existingFiles = [];
        $this->hasMorePages = false;

        // Load initial media/files based on storage scope
        if ($this->storageScope === 'media') {
            $this->loadMedia();
        } else {
            $this->loadExistingFiles();
        }
    }

    /**
     * Load existing files from storage path for direct mode
     */
    protected function loadExistingFiles(): void
    {
        if (! $this->storagePath) {
            $this->existingFiles = [];

            return;
        }

        // C1 FIX: Validate disk is in allowed list
        if (! in_array($this->storageDisk, $this->allowedDisks, true)) {
            $this->existingFiles = [];

            return;
        }

        $disk = Storage::disk($this->storageDisk);
        $files = $disk->files($this->storagePath);

        $this->existingFiles = collect($files)
            ->map(function ($path) use ($disk) {
                // C1 FIX: Skip files with path traversal patterns
                if (str_contains($path, '..')) {
                    return null;
                }

                $fileName = basename($path);
                $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $mimeType = $disk->mimeType($path) ?? 'application/octet-stream';
                $size = $disk->size($path);
                $isImage = str_starts_with($mimeType, 'image/');

                // Apply type filtering
                if ($this->acceptMode === 'image' && ! $isImage) {
                    return null;
                }
                if ($this->acceptMode === 'file' && $isImage) {
                    return null;
                }

                // Apply search filter
                if ($this->search && ! str_contains(strtolower($fileName), strtolower($this->search))) {
                    return null;
                }

                return [
                    'id' => null,
                    'name' => pathinfo($fileName, PATHINFO_FILENAME),
                    'original_name' => $fileName,
                    'path' => $path,
                    'url' => $disk->url($path),
                    'thumbnail_url' => $isImage ? $disk->url($path) : null,
                    'mime_type' => $mimeType,
                    'extension' => $extension,
                    'size' => $size,
                    'human_size' => $this->formatFileSize($size),
                    'width' => null,
                    'height' => null,
                    'is_image' => $isImage,
                    'created_at' => date('Y-m-d H:i', $disk->lastModified($path)),
                    'user_name' => __('Unknown'),
                ];
            })
            ->filter()
            ->sortByDesc('created_at')
            ->values()
            ->toArray();
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->uploadFile = null;
        $this->loadedMedia = [];
        $this->page = 1;

        // Dispatch event to trigger Alpine.js cleanup
        $this->dispatch('close-media-modal');
    }

    public function updatingSearch(): void
    {
        $this->page = 1;
        $this->loadedMedia = [];
    }

    public function updatedSearch(): void
    {
        $this->loadMedia();
    }

    public function updatedSortBy(): void
    {
        $this->page = 1;
        $this->loadedMedia = [];
        $this->loadMedia();
    }

    public function updatedFilterType(): void
    {
        // Only allow filter type changes in mixed mode
        if (! $this->canSwitchFilterType()) {
            $this->filterType = $this->getDefaultFilterType();

            return;
        }

        $this->page = 1;
        $this->loadedMedia = [];
        $this->loadMedia();
    }

    /**
     * Load media with "Load More" pagination
     */
    public function loadMedia(): void
    {
        $user = auth()->user();
        if (! $user || ! $user->can('media.view')) {
            return;
        }

        $canBypassBranch = ! $user->branch_id || $user->can('media.manage-all');

        $query = Media::query()
            ->with('user')
            ->when($user->branch_id && ! $canBypassBranch, fn ($q) => $q->forBranch($user->branch_id));

        // Apply type filtering based on acceptMode
        $this->applyTypeFilter($query);

        // Apply permission filter
        if (! $user->can('media.view-others')) {
            $query->forUser($user->id);
        }

        // Apply search
        if ($this->search) {
            $search = "%{$this->search}%";
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('original_name', 'like', $search);
            });
        }

        // Apply sorting
        switch ($this->sortBy) {
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'name_asc':
                $query->orderBy('original_name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('original_name', 'desc');
                break;
            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $results = $query->paginate($this->perPage, ['*'], 'page', $this->page);

        $newItems = $results->items();

        if ($this->page === 1) {
            $this->loadedMedia = collect($newItems)->map(fn ($m) => $this->formatMediaItem($m))->toArray();
        } else {
            $this->loadedMedia = array_merge(
                $this->loadedMedia,
                collect($newItems)->map(fn ($m) => $this->formatMediaItem($m))->toArray()
            );
        }

        $this->hasMorePages = $results->hasMorePages();
    }

    /**
     * Load more media items
     */
    public function loadMore(): void
    {
        if (! $this->hasMorePages) {
            return;
        }

        $this->isLoadingMore = true;
        try {
            $this->page++;
            $this->loadMedia();
        } finally {
            $this->isLoadingMore = false;
        }
    }

    /**
     * Apply type filter to query based on acceptMode and current filterType
     */
    protected function applyTypeFilter($query): void
    {
        // Strict type enforcement based on acceptMode
        switch ($this->acceptMode) {
            case 'image':
                // ONLY images - no exceptions
                $query->images();
                break;

            case 'file':
                // ONLY files (non-images) - no exceptions
                $query->documents();
                break;

            case 'mixed':
            default:
                // Allow user to filter within mixed mode
                if ($this->filterType === 'images') {
                    $query->images();
                } elseif ($this->filterType === 'documents') {
                    $query->documents();
                }
                // 'all' shows everything
                break;
        }
    }

    /**
     * Format a media item for display
     */
    protected function formatMediaItem(Media $media): array
    {
        return [
            'id' => $media->id,
            'name' => $media->name,
            'original_name' => $media->original_name,
            'url' => $media->url,
            'thumbnail_url' => $media->thumbnail_url,
            'mime_type' => $media->mime_type,
            'extension' => $media->extension,
            'size' => $media->size,
            'human_size' => $media->human_size,
            'width' => $media->width,
            'height' => $media->height,
            'is_image' => $media->isImage(),
            'created_at' => $media->created_at?->format('Y-m-d H:i'),
            'user_name' => $media->user?->name ?? __('Unknown'),
        ];
    }

    public function updatedUploadFile(): void
    {
        $user = auth()->user();

        // For direct mode, we don't require media.upload permission
        // The permission is controlled by the parent form's permission
        if ($this->storageScope === 'media') {
            if (! $user || ! $user->can('media.upload')) {
                $this->uploadFile = null;
                session()->flash('error', __('You do not have permission to upload files'));

                return;
            }
        }

        $allowedExtensions = $this->getAllowedExtensions();
        $allowedMimeTypes = $this->getAllowedMimeTypes();

        $this->validate([
            'uploadFile' => 'file|max:'.$this->maxSize
                .'|mimes:'.implode(',', $allowedExtensions)
                .'|mimetypes:'.implode(',', $allowedMimeTypes),
        ]);

        $this->guardAgainstHtmlPayload($this->uploadFile);

        if ($this->storageScope === 'direct') {
            // Direct mode: upload to specified storage path
            $this->handleDirectUpload();
        } else {
            // Media mode: upload to Media Library
            $this->handleMediaUpload();
        }
    }

    /**
     * Handle direct file upload (stores file without Media record)
     */
    protected function handleDirectUpload(): void
    {
        $user = auth()->user();
        $disk = $this->storageDisk;
        $path = $this->storagePath;

        // C1 FIX: Validate disk is in allowed list
        if (! in_array($disk, $this->allowedDisks, true)) {
            session()->flash('error', __('Invalid storage configuration'));

            return;
        }

        // Store the file
        $storedPath = $this->uploadFile->store($path, $disk);

        $fileName = basename($storedPath);
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $mimeType = $this->uploadFile->getMimeType() ?? 'application/octet-stream';
        $size = $this->uploadFile->getSize();
        $isImage = str_starts_with($mimeType, 'image/');

        $storage = Storage::disk($disk);

        $this->selectedFilePath = $storedPath;
        $this->selectedMedia = [
            'id' => null,
            'name' => pathinfo($fileName, PATHINFO_FILENAME),
            'original_name' => $this->uploadFile->getClientOriginalName(),
            'path' => $storedPath,
            'url' => $storage->url($storedPath),
            'thumbnail_url' => $isImage ? $storage->url($storedPath) : null,
            'mime_type' => $mimeType,
            'extension' => $extension,
            'size' => $size,
            'human_size' => $this->formatFileSize($size),
            'width' => null,
            'height' => null,
            'is_image' => $isImage,
        ];

        $this->previewUrl = $isImage ? $storage->url($storedPath) : null;
        $this->previewName = $this->uploadFile->getClientOriginalName();

        // Dispatch event to parent with the uploaded file info
        $this->dispatch('file-uploaded',
            fieldId: $this->fieldId,
            path: $storedPath,
            fileInfo: $this->selectedMedia
        );

        $this->uploadFile = null;
        $this->closeModal();

        session()->flash('upload-success', __('File uploaded successfully'));
    }

    /**
     * Handle Media Library upload (stores file with Media record)
     */
    protected function handleMediaUpload(): void
    {
        $user = auth()->user();
        $optimizationService = app(ImageOptimizationService::class);
        $disk = config('filesystems.media_disk', 'local');

        $result = $optimizationService->optimizeUploadedFile($this->uploadFile, 'general', $disk);

        $media = Media::create([
            'name' => pathinfo($this->uploadFile->getClientOriginalName(), PATHINFO_FILENAME),
            'original_name' => $this->uploadFile->getClientOriginalName(),
            'file_path' => $result['file_path'],
            'thumbnail_path' => $result['thumbnail_path'],
            'mime_type' => $result['mime_type'],
            'extension' => $result['extension'],
            'size' => $result['size'],
            'optimized_size' => $result['optimized_size'],
            'width' => $result['width'],
            'height' => $result['height'],
            'disk' => $disk,
            'collection' => 'general',
            'user_id' => $user->id,
            'branch_id' => $user->branch_id,
        ]);

        // Auto-select the newly uploaded file
        $this->selectMedia($media->id);
        $this->uploadFile = null;

        // Refresh the media list to include the new item
        $this->page = 1;
        $this->loadedMedia = [];
        $this->loadMedia();

        session()->flash('upload-success', __('File uploaded successfully'));
    }

    /**
     * Select a file in direct mode
     */
    public function selectFile(string $path): void
    {
        // C1 FIX: Validate path is within allowed boundaries
        if (! $this->isValidDirectPath($path)) {
            session()->flash('error', __('Invalid file path'));

            return;
        }

        $disk = Storage::disk($this->storageDisk);

        if (! $disk->exists($path)) {
            session()->flash('error', __('File not found'));

            return;
        }

        $fileName = basename($path);
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $mimeType = $disk->mimeType($path) ?? 'application/octet-stream';
        $size = $disk->size($path);
        $isImage = str_starts_with($mimeType, 'image/');

        // Check type constraints
        if ($this->acceptMode === 'image' && ! $isImage) {
            session()->flash('error', __('Please select an image file'));

            return;
        }
        if ($this->acceptMode === 'file' && $isImage) {
            session()->flash('error', __('Please select a document file, not an image'));

            return;
        }

        $this->selectedFilePath = $path;
        $this->selectedMedia = [
            'id' => null,
            'name' => pathinfo($fileName, PATHINFO_FILENAME),
            'original_name' => $fileName,
            'path' => $path,
            'url' => $disk->url($path),
            'thumbnail_url' => $isImage ? $disk->url($path) : null,
            'mime_type' => $mimeType,
            'extension' => $extension,
            'size' => $size,
            'human_size' => $this->formatFileSize($size),
            'width' => null,
            'height' => null,
            'is_image' => $isImage,
        ];

        $this->previewUrl = $isImage ? $disk->url($path) : null;
        $this->previewName = $fileName;

        // Dispatch event to parent with the selected file info
        $this->dispatch('file-uploaded',
            fieldId: $this->fieldId,
            path: $path,
            fileInfo: $this->selectedMedia
        );

        $this->closeModal();
    }

    public function selectMedia(int $mediaId): void
    {
        // C1 FIX: Require proper permission and use scoped query
        $this->guardMediaAccess();

        $media = $this->scopedMediaQuery()->find($mediaId);

        if (! $media) {
            session()->flash('error', __('Media not found'));

            return;
        }

        // Check constraints
        if (! $this->checkConstraints($media)) {
            return;
        }

        $this->selectedMediaId = $media->id;
        $this->selectedMedia = [
            'id' => $media->id,
            'name' => $media->name,
            'original_name' => $media->original_name,
            'url' => $media->url,
            'thumbnail_url' => $media->thumbnail_url,
            'mime_type' => $media->mime_type,
            'extension' => $media->extension,
            'size' => $media->size,
            'human_size' => $media->human_size,
            'width' => $media->width,
            'height' => $media->height,
            'is_image' => $media->isImage(),
        ];
        $this->previewUrl = $media->isImage() ? ($media->thumbnail_url ?? $media->url) : null;
        $this->previewName = $media->original_name;

        // Dispatch event to parent with the selected media
        $this->dispatch('media-selected',
            fieldId: $this->fieldId,
            mediaId: $media->id,
            media: $this->selectedMedia
        );

        $this->closeModal();
    }

    public function confirmSelection(): void
    {
        if ($this->storageScope === 'direct' && $this->selectedFilePath) {
            $this->dispatch('file-uploaded',
                fieldId: $this->fieldId,
                path: $this->selectedFilePath,
                fileInfo: $this->selectedMedia
            );
        } elseif ($this->selectedMediaId) {
            $this->dispatch('media-selected',
                fieldId: $this->fieldId,
                mediaId: $this->selectedMediaId,
                media: $this->selectedMedia
            );
        }
        $this->closeModal();
    }

    public function clearSelection(): void
    {
        $this->selectedMediaId = null;
        $this->selectedFilePath = null;
        $this->selectedMedia = null;
        $this->previewUrl = null;
        $this->previewName = null;

        if ($this->storageScope === 'direct') {
            $this->dispatch('file-cleared', fieldId: $this->fieldId);
        } else {
            $this->dispatch('media-cleared', fieldId: $this->fieldId);
        }
    }

    protected function checkConstraints(Media $media): bool
    {
        // Check file type based on acceptMode (strict enforcement)
        switch ($this->acceptMode) {
            case 'image':
                if (! $media->isImage()) {
                    session()->flash('error', __('Please select an image file'));

                    return false;
                }
                break;

            case 'file':
                if ($media->isImage()) {
                    session()->flash('error', __('Please select a document file, not an image'));

                    return false;
                }
                break;

            case 'mixed':
            default:
                // Mixed mode accepts both
                break;
        }

        // Check dimension constraints for images
        if ($media->isImage() && ! empty($this->constraints)) {
            if (isset($this->constraints['maxWidth']) && $media->width > $this->constraints['maxWidth']) {
                session()->flash('error', __('Image width should not exceed :width pixels', ['width' => $this->constraints['maxWidth']]));

                return false;
            }
            if (isset($this->constraints['maxHeight']) && $media->height > $this->constraints['maxHeight']) {
                session()->flash('error', __('Image height should not exceed :height pixels', ['height' => $this->constraints['maxHeight']]));

                return false;
            }
            if (isset($this->constraints['minWidth']) && $media->width < $this->constraints['minWidth']) {
                session()->flash('error', __('Image width should be at least :width pixels', ['width' => $this->constraints['minWidth']]));

                return false;
            }
            if (isset($this->constraints['minHeight']) && $media->height < $this->constraints['minHeight']) {
                session()->flash('error', __('Image height should be at least :height pixels', ['height' => $this->constraints['minHeight']]));

                return false;
            }
        }

        return true;
    }

    /**
     * Get allowed extensions based on acceptMode
     */
    protected function getAllowedExtensions(): array
    {
        $imageExtensions = $this->getImageExtensions();
        $documentExtensions = $this->getDocumentExtensions();

        // If custom allowedMimes are specified, derive extensions from them
        if (! empty($this->allowedMimes)) {
            // Return a combined list based on custom mimes
            $extensions = [];
            foreach ($this->allowedMimes as $mime) {
                if (str_starts_with($mime, 'image/')) {
                    $extensions = array_merge($extensions, $imageExtensions);
                } else {
                    $extensions = array_merge($extensions, $documentExtensions);
                }
            }

            return array_unique($extensions);
        }

        // Use acceptMode to determine allowed extensions
        return match ($this->acceptMode) {
            'image' => $imageExtensions,
            'file' => $documentExtensions,
            default => array_merge($imageExtensions, $documentExtensions),
        };
    }

    /**
     * Get allowed MIME types based on acceptMode
     */
    protected function getAllowedMimeTypes(): array
    {
        // If custom allowedMimes are specified, use them directly
        if (! empty($this->allowedMimes)) {
            return $this->allowedMimes;
        }

        $imageMimes = $this->getImageMimeTypes();
        $documentMimes = $this->getDocumentMimeTypes();

        // Use acceptMode to determine allowed MIME types
        return match ($this->acceptMode) {
            'image' => $imageMimes,
            'file' => $documentMimes,
            default => array_merge($imageMimes, $documentMimes),
        };
    }

    /**
     * Get file input accept attribute value
     */
    public function getAcceptAttribute(): string
    {
        $extensions = $this->getAllowedExtensions();

        return implode(',', array_map(fn ($ext) => '.'.$ext, $extensions));
    }

    /**
     * Get human-readable description of allowed file types
     */
    public function getAllowedTypesDescription(): string
    {
        return match ($this->acceptMode) {
            'image' => __('Images').' (JPG, PNG, GIF, WebP)',
            'file' => __('Documents').' (PDF, DOC, XLS, TXT, CSV)',
            default => __('Images & Documents'),
        };
    }

    protected function guardAgainstHtmlPayload($file): void
    {
        // Only read the first 8KB for HTML detection (efficient for large files)
        $handle = fopen($file->getRealPath(), 'r');
        if (! $handle) {
            // If we can't read the file, reject it for security
            abort(422, __('Unable to verify file content. Upload rejected.'));
        }

        try {
            $contents = strtolower((string) fread($handle, 8192));

            $patterns = ['<script', '<iframe', '<html', '<object', '<embed', '&lt;script'];

            if (collect($patterns)->contains(fn ($needle) => str_contains($contents, $needle))) {
                abort(422, __('Uploaded file contains HTML content and was rejected.'));
            }
        } finally {
            fclose($handle);
        }
    }

    public function render()
    {
        // For direct mode, use existingFiles; for media mode, use loadedMedia
        $items = $this->storageScope === 'direct' ? $this->existingFiles : $this->loadedMedia;

        return view('livewire.components.media-picker', [
            'media' => $items,
            'allowedExtensions' => $this->getAllowedExtensions(),
            'acceptAttribute' => $this->getAcceptAttribute(),
            'allowedTypesDescription' => $this->getAllowedTypesDescription(),
            'canSwitchFilter' => $this->canSwitchFilterType(),
            'isDirectMode' => $this->storageScope === 'direct',
        ]);
    }
}
