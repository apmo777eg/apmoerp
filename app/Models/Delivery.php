<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Delivery extends BaseModel
{
    use SoftDeletes;

    protected ?string $moduleKey = 'sales';

    protected $table = 'deliveries';

    /**
     * Fillable fields aligned with migration:
     * 2026_01_04_000005_create_sales_purchases_tables.php
     */
    protected $fillable = [
        'branch_id',
        'sale_id',
        'reference_number',
        'status',
        'scheduled_date',
        'delivery_date',
        'delivery_address',
        'recipient_name',
        'recipient_phone',
        'driver_name',
        'vehicle_number',
        'shipping_cost',
        'notes',
        'signature_image',
        'delivered_by',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'delivery_date' => 'date',
        'shipping_cost' => 'decimal:4',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function deliveredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivered_by');
    }

    // Backward compatibility accessors
    public function getDeliveredAtAttribute()
    {
        return $this->delivery_date;
    }

    // Scopes
    public function scopePending(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeDispatched(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', 'dispatched');
    }

    public function scopeDelivered(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', 'delivered');
    }
}
