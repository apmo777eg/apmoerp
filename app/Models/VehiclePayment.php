<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehiclePayment extends BaseModel
{
    protected ?string $moduleKey = 'vehicles';

    protected $fillable = ['contract_id', 'branch_id', 'method', 'amount', 'paid_at', 'reference', 'extra_attributes'];

    protected $casts = ['amount' => 'decimal:2', 'paid_at' => 'datetime'];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(VehicleContract::class, 'contract_id');
    }
}
