<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class BranchModule extends Pivot
{
    use HasFactory;

    protected $table = 'branch_modules';

    /**
     * Pivot between branches and modules.
     *
     * Columns are designed to be flexible:
     * - branch_id   : int
     * - module_id   : int|null (FK to modules table)
     * - module_key  : string (stable key, e.g. "inventory")
     * - enabled     : bool
     * - settings    : json
     */
    protected $fillable = [
        'branch_id',
        'module_id',
        'module_key',
        'enabled',
        'settings',
        'activation_constraints',
        'permission_overrides',
        'inherit_settings',
        'activated_at',
        'is_enabled',
    ];

    protected $casts = [
        'enabled' => 'bool',
        'settings' => 'array',
        'activation_constraints' => 'array',
        'permission_overrides' => 'array',
        'inherit_settings' => 'bool',
        'activated_at' => 'datetime',
    ];

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = true;

    /** Relationships */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    /** Accessors / Mutators for backwards compatibility */
    public function getIsEnabledAttribute(): bool
    {
        return (bool) ($this->attributes['enabled'] ?? false);
    }

    public function setIsEnabledAttribute($value): void
    {
        $this->attributes['enabled'] = (bool) $value;
    }

    public function getModuleKeyAttribute(): ?string
    {
        if (array_key_exists('module_key', $this->attributes) && $this->attributes['module_key']) {
            return $this->attributes['module_key'];
        }

        return $this->relationLoaded('module') && $this->module
            // DB-first: canonical column in `modules` is `module_key`.
            ? $this->module->module_key
            : null;
    }

    /**
     * Check if activation constraints are satisfied
     */
    public function constraintsSatisfied(array $context = []): bool
    {
        if (empty($this->activation_constraints)) {
            return true;
        }

        foreach ($this->activation_constraints as $key => $value) {
            if (! isset($context[$key]) || $context[$key] !== $value) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get effective settings (with inheritance)
     */
    public function getEffectiveSettings(): array
    {
        $settings = $this->settings ?? [];

        if ($this->inherit_settings && $this->module) {
            $defaultSettings = $this->module->default_settings ?? [];
            $settings = array_merge($defaultSettings, $settings);
        }

        return $settings;
    }

    /**
     * Get permission overrides for this branch-module combination
     */
    public function getPermissionOverride(string $permission, $default = null)
    {
        return $this->permission_overrides[$permission] ?? $default;
    }
}
