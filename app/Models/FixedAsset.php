<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FixedAsset extends BaseModel
{
    /**
     * Fillable fields aligned with migration:
     * 2026_01_04_000007_create_accounting_tables.php
     */
    protected $fillable = [
        'branch_id',
        'asset_code',
        'name',
        'name_ar',
        'category',
        'description',
        'serial_number',
        'location',
        'assigned_to',
        'purchase_date',
        'purchase_cost',
        'salvage_value',
        'useful_life_months',
        'depreciation_method',
        'accumulated_depreciation',
        'current_value',
        'asset_account_id',
        'depreciation_account_id',
        'expense_account_id',
        'status',
        'disposal_date',
        'disposal_value',
        'disposal_notes',
        'last_maintenance_date',
        'next_maintenance_date',
        'maintenance_notes',
        'warranty_expiry',
        'warranty_vendor',
        'custom_fields',
    ];

    protected $attributes = [
        'purchase_cost' => 0,
        'salvage_value' => 0,
        'accumulated_depreciation' => 0,
        'current_value' => 0,
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'purchase_cost' => 'decimal:4',
        'salvage_value' => 'decimal:4',
        'accumulated_depreciation' => 'decimal:4',
        'current_value' => 'decimal:4',
        'disposal_date' => 'date',
        'disposal_value' => 'decimal:4',
        'warranty_expiry' => 'date',
        'last_maintenance_date' => 'date',
        'next_maintenance_date' => 'date',
        'custom_fields' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($asset) {
            if (! $asset->asset_code) {
                // Generate unique asset code using timestamp and random component
                // Format: FA-YYYYMMDD-HHMMSS-RAND
                $asset->asset_code = 'FA-'.date('Ymd-His').'-'.strtoupper(substr(uniqid(), -4));
            }

            if (! $asset->current_value) {
                $asset->current_value = $asset->purchase_cost;
            }
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assetAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'asset_account_id');
    }

    public function depreciationAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'depreciation_account_id');
    }

    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }

    public function depreciations(): HasMany
    {
        return $this->hasMany(AssetDepreciation::class, 'asset_id');
    }

    public function maintenanceLogs(): HasMany
    {
        return $this->hasMany(AssetMaintenanceLog::class, 'asset_id');
    }

    // Backward compatibility accessors
    public function getBookValueAttribute()
    {
        return $this->current_value;
    }

    public function getDisposalAmountAttribute()
    {
        return $this->disposal_value;
    }

    public function getUsefulLifeYearsAttribute()
    {
        return (int) floor(($this->useful_life_months ?? 0) / 12);
    }

    public function getMetaAttribute()
    {
        return $this->custom_fields;
    }

    /**
     * Check if asset is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if asset is fully depreciated
     */
    public function isFullyDepreciated(): bool
    {
        $currentValue = decimal_float($this->current_value ?? 0);
        $salvageValue = decimal_float($this->salvage_value ?? 0);

        return $currentValue <= $salvageValue;
    }

    /**
     * Get total useful life in months
     */
    public function getTotalUsefulLifeMonths(): int
    {
        return (int) ($this->useful_life_months ?? 0);
    }

    /**
     * Calculate straight-line depreciation per month
     */
    public function getMonthlyDepreciation(): float
    {
        $purchaseCost = decimal_float($this->purchase_cost ?? 0);
        $salvageValue = decimal_float($this->salvage_value ?? 0);

        // Prevent negative depreciable amounts when salvage exceeds purchase cost
        $depreciableAmount = max(0, $purchaseCost - $salvageValue);
        $totalMonths = $this->getTotalUsefulLifeMonths();

        return $totalMonths > 0 ? $depreciableAmount / $totalMonths : 0;
    }

    /**
     * Scope for active assets
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for disposed assets
     */
    public function scopeDisposed(Builder $query): Builder
    {
        return $query->where('status', 'disposed');
    }

    /**
     * Scope for fully depreciated assets
     */
    public function scopeFullyDepreciated(Builder $query): Builder
    {
        return $query->where('status', 'fully_depreciated');
    }

    /**
     * Scope for assets by category
     */
    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }
}
