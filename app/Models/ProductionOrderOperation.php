<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionOrderOperation extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'production_order_id',
        'branch_id',
        'bom_operation_id',
        'work_center_id',
        'operation_name',
        'sequence',
        'status',
        'planned_duration_minutes',
        'actual_duration_minutes',
        'started_at',
        'completed_at',
        'operator_id',
        'notes',
        'quality_results',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'planned_duration_minutes' => 'decimal:2',
        'actual_duration_minutes' => 'decimal:2',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'quality_results' => 'array',
    ];

    /**
     * Get the production order.
     */
    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    /**
     * Get the BOM operation.
     */
    public function bomOperation(): BelongsTo
    {
        return $this->belongsTo(BomOperation::class);
    }

    /**
     * Get the work center.
     */
    public function workCenter(): BelongsTo
    {
        return $this->belongsTo(WorkCenter::class);
    }

    /**
     * Get the operator.
     */
    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    /**
     * Calculate duration variance.
     */
    public function getDurationVarianceAttribute(): float
    {
        return $this->actual_duration_minutes - $this->planned_duration_minutes;
    }

    /**
     * Start operation.
     */
    public function start(int $operatorId): void
    {
        $this->update([
            'status' => 'in_progress',
            'started_at' => now(),
            'operator_id' => $operatorId,
        ]);
    }

    /**
     * Complete operation.
     */
    public function complete(array $qualityResults = []): void
    {
        $duration = $this->started_at ? now()->diffInMinutes($this->started_at) : 0;

        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'actual_duration_minutes' => $duration,
            'quality_results' => $qualityResults,
        ]);
    }

    /**
     * Put operation on hold.
     */
    public function hold(string $reason): void
    {
        $this->update([
            'status' => 'on_hold',
            'notes' => ($this->notes ?? '')."\n[HOLD] {$reason}",
        ]);
    }
}
