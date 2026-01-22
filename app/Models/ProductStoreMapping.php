<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductStoreMapping extends BaseModel
{
    protected $fillable = [
        'product_id',
        'branch_id',
        'store_id',
        'external_id',
        'external_sku',
        'external_data',
        'last_synced_at',
    ];

    protected $casts = [
        'external_data' => 'array',
        'last_synced_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function markSynced(): void
    {
        $this->update(['last_synced_at' => now()]);
    }
}
