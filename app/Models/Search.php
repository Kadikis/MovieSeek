<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $guest_uuid
 * @property string $query
 * @property int $total_results
 * @property int $total_pages
 * @property bool $no_results
 * @property int $pages_loaded
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Search extends Model
{
    protected $fillable = [
        'query',
        'guest_uuid',
        'total_results',
        'total_pages',
        'no_results',
        'pages_loaded',
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
