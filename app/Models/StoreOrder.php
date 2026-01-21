<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * StoreOrder - E-commerce store order model
 *
 * V56-CRITICAL-03 FIX: Added HasBranch trait for proper multi-branch scoping.
 * Store orders must be isolated by branch to prevent cross-branch data leakage.
 */
class StoreOrder extends Model
{
    use HasBranch;
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'external_order_id',
        'status',
        'branch_id',
        'currency',
        'total',
        'discount_total',
        'shipping_total',
        'tax_total',
        'payload',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'payload' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (StoreOrder $order): void {
            if (! $order->external_order_id) {
                $order->external_order_id = 'LOCAL-'.uniqid();
            }

            if ($order->payload === null) {
                $order->payload = [];
            }
        });
    }

    public function sale()
    {
        return $this->hasOne(Sale::class);
    }

    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }

    public function getSourceAttribute(): ?string
    {
        $payload = $this->payload;

        if (! is_array($payload)) {
            return null;
        }

        $meta = $payload['meta'] ?? null;

        if (! is_array($meta)) {
            return null;
        }

        $source = $meta['source'] ?? null;

        return is_string($source) ? $source : null;
    }
}
