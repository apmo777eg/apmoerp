<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class PurchaseRequisition extends BaseModel
{
    protected ?string $moduleKey = 'purchases';

    protected $table = 'purchase_requisitions';

    /**
     * Fillable aligned with migrations:
     * - 2026_01_04_000002_create_purchases_tables.php
     * - 2026_02_16_000004_add_subject_and_cost_center_to_purchase_requisitions_table.php
     */
    protected $fillable = [
        'branch_id',
        'code',
        'subject',
        'department_id',
        'cost_center_id',
        'requested_by',
        'status',
        'priority',
        'required_date',
        'justification',
        'notes',
        'estimated_total',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'is_converted',
        'converted_to_po_id',
        'extra_attributes',
        'created_by',
        'updated_by',
        'requisition_code',
        'required_by',
    ];

    protected $casts = [
        'required_date' => 'date',
        'estimated_total' => 'decimal:4',
        'approved_at' => 'datetime',
        'is_converted' => 'boolean',
        'extra_attributes' => 'array',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (self $model): void {
            // BaseModel may have already set a generic code like REC-xxxx.
            // Ensure requisitions follow their own sequential pattern.
            $needsGeneratedCode = ! $model->code || ! preg_match('/^REQ-\d{8}-\d{5}$/', (string) $model->code);

            if ($needsGeneratedCode) {
                $model->code = DB::transaction(function () {
                    $lastReq = static::whereDate('created_at', today())
                        ->lockForUpdate()
                        ->orderBy('id', 'desc')
                        ->first();

                    $seq = 1;
                    if ($lastReq && preg_match('/REQ-\d{8}-(\d{5})$/', (string) $lastReq->code, $matches)) {
                        $seq = ((int) $matches[1]) + 1;
                    }

                    return 'REQ-'.date('Ymd').'-'.str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
                });
            }
        });
    }

    /**
     * Backward compatibility: some screens refer to requisition_code.
     */
    public function getRequisitionCodeAttribute(): ?string
    {
        return $this->code;
    }

    public function setRequisitionCodeAttribute(?string $value): void
    {
        $this->attributes['code'] = $value;
    }

    /**
     * Backward compatibility: some screens refer to required_by.
     */
    public function getRequiredByAttribute()
    {
        return $this->required_date;
    }

    public function setRequiredByAttribute($value): void
    {
        $this->attributes['required_date'] = $value;
    }

    // Relationships
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Canonical relationship name.
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Alias used by some legacy views/components.
     */
    public function employee(): BelongsTo
    {
        return $this->requestedBy();
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class, 'cost_center_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseRequisitionItem::class, 'requisition_id');
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class, 'converted_to_po_id');
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(SupplierQuotation::class, 'requisition_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopePendingApproval(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        // Schema uses status = pending
        return $query->where('status', 'pending');
    }

    public function scopeApproved(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', 'approved');
    }

    public function scopeNotConverted(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_converted', false);
    }

    public function scopeUrgent(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereIn('priority', ['high', 'urgent']);
    }

    // Business Logic
    public function approve(?int $approvedBy = null): void
    {
        $approvedBy = $approvedBy ?? (function_exists('actual_user_id') ? actual_user_id() : auth()->id());

        $this->update([
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);
    }

    public function reject(?string $reason = null, ?int $rejectedBy = null): void
    {
        $rejectedBy = $rejectedBy ?? (function_exists('actual_user_id') ? actual_user_id() : auth()->id());

        $this->update([
            'status' => 'rejected',
            'approved_by' => $rejectedBy,
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    public function convertToPO(int $purchaseId): void
    {
        $this->update([
            'status' => 'converted',
            'is_converted' => true,
            'converted_to_po_id' => $purchaseId,
        ]);
    }

    public function canBeApproved(): bool
    {
        return in_array($this->status, ['draft', 'pending'], true);
    }

    public function canBeConverted(): bool
    {
        return $this->status === 'approved' && ! $this->is_converted;
    }
}
