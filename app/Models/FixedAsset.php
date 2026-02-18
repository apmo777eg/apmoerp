<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FixedAsset extends BaseModel
{
    protected ?string $moduleKey = 'assets';

    protected $table = 'fixed_assets';

    protected $fillable = [
        'branch_id',
        'supplier_id',

        'asset_code',
        'name',
        'description',
        'notes',

        'category',
        'location',

        'purchase_date',
        'purchase_cost',
        'book_value',
        'salvage_value',

        'useful_life_months',
        'depreciation_method',
        'depreciation_start_date',
        'depreciation_rate',
        'last_depreciation_date',

        'accumulated_depreciation',
        'status',

        'assigned_to',
        'serial_number',
        'model',
        'manufacturer',
        'warranty_expiry',

        'custom_fields',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'depreciation_start_date' => 'date',
        'last_depreciation_date' => 'date',
        'warranty_expiry' => 'date',

        'purchase_cost' => 'decimal:4',
        'book_value' => 'decimal:4',
        'salvage_value' => 'decimal:4',
        'accumulated_depreciation' => 'decimal:4',
        'depreciation_rate' => 'decimal:4',

        'custom_fields' => 'array',
    ];

    public const DEPRECIATION_STRAIGHT_LINE = 'straight_line';
    public const DEPRECIATION_DECLINING_BALANCE = 'declining_balance';
    public const DEPRECIATION_UNITS_OF_PRODUCTION = 'units_of_production';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_DISPOSED = 'disposed';
    public const STATUS_FULLY_DEPRECIATED = 'fully_depreciated';

    protected static function booted(): void
    {
        static::creating(function (FixedAsset $asset) {
            if (! $asset->asset_code) {
                $asset->asset_code = $asset->generateAssetCode();
            }

            // Initialize book value when creating
            if ($asset->book_value === null) {
                $asset->book_value = $asset->calculateBookValue();
            }
        });

        static::saving(function (FixedAsset $asset) {
            // Keep book_value consistent if purchase_cost / accumulated_depreciation changed.
            $asset->book_value = $asset->calculateBookValue();
        });
    }

    // Relationships
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function depreciations(): HasMany
    {
        return $this->hasMany(AssetDepreciation::class, 'asset_id');
    }

    public function maintenanceRecords(): HasMany
    {
        return $this->hasMany(AssetMaintenanceLog::class, 'asset_id');
    }

    // Business logic
    public function generateAssetCode(): string
    {
        $prefix = 'FA-' . date('Ym') . '-';

        $count = static::query()
            ->when($this->branch_id, fn ($q) => $q->where('branch_id', $this->branch_id))
            ->where('asset_code', 'like', $prefix . '%')
            ->count();

        return $prefix . str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }

    public function calculateBookValue(): float
    {
        $purchaseCost = decimal_float($this->purchase_cost ?? 0, 4);
        $accumulated = decimal_float($this->accumulated_depreciation ?? 0, 4);

        return max(0.0, $purchaseCost - $accumulated);
    }

    public function isFullyDepreciated(): bool
    {
        $purchaseCost = decimal_float($this->purchase_cost ?? 0, 4);
        $salvageValue = decimal_float($this->salvage_value ?? 0, 4);

        return $this->accumulated_depreciation >= ($purchaseCost - $salvageValue);
    }

    public function getTotalUsefulLifeMonths(): int
    {
        return (int) ($this->useful_life_months ?? 0);
    }

    /**
     * Calculate straight-line depreciation per month.
     */
    public function getMonthlyDepreciation(): float
    {
        $purchaseCost = decimal_float($this->purchase_cost ?? 0, 4);
        $salvageValue = decimal_float($this->salvage_value ?? 0, 4);

        $depreciableAmount = max(0, $purchaseCost - $salvageValue);
        $totalMonths = $this->getTotalUsefulLifeMonths();

        return $totalMonths > 0 ? $depreciableAmount / $totalMonths : 0.0;
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeDisposed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DISPOSED);
    }

    public function scopeFullyDepreciated(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_FULLY_DEPRECIATED);
    }

    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }
}
