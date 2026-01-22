<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Warranty extends BaseModel
{
    protected ?string $moduleKey = 'vehicles';

    protected $table = 'warranties';

    protected $fillable = ['vehicle_id', 'branch_id', 'provider', 'start_date', 'end_date', 'notes', 'extra_attributes'];

    protected $casts = ['start_date' => 'date', 'end_date' => 'date'];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
