<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ProductCompatibility - Product to vehicle compatibility mapping
 *
 * SECURITY NOTE (V58-IDOR-01): This model does NOT use direct branch scoping.
 * ProductCompatibility links products to vehicle models. Branch isolation is achieved
 * indirectly through the Product relationship, which is branch-scoped.
 * Access is controlled via permission checks and product ownership.
 */
class ProductCompatibility extends Model
{
    protected $table = 'product_compatibilities';

    protected $fillable = [
        'product_id',
        'vehicle_model_id',
        'oem_number',
        'position',
        'notes',
        'is_verified',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function vehicleModel(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class);
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified', true);
    }

    public function scopeForProduct(Builder $query, int $productId): Builder
    {
        return $query->where('product_id', $productId);
    }

    public function scopeForVehicle(Builder $query, int $vehicleModelId): Builder
    {
        return $query->where('vehicle_model_id', $vehicleModelId);
    }

    public function scopeByOem(Builder $query, string $oemNumber): Builder
    {
        return $query->where('oem_number', 'like', "%{$oemNumber}%");
    }
}
