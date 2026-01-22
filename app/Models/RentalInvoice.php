<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RentalInvoice extends BaseModel
{
    protected ?string $moduleKey = 'rentals';

    protected $fillable = ['contract_id', 'branch_id', 'code', 'period', 'due_date', 'amount', 'paid_total', 'status', 'extra_attributes'];

    protected $casts = ['amount' => 'decimal:2', 'paid_total' => 'decimal:2', 'due_date' => 'date'];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(RentalContract::class, 'contract_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(RentalPayment::class, 'invoice_id');
    }

    public function scopeForBranch(Builder $query, $branch): Builder
    {
        $id = is_object($branch) ? $branch->getKey() : $branch;

        return $query->whereHas('contract', function ($q) use ($id) {
            $q->where('branch_id', $id);
        });
    }
}
