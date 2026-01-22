<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchHistory extends BaseModel
{
    use HasFactory;

    protected $table = 'search_history';

    protected $fillable = [
        'user_id',
        'branch_id',
        'query',
        'module',
        'context',
        'results_count',
    ];

    protected $casts = [
        'results_count' => 'integer',
    ];

    /**
     * Get the user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get recent searches for a user.
     */
    public static function getRecentSearches(int $userId, int $limit = 10): array
    {
        return static::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->pluck('query')
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Get popular searches.
     */
    public static function getPopularSearches(int $limit = 10): array
    {
        return static::selectRaw('query, COUNT(*) as count')
            ->groupBy('query')
            ->orderByDesc('count')
            ->limit($limit)
            ->pluck('query')
            ->toArray();
    }
}
