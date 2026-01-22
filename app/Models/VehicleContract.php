<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleContract extends BaseModel
{
    protected ?string $moduleKey = 'vehicles';

    protected $fillable = ['vehicle_id', 'branch_id', 'customer_id', 'start_date', 'end_date', 'price', 'status', 'extra_attributes'];

    protected $casts = ['start_date' => 'date', 'end_date' => 'date', 'price' => 'decimal:2'];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(VehiclePayment::class, 'contract_id');
    }
}
