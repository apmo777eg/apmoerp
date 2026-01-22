<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BomOperation extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'bom_id',
        'branch_id',
        'work_center_id',
        'operation_name',
        'operation_name_ar',
        'description',
        'sequence',
        'duration_minutes',
        'setup_time_minutes',
        'labor_cost',
        'quality_criteria',
        'metadata',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'duration_minutes' => 'decimal:2',
        'setup_time_minutes' => 'decimal:2',
        'labor_cost' => 'decimal:2',
        'quality_criteria' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the BOM that owns the operation.
     */
    public function bom(): BelongsTo
    {
        return $this->belongsTo(BillOfMaterial::class, 'bom_id');
    }

    /**
     * Get the work center.
     */
    public function workCenter(): BelongsTo
    {
        return $this->belongsTo(WorkCenter::class);
    }

    /**
     * Calculate total time including setup.
     */
    public function getTotalTimeAttribute(): float
    {
        return decimal_float($this->duration_minutes) + decimal_float($this->setup_time_minutes);
    }

    /**
     * Calculate operation cost.
     */
    public function calculateCost(float $quantity = 1.0): float
    {
        $timeHours = $this->total_time / 60;
        $workCenterCost = $timeHours * decimal_float($this->workCenter->cost_per_hour);
        $laborCost = decimal_float($this->labor_cost);

        return ($workCenterCost + $laborCost) * $quantity;
    }
}
