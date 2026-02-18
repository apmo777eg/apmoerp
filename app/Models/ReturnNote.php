<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * ReturnNote Model - Simple returns (legacy)
 * 
 * For simple return tracking. For advanced returns with credit notes,
 * approval workflows, and detailed tracking, use SalesReturn model.
 * 
 * @property int $id
 * @property int $branch_id
 * @property string $reference_number
 * @property string $type (sale_return|purchase_return)
 * @property int|null $sale_id
 * @property int|null $purchase_id
 * @property int|null $customer_id
 * @property int|null $supplier_id
 * @property int|null $warehouse_id
 * @property string $status
 * @property \Carbon\Carbon $return_date
 * @property string|null $reason
 * @property float $total_amount
 * @property string|null $refund_method
 * @property bool $restock_items
 * @property int|null $processed_by
 */
class ReturnNote extends BaseModel
{
protected ?string $moduleKey = 'sales';

    protected $table = 'return_notes';

    /**
     * Fillable fields aligned with migration:
     * 2026_01_04_000005_create_sales_purchases_tables.php
     */
    protected $fillable = [
        'branch_id',
        'reference_number',
        'type',
        'sale_id',
        'purchase_id',
        'customer_id',
        'supplier_id',
        'warehouse_id',
        'status',
        'return_date',
        'reason',
        'total_amount',
        'refund_method',
        'restock_items',
        'processed_by',
    ];

    protected $casts = [
        'return_date' => 'date',
        'total_amount' => 'decimal:4',
        'restock_items' => 'boolean',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (self $model): void {
            if ($model->reference_number) {
                return;
            }

            $model->reference_number = DB::transaction(function () use ($model) {
                $prefix = 'RTN-'.date('Ymd').'-';

                $last = static::query()
                    ->when($model->branch_id, fn ($q) => $q->where('branch_id', $model->branch_id))
                    ->where('reference_number', 'like', $prefix.'%')
                    ->lockForUpdate()
                    ->orderByDesc('id')
                    ->first();

                $seq = 1;
                if ($last && preg_match('/RTN-\d{8}-(\d{5})$/', (string) $last->reference_number, $matches)) {
                    $seq = ((int) $matches[1]) + 1;
                }

                return $prefix.str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
            });
        });
    }


    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_REJECTED = 'rejected';

    // Type constants
    public const TYPE_SALE = 'sale_return';
    public const TYPE_PURCHASE = 'purchase_return';

    /**
     * Relationships
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Scopes
     */
    public function scopeSaleReturns(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('type', self::TYPE_SALE);
    }

    public function scopePurchaseReturns(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('type', self::TYPE_PURCHASE);
    }

    public function scopePending(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeCompleted(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Helper Methods
     */
    public function isSaleReturn(): bool
    {
        return $this->type === self::TYPE_SALE;
    }

    public function isPurchaseReturn(): bool
    {
        return $this->type === self::TYPE_PURCHASE;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function canBeApproved(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Mark as approved
     */
    public function approve(?int $userId = null): bool
    {
        if (!$this->canBeApproved()) {
            return false;
        }

        return $this->update([
            'status' => self::STATUS_APPROVED,
            // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
            'processed_by' => $userId ?? actual_user_id(),
        ]);
    }

    /**
     * Mark as completed
     */
    public function complete(?int $userId = null): bool
    {
        if ($this->status !== self::STATUS_APPROVED) {
            return false;
        }

        return $this->update([
            'status' => self::STATUS_COMPLETED,
            // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
            'processed_by' => $userId ?? actual_user_id(),
        ]);
    }

    /**
     * Mark as rejected
     */
    public function reject(?int $userId = null): bool
    {
        if (!$this->canBeApproved()) {
            return false;
        }

        return $this->update([
            'status' => self::STATUS_REJECTED,
            // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
            'processed_by' => $userId ?? actual_user_id(),
        ]);
    }

    // Backward compatibility accessor
    public function getTotalAttribute()
    {
        return $this->total_amount;
    }
}
