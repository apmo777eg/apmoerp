<?php

namespace App\Models;

use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * V65-BUG-FIX: Added HasBranch trait for proper branch scoping.
 */
class StockTransferHistory extends Model
{
    use HasBranch, HasFactory;

    protected $table = 'stock_transfer_history';

    protected $fillable = [
        'branch_id',
        'stock_transfer_id',
        'from_status',
        'to_status',
        'notes',
        'metadata',
        'changed_by',
        'changed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'changed_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function stockTransfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
