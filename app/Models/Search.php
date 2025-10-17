<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property string $query
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Search extends Model
{
    protected $fillable = [
        'query',
    ];

    public function movies(): HasMany
    {
        return $this->hasMany(Movie::class);
    }
}
