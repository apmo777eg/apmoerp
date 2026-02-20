<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\Media;
use App\Services\ImageOptimizationService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use App\Livewire\BaseComponent as Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class MediaLibrary extends Component
{
    use WithFileUploads, WithPagination;

    // Media Library only accepts images
    private const ALLOWED_EXTENSIONS = [
        'jpg',
        'jpeg',
        'png',
        'gif',
        'webp',
        'ico',
    ];

    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/x-icon',
        'image/vnd.microsoft.icon',
    ];

    public $files = [];

    #[Url]
    public string $search = '';

    #[Url]
    public string $filterOwner = 'all'; // all, mine

    // Image preview modal
    public bool $showPreview = false;

    public ?array $previewImage = null;

    public function mount(): void
    {
        $user = auth()->user();
        if (! $user || ! $user->can('media.view')) {
            abort(403, __('Unauthorized access to media library'));
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFiles(): void
    {
        $this->validate([
            'files.*' => 'file|max:10240|mimes:'.implode(',', self::ALLOWED_EXTENSIONS).
                '|mimetypes:'.implode(',', self::ALLOWED_MIME_TYPES), // 10MB max, restricted types
        ]);

        $user = auth()->user();
        if (! $user->can('media.upload')) {
            session()->flash('error', __('You do not have permission to upload files'));

            return;
        }

        $optimizationService = app(ImageOptimizationService::class);
        $disk = config('filesystems.media_disk', 'local');

        foreach ($this->files as $file) {
            $this->guardAgainstHtmlPayload($file);
            $result = $optimizationService->optimizeUploadedFile($file, 'general', $disk);

            Media::create([
                'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'original_name' => $file->getClientOriginalName(),
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
        }

        $this->files = [];
        session()->flash('success', __('Files uploaded successfully'));
    }

    public function viewImage(int $id): void
    {
        $user = auth()->user();
        $canBypassBranch = ! $user->branch_id || $user->can('media.manage-all');
        $media = Media::query()
            ->when($user->branch_id && ! $canBypassBranch, fn ($q) => $q->forBranch($user->branch_id))
            ->findOrFail($id);

        if (! $media->isImage()) {
            session()->flash('error', __('This file is not an image'));

            return;
        }

        $this->previewImage = [
            'id' => $media->id,
            'name' => $media->original_name,
            'url' => $media->url,
            'size' => $media->human_size,
            'width' => $media->width,
            'height' => $media->height,
            'uploaded_by' => $media->user->name ?? __('Unknown'),
            'created_at' => $media->created_at?->format('Y-m-d H:i'),
        ];
        $this->showPreview = true;
    }

    public function closePreview(): void
    {
        $this->showPreview = false;
        $this->previewImage = null;
    }

    public function delete(int $id): void
    {
        $user = auth()->user();
        $canBypassBranch = ! $user->branch_id || $user->can('media.manage-all');
        $media = Media::query()
            ->when($user->branch_id && ! $canBypassBranch, fn ($q) => $q->forBranch($user->branch_id))
            ->findOrFail($id);

        // Check permissions
        $canDelete = $user->can('media.manage') ||
                     ($user->can('media.delete') && $media->user_id === $user->id);

        if (! $canDelete) {
            session()->flash('error', __('You do not have permission to delete this file'));

            return;
        }

        // Delete files from storage
        Storage::disk($media->disk)->delete($media->file_path);
        if ($media->thumbnail_path) {
            Storage::disk($media->disk)->delete($media->thumbnail_path);
        }

        $media->delete();
        session()->flash('success', __('File deleted successfully'));
    }

    public function render()
    {
        $user = auth()->user();
        $canBypassBranch = ! $user->branch_id || $user->can('media.manage-all');

        $query = Media::query()
            ->with('user')
            ->when($user->branch_id && ! $canBypassBranch, fn ($q) => $q->forBranch($user->branch_id))
            ->images() // Only show images in media library
            ->when(
                $this->filterOwner === 'mine' || ! $user->can('media.view-others'),
                fn ($q) => $q->forUser($user->id)
            )
            ->when($this->search, function ($query) {
                $search = "%{$this->search}%";

                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('name', 'like', $search)
                        ->orWhere('original_name', 'like', $search);
                });
            })
            ->orderBy('created_at', 'desc');

        $media = $query->paginate(20);

        return view('livewire.admin.media-library', [
            'media' => $media,
        ])->layout('layouts.app', ['title' => __('Media Library')]);
    }

    protected function guardAgainstHtmlPayload($file): void
    {
        $contents = strtolower((string) $file->get());
        $patterns = ['<script', '<iframe', '<html', '<object', '<embed', '&lt;script'];

        if (collect($patterns)->contains(fn ($needle) => str_contains($contents, $needle))) {
            abort(422, __('Uploaded file contains HTML content and was rejected.'));
        }
    }
}
