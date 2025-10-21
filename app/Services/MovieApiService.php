<?php

namespace App\Services;

use App\Structures\MovieApiSearchResponseStructure;
use App\Structures\MovieApiSingleMovieResponseStructure;

abstract class MovieApiService
{
    abstract public function search(string $query, int $page = 1): MovieApiSearchResponseStructure;
    abstract public function getMovieByImdbId(string $imdbId): ?MovieApiSingleMovieResponseStructure;
}
