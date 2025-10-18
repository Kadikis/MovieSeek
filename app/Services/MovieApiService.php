<?php

namespace App\Services;

use Illuminate\Support\Collection;
use App\Structures\MovieApiSingleMovieResponseStructure;

abstract class MovieApiService
{
    abstract public function search(string $query): Collection;
    abstract public function getMovieByImdbId(string $imdbId): ?MovieApiSingleMovieResponseStructure;
}
