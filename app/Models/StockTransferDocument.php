<?php

namespace App\Models;

use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * V65-BUG-FIX: Added HasBranch trait for proper branch scoping.
 */
class StockTransferDocument extends Model
{
    use HasBranch, HasFactory;

    protected $fillable = [
        'branch_id',
        'stock_transfer_id',
        'document_type',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'description',
        'uploaded_by',
    ];

    /**
     * Ensure branch_id is derived from the parent stock transfer when not provided (works in console/jobs).
     */
    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if ($model->branch_id !== null) {
                return;
            }

            if ($model->stock_transfer_id) {
                $parent = StockTransfer::withoutGlobalScopes()->find($model->stock_transfer_id);
                if ($parent && $parent->branch_id) {
                    $model->branch_id = $parent->branch_id;
                }
            }
        });
    }


    /**
     * Relationships
     */
    public function stockTransfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get human-readable file size
     */
    public function getFormattedFileSize(): string
    {
        $bytes = $this->file_size;
        
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        
        return $bytes . ' bytes';
    }
}
