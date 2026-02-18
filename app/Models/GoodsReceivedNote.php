<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoodsReceivedNote extends BaseModel
{
    use SoftDeletes;

    /**
     * Statuses used across Livewire screens.
     *
     * NOTE: This column is a plain string (no DB enum) so we unify conventions in code.
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_INSPECTING = 'inspecting';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_COMPLETE = 'complete';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

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
        'inspection_notes',
        'rejection_reason',
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
            // Ensure required FK values are present.
            // Many UI screens only provide purchase_id; derive supplier/warehouse/branch from it.
            if ($model->purchase_id && (! $model->branch_id || ! $model->warehouse_id || ! $model->supplier_id)) {
                $purchase = Purchase::query()
                    ->select(['id', 'branch_id', 'warehouse_id', 'supplier_id'])
                    ->find($model->purchase_id);

                if ($purchase) {
                    $model->branch_id = $model->branch_id ?: $purchase->branch_id;
                    $model->supplier_id = $model->supplier_id ?: $purchase->supplier_id;
                    $model->warehouse_id = $model->warehouse_id ?: $purchase->warehouse_id;
                }
            }

            // Warehouse is NOT nullable in schema; pick a safe default if still empty.
            if (! $model->warehouse_id && $model->branch_id) {
                $fallbackWarehouseId = Warehouse::query()
                    ->where('branch_id', $model->branch_id)
                    ->value('id');

                if ($fallbackWarehouseId) {
                    $model->warehouse_id = $fallbackWarehouseId;
                }
            }

            if (! $model->reference_number) {
                $model->reference_number = static::generateReferenceNumber();
            }

            if (! $model->received_date) {
                $model->received_date = today();
            }

            if (! $model->received_by && auth()->check()) {
                $model->received_by = auth()->id();
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

    // Scopes
    public function scopePendingInspection(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_INSPECTING]);
    }

    public function scopeApproved(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereIn('status', [self::STATUS_APPROVED, self::STATUS_COMPLETE]);
    }

    // Business Logic
    public function approve(int $approvedBy): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'inspected_by' => $approvedBy,
            'inspected_at' => now(),
        ]);
    }

    public function reject(int $rejectedBy, string $reason): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'inspected_by' => $rejectedBy,
            'inspected_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    public function canBeApproved(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_INSPECTING], true);
    }

    public function getTotalQuantityReceived(): float
    {
        return decimal_float($this->items->sum('received_quantity'), 4);
    }

    public function getTotalQuantityAccepted(): float
    {
        return decimal_float($this->items->sum('accepted_quantity'), 4);
    }

    public function getTotalQuantityRejected(): float
    {
        return decimal_float($this->items->sum('rejected_quantity'), 4);
    }

    public function hasDiscrepancies(): bool
    {
        return $this->items->contains(function ($item) {
            return $item->received_quantity != $item->expected_quantity || $item->rejected_quantity > 0;
        });
    }
}