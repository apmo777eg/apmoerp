<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionOrder extends BaseModel
{
    protected ?string $moduleKey = 'manufacturing';

    protected $table = 'production_orders';

    protected $fillable = [
        'reference_number',
        'branch_id',
        'bom_id',
        'sale_id',
        'product_id',
        'warehouse_id',

        'planned_quantity',
        'produced_quantity',
        'rejected_quantity',

        'planned_start_date',
        'planned_end_date',
        'actual_start_date',
        'actual_end_date',

        'status',
        'priority',

        'estimated_cost',
        'actual_cost',
        'material_cost',
        'labor_cost',
        'overhead_cost',

        'notes',
        'custom_fields',

        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'planned_quantity' => 'decimal:4',
        'produced_quantity' => 'decimal:4',
        'rejected_quantity' => 'decimal:4',

        'planned_start_date' => 'date',
        'planned_end_date' => 'date',
        'actual_start_date' => 'datetime',
        'actual_end_date' => 'datetime',

        'estimated_cost' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'material_cost' => 'decimal:2',
        'labor_cost' => 'decimal:2',
        'overhead_cost' => 'decimal:2',

        'custom_fields' => 'array',
        'approved_at' => 'datetime',
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    protected static function booted(): void
    {
        static::creating(function (ProductionOrder $order) {
            if (! $order->reference_number) {
                $order->reference_number = $order->generateReferenceNumber();
            }
        });
    }

    // Relationships
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function bom(): BelongsTo
    {
        return $this->belongsTo(BillOfMaterial::class, 'bom_id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProductionOrderItem::class, 'production_order_id');
    }

    public function operations(): HasMany
    {
        return $this->hasMany(ProductionOrderOperation::class, 'production_order_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    // Helpers
    public function getDisplayNameAttribute(): string
    {
        return $this->reference_number ?: 'PO-' . $this->id;
    }


    public static function generateOrderNumber(int $branchId): string
    {
        $tmp = new static();
        $tmp->branch_id = $branchId;

        return $tmp->generateReferenceNumber();
    }

    public function generateReferenceNumber(): string
    {
        $prefix = 'PO-' . date('Ym') . '-';

        $count = static::query()
            ->when($this->branch_id, fn ($q) => $q->where('branch_id', $this->branch_id))
            ->where('reference_number', 'like', $prefix . '%')
            ->count();

        return $prefix . str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function getCompletionPercentage(): float
    {
        $planned = decimal_float($this->planned_quantity ?? 0, 4);
        if ($planned <= 0) {
            return 0.0;
        }

        $produced = decimal_float($this->produced_quantity ?? 0, 4);

        return min(100.0, ($produced / $planned) * 100);
    }

    public function canStart(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_DRAFT], true);
    }

    public function canComplete(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function canCancel(): bool
    {
        return ! $this->isCompleted() && ! $this->isCancelled();
    }

    public function calculateRequiredMaterials(): array
    {
        if (! $this->bom) {
            return [];
        }

        return $this->bom->calculateRequiredMaterials((float) ($this->planned_quantity ?? 0));
    }

    public function checkMaterialAvailability(): array
    {
        if (! $this->bom) {
            return [];
        }

        return $this->bom->checkMaterialAvailability((float) ($this->planned_quantity ?? 0), $this->warehouse_id);
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => __('Draft'),
            self::STATUS_PENDING => __('Pending'),
            self::STATUS_IN_PROGRESS => __('In Progress'),
            self::STATUS_COMPLETED => __('Completed'),
            self::STATUS_CANCELLED => __('Cancelled'),
            default => ucfirst(str_replace('_', ' ', (string) $this->status)),
        };
    }

    public function getPriorityLabel(): string
    {
        return match ($this->priority) {
            self::PRIORITY_LOW => __('Low'),
            self::PRIORITY_NORMAL => __('Normal'),
            self::PRIORITY_HIGH => __('High'),
            self::PRIORITY_URGENT => __('Urgent'),
            default => ucfirst((string) $this->priority),
        };
    }
}
