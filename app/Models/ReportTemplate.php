<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * ReportTemplate - System report templates configuration
 *
 * SECURITY NOTE (V58-IDOR-01): This model intentionally does NOT use branch scoping.
 * ReportTemplate is a global configuration resource defining available report types.
 * Access is controlled via permission checks (reports.templates.manage) rather than branch isolation.
 * This is by design, as report templates are system-wide definitions.
 */
class ReportTemplate extends Model
{
    use HasFactory;

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'name',
        'description',
        'route_name',
        'default_filters',
        'output_type',
        'export_columns',
        'is_active',
        'module',
        'category',
        'required_permission',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'default_filters' => 'array',
        'export_columns' => 'array',
        'is_active' => 'bool',
    ];
}
