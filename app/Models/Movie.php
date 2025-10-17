<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $search_id
 * @property string $title
 * @property string|null $year
 * @property string $imdb_id
 * @property string $type
 * @property string|null $poster
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
    ];

    public function search(): BelongsTo
    {
        return $this->belongsTo(Search::class);
    }
}
