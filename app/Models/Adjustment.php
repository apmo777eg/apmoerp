<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Adjustment extends BaseModel
{
    use SoftDeletes;

    protected ?string $moduleKey = 'inventory';

    protected $table = 'stock_adjustments';

    /**
     * Fillable fields aligned with migration:
     * 2026_01_04_000003_create_inventory_tables.php
     */
    protected $fillable = [
        'branch_id',
        'warehouse_id',
        'reference_number',
        'adjustment_type',
        'status',
        'reason',
        'total_adjustment_value',
        'approved_by',
        'approved_at',
        'created_by',
        'note',
    ];

    protected $casts = [
        'total_adjustment_value' => 'decimal:4',
        'approved_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(AdjustmentItem::class, 'adjustment_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Backward compatibility accessor
    public function getNoteAttribute()
    {
        return $this->reason;
    }

    public function setNoteAttribute($value): void
    {
        $this->attributes['reason'] = $value;
    }
}
