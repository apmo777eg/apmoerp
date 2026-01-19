<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductFieldValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'module_product_field_id',
        'value',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(ModuleProductField::class, 'module_product_field_id');
    }

    public function getTypedValueAttribute()
    {
        $field = $this->field;

        if (! $field) {
            return $this->value;
        }

        return match ($field->field_type) {
            'number' => (int) $this->value,
            'decimal' => decimal_float($this->value),
            'checkbox' => (bool) $this->value,
            'date' => $this->value ? \Carbon\Carbon::parse($this->value) : null,
            'datetime' => $this->value ? \Carbon\Carbon::parse($this->value) : null,
            'multiselect' => json_decode($this->value, true) ?? [],
            default => $this->value,
        };
    }
}
