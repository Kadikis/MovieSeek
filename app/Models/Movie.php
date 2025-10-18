<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $search_id
 * @property string $title
 * @property string|null $year
 * @property string|null $poster
 * @property string|null $rated
 * @property string|null $released
 * @property string|null $runtime
 * @property string|null $genre
 * @property string|null $director
 * @property string|null $writer
 * @property string|null $actors
 * @property string|null $plot
 * @property string|null $language
 * @property string|null $country
 * @property string|null $imdb_rating
 * @property string|null $imdb_votes
 * @property string|null $imdb_id
 * @property string|null $type
 * @property boolean $full_data
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Movie extends Model
{
    protected $fillable = [
        'title',
        'year',
        'imdb_id',
        'type',
        'poster',
        'rated',
        'released',
        'runtime',
        'genre',
        'director',
        'writer',
        'actors',
        'plot',
        'language',
        'country',
        'imdb_rating',
        'imdb_votes',
        'imdb_id',
        'type',
        'full_data',
    ];

    public function search(): BelongsTo
    {
        return $this->belongsTo(Search::class);
    }

    public function isExpired(): bool
    {
        return $this->created_at->diffInDays(now()) > 7;
    }

    public function hasFullData(): bool
    {
        return $this->full_data;
    }
}
