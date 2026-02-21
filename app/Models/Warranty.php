<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Warranty extends BaseModel
{
    protected ?string $moduleKey = 'vehicles';

    protected $table = 'warranties';

    protected $fillable = ['vehicle_id', 'branch_id', 'provider', 'start_date', 'end_date', 'start_at', 'end_at', 'notes', 'extra_attributes'];

    protected $casts = ['start_date' => 'date', 'end_date' => 'date', 'extra_attributes' => 'array'];

    protected $appends = ['start_at', 'end_at'];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
    // Backward compatibility accessors
    public function getStartAtAttribute()
    {
        return $this->start_date;
    }

    public function setStartAtAttribute($value): void
    {
        $this->attributes['start_date'] = $value;
    }

    public function getEndAtAttribute()
    {
        return $this->end_date;
    }

    public function setEndAtAttribute($value): void
    {
        $this->attributes['end_date'] = $value;
    }


}
