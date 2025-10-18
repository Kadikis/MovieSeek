<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property int $session_id
 * @property string $query
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Search extends Model
{
    protected $fillable = [
        'query',
        'session_id',
    ];

    public function movies(): BelongsToMany
    {
        return $this->belongsToMany(Movie::class, 'search_movies')->withTimestamps();
    }

    public function isExpired(): bool
    {
        return $this->created_at->diffInDays(now()) > 3;
    }

    public function isEmpty(): bool
    {
        return $this->movies->count() === 0;
    }
}
