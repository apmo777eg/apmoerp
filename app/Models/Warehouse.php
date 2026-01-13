<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;

class Warehouse extends BaseModel
{
    protected ?string $moduleKey = 'inventory';

    protected $table = 'warehouses';

    /**
     * Fillable fields aligned with migration:
     * 2026_01_04_000003_create_inventory_tables.php
     */
    protected $fillable = [
        'branch_id',
        'name',
        'name_ar',
        'code',
        'type',
        'address',
        'phone',
        'manager_id',
        'is_active',
        'is_default',
        'allow_negative_stock',
        'settings',
        // For BaseModel compatibility
        'extra_attributes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'allow_negative_stock' => 'boolean',
        'settings' => 'array',
        'extra_attributes' => 'array',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function ($m) {
            $m->code = $m->code ?: 'WH-'.Str::upper(Str::random(6));
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function products(): HasManyThrough
    {
        return $this->hasManyThrough(Product::class, StockMovement::class, 'warehouse_id', 'id', 'id', 'product_id')->distinct();
    }

    public function transfersFrom(): HasMany
    {
        return $this->hasMany(Transfer::class, 'from_warehouse_id');
    }

    public function transfersTo(): HasMany
    {
        return $this->hasMany(Transfer::class, 'to_warehouse_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    // V7-HIGH-N04 FIX: Group OR conditions to prevent branch isolation bypass
    // Without grouping, SQL becomes: (branch_id = X AND name like ...) OR code like ...
    // which can match warehouses from other branches via the OR clause
    public function scopeSearch(Builder $query, $t): Builder
    {
        return $query->where(fn ($q) => $q->where('name', 'like', "%$t%")->orWhere('code', 'like', "%$t%"));
    }

    // Backward compatibility accessor
    public function getStatusAttribute(): string
    {
        return $this->is_active ? 'active' : 'inactive';
    }
}
