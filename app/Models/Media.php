<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Media Model
 * 
 * V57-CRITICAL-02 FIX: Added HasBranch trait for proper branch scoping.
 * Media files are branch-owned resources and should be isolated by branch.
 */
class Media extends Model
{
    use HasFactory, HasBranch;

    protected $table = 'media';

    protected $fillable = [
        'name',
        'original_name',
        'file_path',
        'thumbnail_path',
        'mime_type',
        'extension',
        'size',
        'optimized_size',
        'width',
        'height',
        'disk',
        'collection',
        'user_id',
        'branch_id',
        'metadata',
    ];

    protected $casts = [
        'size' => 'integer',
        'optimized_size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function getUrlAttribute(): string
    {
        return $this->generateAccessibleUrl($this->file_path);
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        if (! $this->thumbnail_path) {
            return null;
        }

        return $this->generateAccessibleUrl($this->thumbnail_path, true);
    }

    public function getHumanSizeAttribute(): string
    {
        return $this->formatBytes($this->size);
    }

    public function getOptimizedHumanSizeAttribute(): ?string
    {
        if (! $this->optimized_size) {
            return null;
        }

        return $this->formatBytes($this->optimized_size);
    }

    public function getCompressionRatioAttribute(): ?float
    {
        if (! $this->optimized_size || $this->size == 0) {
            return null;
        }

        return round((1 - ($this->optimized_size / $this->size)) * 100, 2);
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function isDocument(): bool
    {
        $documentTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
            'text/csv',
        ];

        return in_array($this->mime_type, $documentTypes);
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2).' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2).' KB';
        }

        return $bytes.' bytes';
    }

    public function scopeImages(Builder $query): Builder
    {
        return $query->where('mime_type', 'like', 'image/%');
    }

    public function scopeDocuments(Builder $query): Builder
    {
        $documentTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
            'text/csv',
        ];

        return $query->whereIn('mime_type', $documentTypes);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForBranch(Builder $query, int $branchId): Builder
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeCollection(Builder $query, string $collection): Builder
    {
        return $query->where('collection', $collection);
    }

    protected function generateAccessibleUrl(string $path, bool $isThumbnail = false): string
    {
        $disk = Storage::disk($this->disk);

        if (method_exists($disk, 'temporaryUrl')) {
            try {
                return $disk->temporaryUrl($path, now()->addMinutes(10));
            } catch (\Exception) {
                // fallback below
            }
        }

        try {
            return $disk->url($path);
        } catch (\Exception) {
            return route('app.media.download', array_filter([
                'media' => $this->id,
                'thumbnail' => $isThumbnail ? 1 : null,
            ]));
        }
    }
}
