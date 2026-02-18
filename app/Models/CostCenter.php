<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CostCenter extends BaseModel
{
    protected ?string $moduleKey = 'settings';

    protected $table = 'cost_centers';

    protected $fillable = [
        'branch_id',
        'name',
        'name_ar',
        'code',
        'description',
        'parent_id',
        'department_id',
        'budget',
        'budget_period',
        'is_active',
        'sort_order',
        'extra_attributes',
    ];

    protected $casts = [
        'budget' => 'decimal:4',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'extra_attributes' => 'array',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
