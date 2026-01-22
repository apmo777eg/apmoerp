<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DocumentTag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'color',
        'description',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tag) {
            if (! $tag->slug) {
                $tag->slug = \Illuminate\Support\Str::slug($tag->name);
            }
        });
    }

    // Relationships
    public function documents(): BelongsToMany
    {
        // V62-FIX: Use correct pivot table name and column names matching migration
        return $this->belongsToMany(Document::class, 'document_tag', 'document_tag_id', 'document_id');
    }

    // Business Methods
    public function getDocumentCount(): int
    {
        return $this->documents()->count();
    }
}
