<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoodsReceivedNote extends BaseModel
{
    use SoftDeletes;

    protected ?string $moduleKey = 'purchases';

    protected $table = 'goods_received_notes';

    /**
     * Fillable fields aligned with migration:
     * 2026_01_04_000005_create_sales_purchases_tables.php
     */
    protected $fillable = [
        'branch_id',
        'warehouse_id',
        'purchase_id',
        'supplier_id',
        'reference_number',
        'supplier_delivery_note',
        'status',
        'received_date',
        'notes',
        'received_by_name',
        'received_by',
        'inspected_by',
        'inspected_at',
    ];

    protected $casts = [
        'received_date' => 'date',
        'inspected_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function ($model) {
            if (! $model->reference_number) {
                $model->reference_number = static::generateReferenceNumber();
            }
        });
    }

    /**
     * Generate unique GRN reference number
     * V8-HIGH-N02 FIX: Use lockForUpdate to prevent race condition
     * V32-CRIT-03 FIX: Wrap in DB::transaction to ensure lockForUpdate is effective
     */
    public static function generateReferenceNumber(): string
    {
        return \Illuminate\Support\Facades\DB::transaction(function () {
            // Get the last reference number with a lock to prevent duplicates
            // V32-CRIT-03 FIX: The outer DB::transaction ensures the lock is effective
            $lastGrn = static::whereDate('created_at', today())
                ->lockForUpdate()
                ->orderBy('id', 'desc')
                ->first();

            $seq = 1;
            if ($lastGrn && preg_match('/GRN-\d{8}-(\d{5})$/', $lastGrn->reference_number, $matches)) {
                $seq = ((int) $matches[1]) + 1;
            }

            return 'GRN-'.date('Ymd').'-'.str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
        });
    }

    // Relationships
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function inspectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(GRNItem::class, 'grn_id');
    }

    // Backward compatibility accessor
    public function getCodeAttribute()
    {
        return $this->reference_number;
    }

    public function getDeliveryNoteNoAttribute()
    {
        return $this->supplier_delivery_note;
    }

    // Scopes
    public function scopePendingInspection(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', 'inspecting');
    }

    public function scopeApproved(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', 'completed');
    }

    // Business Logic
    public function approve(int $approvedBy): void
    {
        $this->update([
            'status' => 'completed',
            'inspected_by' => $approvedBy,
            'inspected_at' => now(),
        ]);
    }

    public function reject(int $rejectedBy, string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'inspected_by' => $rejectedBy,
            'inspected_at' => now(),
            'notes' => $reason,
        ]);
    }

    public function canBeApproved(): bool
    {
        return in_array($this->status, ['pending', 'inspecting']);
    }

    public function getTotalQuantityReceived(): float
    {
        return decimal_float($this->items->sum('received_quantity'));
    }

    public function getTotalQuantityAccepted(): float
    {
        return decimal_float($this->items->sum('accepted_quantity'));
    }

    public function getTotalQuantityRejected(): float
    {
        return decimal_float($this->items->sum('rejected_quantity'));
    }

    public function hasDiscrepancies(): bool
    {
        return $this->items->contains(function ($item) {
            return $item->received_quantity != $item->expected_quantity || $item->rejected_quantity > 0;
        });
    }
}
