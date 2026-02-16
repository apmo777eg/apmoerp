<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    use HasFactory;

    protected $table = 'modules';

    protected $fillable = [
        'module_key',
        'slug',
        'name',
        'name_ar',
        'version',
        'is_core',
        'is_active',
        'description',
        'description_ar',
        'icon',
        'color',
        'sort_order',
        'default_settings',
        'pricing_type',
        'has_variations',
        'has_inventory',
        'has_serial_numbers',
        'has_expiry_dates',
        'has_batch_numbers',
        'is_rental',
        'is_service',
        'category',
        'module_type',
        'operation_config',
        'integration_hooks',
        'supports_reporting',
        'supports_custom_fields',
        'supports_items',
    ];

    protected $attributes = [
        'module_type' => 'data',
        'supports_reporting' => true,
        'supports_custom_fields' => true,
        'supports_items' => true,
        'has_inventory' => true,
    ];

    protected $casts = [
        'is_core' => 'bool',
        'is_active' => 'bool',
        'has_variations' => 'bool',
        'has_inventory' => 'bool',
        'has_serial_numbers' => 'bool',
        'has_expiry_dates' => 'bool',
        'has_batch_numbers' => 'bool',
        'is_rental' => 'bool',
        'is_service' => 'bool',
        'default_settings' => 'array',
        'operation_config' => 'array',
        'integration_hooks' => 'array',
        'supports_reporting' => 'bool',
        'supports_custom_fields' => 'bool',
        'supports_items' => 'bool',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function ($model): void {
            // Auto-generate slug from module_key if not provided
            if (empty($model->slug) && ! empty($model->module_key)) {
                $model->slug = $model->module_key;
            }
        });
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_modules')
            ->using(BranchModule::class)
            ->withPivot(['enabled', 'settings', 'module_key'])
            ->withTimestamps();
    }

    public function branchModules(): HasMany
    {
        return $this->hasMany(BranchModule::class);
    }

    public function customFields(): HasMany
    {
        return $this->hasMany(ModuleCustomField::class);
    }

    public function productFields(): HasMany
    {
        return $this->hasMany(ModuleProductField::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function rentalPeriods(): HasMany
    {
        return $this->hasMany(RentalPeriod::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(ModuleSetting::class);
    }

    public function reportDefinitions(): HasMany
    {
        return $this->hasMany(ReportDefinition::class);
    }

    public function policies(): HasMany
    {
        return $this->hasMany(ModulePolicy::class);
    }

    public function operations(): HasMany
    {
        return $this->hasMany(ModuleOperation::class);
    }

    public function navigation(): HasMany
    {
        return $this->hasMany(ModuleNavigation::class);
    }

    public function getLocalizedNameAttribute(): string
    {
        return app()->getLocale() === 'ar' && $this->name_ar ? $this->name_ar : $this->name;
    }

    public function getLocalizedDescriptionAttribute(): ?string
    {
        return app()->getLocale() === 'ar' && $this->description_ar ? $this->description_ar : $this->description;
    }

    /**
     * Resolve the module icon for display.
     *
     * Legacy installs may store icon *names* (e.g. "calculator", "user-group", "truck")
     * while newer installs store emojis. This accessor provides a safe, user-friendly
     * fallback so the UI always shows an icon.
     */
    public static function resolveIcon(?string $icon): string
    {
        $icon = trim((string) ($icon ?? ''));

        if ($icon === '') {
            return '📦';
        }

        // If it already looks like an emoji / non-ascii symbol, return as-is.
        // (This covers emojis such as "🏍️", "🧮", etc.)
        if (preg_match('/[^\x00-\x7F]/u', $icon)) {
            return $icon;
        }

        $key = strtolower($icon);

        $map = [
            // Common legacy icon names (Heroicons / FontAwesome-like)
            'calculator' => '🧮',
            'cash' => '💰',
            'money' => '💰',
            'currency' => '💰',
            'user-group' => '👥',
            'users' => '👥',
            'user' => '👤',
            'home' => '🏠',
            'folder' => '📁',
            'truck' => '🚚',
            'car' => '🚗',
            'motorcycle' => '🏍️',
            'cog' => '⚙️',
            'settings' => '⚙️',
            'ticket' => '🎫',
            'chart' => '📊',
            'report' => '📊',
            'box' => '📦',
            'inventory' => '📦',
            'warehouse' => '🏬',
            'building' => '🏢',
            'shop' => '🏪',
            'store' => '🏪',
            'cart' => '🛒',
            'bag' => '🛍️',
            'document' => '📄',
            'file' => '📄',
        ];

        return $map[$key] ?? '📦';
    }

    public function getDisplayIconAttribute(): string
    {
        return self::resolveIcon($this->icon);
    }


    public function hasBuyPrice(): bool
    {
        return in_array($this->pricing_type, ['buy_sell', 'cost_only']);
    }

    public function hasSellPrice(): bool
    {
        return in_array($this->pricing_type, ['buy_sell', 'sell_only']);
    }

    public function getSetting(string $key, $branchId = null, $default = null)
    {
        return ModuleSetting::getValue($this->id, $key, $branchId, $default);
    }

    public function setSetting(string $key, $value, $branchId = null, $type = 'string'): ModuleSetting
    {
        return ModuleSetting::setValue($this->id, $key, $value, $branchId, $type);
    }

    public function scopeCore(Builder $query): Builder
    {
        return $query->where('is_core', true);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeModuleKey(Builder $query, string $moduleKey): Builder
    {
        return $query->where('module_key', $moduleKey);
    }

    public function scopeSlug(Builder $query, string $slug): Builder
    {
        return $query->where('slug', $slug);
    }

    public function scopeRental(Builder $query): Builder
    {
        return $query->where('is_rental', true);
    }

    public function scopeService(Builder $query): Builder
    {
        return $query->where('is_service', true);
    }

    public function scopeWithInventory(Builder $query): Builder
    {
        return $query->where('has_inventory', true);
    }

    public function scopeCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('module_type', $type);
    }

    public function scopeDataOriented(Builder $query): Builder
    {
        return $query->where('module_type', 'data');
    }

    public function scopeFunctional(Builder $query): Builder
    {
        return $query->where('module_type', 'functional');
    }

    public function scopeSupportsReporting(Builder $query): Builder
    {
        return $query->where('supports_reporting', true);
    }

    public function scopeSupportsCustomFields(Builder $query): Builder
    {
        return $query->where('supports_custom_fields', true);
    }

    /**
     * Scope to get modules that support items/products
     */
    public function scopeSupportsItems(Builder $query): Builder
    {
        return $query->where('supports_items', true);
    }

    /**
     * Check if module is data-oriented
     */
    public function isDataOriented(): bool
    {
        return $this->module_type === 'data';
    }

    /**
     * Check if module supports items/products
     */
    public function supportsItems(): bool
    {
        return $this->supports_items === true;
    }

    /**
     * Check if module is functional
     */
    public function isFunctional(): bool
    {
        return $this->module_type === 'functional';
    }

    /**
     * Get the module type label based on supports_items and key
     */
    public function getModuleTypeLabel(): string
    {
        // Stock module (inventory) - tracks stock but doesn't create items
        if ($this->module_key === 'inventory') {
            return __('Stock Module');
        }

        // Product modules - create items/products
        if ($this->supports_items) {
            return __('Product Module');
        }

        // Operational modules - use products from other modules
        if (in_array($this->module_key, ['sales', 'purchases', 'pos'])) {
            return __('Operational Module');
        }

        // Management modules - administrative tasks
        return __('Management Module');
    }

    /**
     * Get the module type color class
     */
    public function getModuleTypeColor(): string
    {
        if ($this->module_key === 'inventory') {
            return 'bg-blue-100 text-blue-700';
        }

        if ($this->supports_items) {
            return 'bg-emerald-100 text-emerald-700';
        }

        if (in_array($this->module_key, ['sales', 'purchases', 'pos'])) {
            return 'bg-amber-100 text-amber-700';
        }

        return 'bg-slate-100 text-slate-700';
    }

    /**
     * Get operation configuration
     */
    public function getOperationConfig(string $key, $default = null)
    {
        return $this->operation_config[$key] ?? $default;
    }

    /**
     * Get integration hooks
     */
    public function getIntegrationHook(string $key, $default = null)
    {
        return $this->integration_hooks[$key] ?? $default;
    }
}
