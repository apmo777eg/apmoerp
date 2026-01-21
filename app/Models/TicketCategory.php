<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * TicketCategory - Helpdesk ticket category configuration
 *
 * SECURITY NOTE (V58-IDOR-01): This model intentionally does NOT use branch scoping.
 * TicketCategory is a global configuration resource managed at the organization level.
 * Access is controlled via permission checks (helpdesk.manage) rather than branch isolation.
 * This is by design, as categories should be consistent across all branches.
 */
class TicketCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ticket_categories';

    protected $fillable = [
        'name',
        'name_ar',
        'slug',
        'description',
        'parent_id',
        'default_assignee_id',
        'sla_policy_id',
        'color',
        'icon',
        'is_active',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Relationships
    public function parent()
    {
        return $this->belongsTo(TicketCategory::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(TicketCategory::class, 'parent_id');
    }

    public function defaultAssignee()
    {
        return $this->belongsTo(User::class, 'default_assignee_id');
    }

    public function slaPolicy()
    {
        return $this->belongsTo(TicketSLAPolicy::class, 'sla_policy_id');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'category_id');
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeParents(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // Business Methods
    public function getTicketCount(): int
    {
        return $this->tickets()->count();
    }

    public function getFullPath(): string
    {
        $path = [$this->name];
        $category = $this;

        while ($category->parent) {
            $category = $category->parent;
            array_unshift($path, $category->name);
        }

        return implode(' > ', $path);
    }

    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }
}
