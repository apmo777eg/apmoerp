<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends BaseModel
{
    protected ?string $moduleKey = 'settings';

    protected $table = 'departments';

    protected $fillable = [
        'branch_id',
        'name',
        'name_ar',
        'code',
        'description',
        'parent_id',
        'manager_id',
        'is_active',
        'sort_order',
        'extra_attributes',
    ];

    protected $casts = [
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

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
