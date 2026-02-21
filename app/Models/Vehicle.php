<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends BaseModel
{
    protected ?string $moduleKey = 'vehicles';

    protected $fillable = ['branch_id', 'name', 'vin', 'plate', 'brand', 'model', 'year', 'color', 'status', 'sale_price', 'cost', 'extra_attributes'];

    protected $casts = ['year' => 'int', 'sale_price' => 'decimal:2', 'cost' => 'decimal:2', 'extra_attributes' => 'array'];

    protected $appends = ['name'];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(VehicleContract::class);
    }

    public function warranties(): HasMany
    {
        return $this->hasMany(Warranty::class);
    }
    public function getNameAttribute(): ?string
    {
        $attrs = $this->extra_attributes;
        if (is_array($attrs) && isset($attrs['name']) && $attrs['name'] !== '') {
            return (string) $attrs['name'];
        }

        $parts = array_filter([(string) ($this->brand ?? ''), (string) ($this->model ?? '')], fn ($v) => $v !== '');
        if (! empty($parts)) {
            return trim(implode(' ', $parts));
        }

        return $this->plate ?? $this->vin ?? null;
    }

    public function setNameAttribute($value): void
    {
        $attrs = $this->extra_attributes;
        if (! is_array($attrs)) {
            $attrs = [];
        }

        if ($value === null || $value === '') {
            unset($attrs['name']);
        } else {
            $attrs['name'] = (string) $value;
        }

        $this->extra_attributes = $attrs;
    }


}
