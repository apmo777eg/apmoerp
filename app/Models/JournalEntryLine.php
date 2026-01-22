<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * V65-BUG-FIX: Added HasBranch trait for proper branch scoping.
 */
class JournalEntryLine extends Model
{
    use HasBranch;

    protected $table = 'journal_entry_lines';

    /**
     * Fillable fields aligned with migration:
     * 2026_01_04_000007_create_accounting_tables.php
     */
    protected $fillable = [
        'branch_id',
        'journal_entry_id',
        'account_id',
        'debit',
        'credit',
        'description',
        'reference',
    ];

    protected $casts = [
        'debit' => 'decimal:4',
        'credit' => 'decimal:4',
    ];

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    // Backward compatibility accessors
    public function getDebitBaseAttribute()
    {
        return $this->debit;
    }

    public function getCreditBaseAttribute()
    {
        return $this->credit;
    }
}
