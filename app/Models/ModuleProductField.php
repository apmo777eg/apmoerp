<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModuleProductField extends Model
{
    use HasFactory;

    /**
     * Canonical schema (DB-first):
     * - field_label / field_type / field_options
     */
    protected $fillable = [
        'module_id',
        'field_key',
        'field_label',
        'field_label_ar',
        'field_type',
        'field_options',
        'placeholder',
        'placeholder_ar',
        'default_value',
        'validation_rules',
        'is_required',
        'is_searchable',
        'is_filterable',
        'show_in_list',
        'show_in_form',
        'is_active',
        'sort_order',
        'field_group',
    ];

    protected $casts = [
        'field_options' => 'array',
        'is_required' => 'boolean',
        'is_searchable' => 'boolean',
        'is_filterable' => 'boolean',
        'show_in_list' => 'boolean',
        'show_in_form' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(ProductFieldValue::class);
    }

    public function getLocalizedLabelAttribute(): string
    {
        $locale = app()->getLocale();

        return $locale === 'ar' && $this->field_label_ar ? $this->field_label_ar : $this->field_label;
    }

    public function getLocalizedPlaceholderAttribute(): ?string
    {
        $locale = app()->getLocale();

        return $locale === 'ar' && $this->placeholder_ar ? $this->placeholder_ar : $this->placeholder;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForList(Builder $query): Builder
    {
        return $query->where('show_in_list', true);
    }

    public function scopeForForm(Builder $query): Builder
    {
        return $query->where('show_in_form', true);
    }

    public function scopeSearchable(Builder $query): Builder
    {
        return $query->where('is_searchable', true);
    }

    public function scopeFilterable(Builder $query): Builder
    {
        return $query->where('is_filterable', true);
    }
}
