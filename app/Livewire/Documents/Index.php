<?php

declare(strict_types=1);

namespace App\Livewire\Documents;

use App\Models\Document;
use App\Models\DocumentTag;
use App\Services\DocumentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use App\Livewire\BaseComponent as Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    private const ALLOWED_SORT_FIELDS = ['created_at', 'title', 'file_name'];

    // Image MIME types to exclude from documents
    private const IMAGE_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
        'image/x-icon',
        'image/vnd.microsoft.icon',
    ];

    #[Url]
    public string $search = '';

    #[Url]
    public string $category = '';

    #[Url]
    public string $folder = '';

    #[Url]
    public ?int $tag = null;

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    protected DocumentService $documentService;

    public function boot(DocumentService $documentService): void
    {
        $this->documentService = $documentService;
    }

    public function mount(): void
    {
        $this->authorize('documents.view');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if (! $this->isAllowedSortField($field)) {
            $this->sortField = 'created_at';
            $this->sortDirection = 'desc';

            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function delete(int $id): void
    {
        $this->authorize('documents.delete');

        $user = auth()->user();

        $document = Document::query()
            ->when($user?->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
            ->where(function ($q) use ($user) {
                $q->where('uploaded_by', $user?->id)
                    ->orWhere('is_public', true)
                    ->orWhereHas('shares', function ($shareQuery) use ($user) {
                        $shareQuery
                            ->where('shared_with_user_id', $user?->id)
                            ->active();
                    });
            })
            ->findOrFail($id);

        if ($user && $document->uploaded_by !== $user->id && ! $user->can('documents.manage')) {
            $share = $document->shares()
                ->active()
                ->where('shared_with_user_id', $user->id)
                ->first();

            if (! $share || ! $share->canDelete()) {
                abort(403);
            }
        }

        $this->documentService->deleteDocument($document);

        session()->flash('success', __('Document deleted successfully'));
        $this->resetPage();
    }

    public function render()
    {
        $user = auth()->user();
        $branchId = $user->branch_id;

        $sortField = $this->sanitizeSortField($this->sortField);
        $sortDirection = $this->sanitizeSortDirection($this->sortDirection);
        $search = $this->normalizedSearch();

        // Build query - exclude images (documents should be files only)
        $query = Document::with(['uploader', 'tags'])
            ->where(function ($q) use ($user) {
                $q->where('uploaded_by', $user->id)
                    ->orWhere('is_public', true)
                    ->orWhereHas('shares', function ($shareQuery) use ($user) {
                        $shareQuery->where('shared_with_user_id', $user->id)->active();
                    });
            })
            ->whereNotIn('mime_type', self::IMAGE_MIME_TYPES)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($search !== '', fn ($q) => $q->where(function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('file_name', 'like', "%{$search}%");
            }))
            ->when($this->category, fn ($q) => $q->where('category', $this->category))
            ->when($this->folder, fn ($q) => $q->where('folder', $this->folder))
            ->when($this->tag, fn ($q) => $q->whereHas('tags', fn ($tq) => $tq->where('document_tags.id', $this->tag)));

        $documents = $query->orderBy($sortField, $sortDirection)
            ->paginate(15);

        // Get statistics
        $stats = $this->documentService->getStatistics($branchId);

        // Get filter options (limited to prevent performance issues)
        $tags = DocumentTag::orderBy('name')->limit(200)->get();
        $categories = Document::select('category')
            ->whereNotNull('category')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->distinct()
            ->pluck('category');
        $folders = Document::select('folder')
            ->whereNotNull('folder')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->distinct()
            ->pluck('folder');

        return view('livewire.documents.index', [
            'documents' => $documents,
            'stats' => $stats,
            'tags' => $tags,
            'categories' => $categories,
            'folders' => $folders,
        ]);
    }

    private function sanitizeSortField(string $field): string
    {
        if ($this->isAllowedSortField($field)) {
            return $field;
        }

        $this->sortField = 'created_at';

        return 'created_at';
    }

    private function sanitizeSortDirection(string $direction): string
    {
        $direction = strtolower($direction);

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'desc';
        }

        $this->sortDirection = $direction;

        return $direction;
    }

    private function normalizedSearch(): string
    {
        $normalized = Str::of($this->search)
            ->trim()
            ->limit(100, '')
            ->toString();

        $normalized = str_replace(['%', '_'], '', $normalized);

        $this->search = $normalized;

        return $normalized;
    }

    private function isAllowedSortField(string $field): bool
    {
        return in_array($field, self::ALLOWED_SORT_FIELDS, true);
    }
}
