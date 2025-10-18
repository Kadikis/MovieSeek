<?php

namespace App\Services;

use App\Models\Movie;

class MovieService
{
    public function getMovieByImdbId(string $imdbId, MovieApiService $movieApiService): ?Movie
    {
        $movie = Movie::where('imdb_id', $imdbId)->first();

        //If we have a movie and it's not expired and it has full data, return it
        if ($movie && !$movie->isExpired() && $movie->hasFullData()) {
            return $movie;
        }

        $movieData = $movieApiService->getMovieByImdbId($imdbId);
        if (!$movieData) {
            return null;
        }

        //TODO: Fetch just one and update it
        $movies = Movie::where('imdb_id', $imdbId)->get();
        $movies->each->update(['full_data' => true, ...$movieData->toArray()]);

        return $movies->first();
    }
}
