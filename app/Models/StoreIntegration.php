<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreIntegration extends BaseModel
{
    protected $fillable = [
        'store_id',
        'branch_id',
        'platform',
        'api_key',
        'api_secret',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'webhook_secret',
        'webhooks_registered',
        'permissions',
        'is_active',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'webhooks_registered' => 'boolean',
        'permissions' => 'array',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'api_key',
        'api_secret',
        'access_token',
        'refresh_token',
        'webhook_secret',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function isTokenExpired(): bool
    {
        if (! $this->token_expires_at) {
            return false;
        }

        return $this->token_expires_at->isPast();
    }

    public function hasPermission(string $permission): bool
    {
        if (! $this->permissions) {
            return true;
        }

        return in_array($permission, $this->permissions, true);
    }
}
