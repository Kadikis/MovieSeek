<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Movie;
use App\Models\Search;
use App\Repositories\SearchRepository;
use App\Structures\MovieApiSearchResponseStructure;

class MovieService
{

    public function __construct(
        private readonly SearchRepository $searchRepository,
    ) {}

    public function search(string $query, MovieApiService $movieApiService, string $guestUuid): ?Search
    {
        //Normally we would run this in background job, but for now we will just set the time limit
        set_time_limit(60);

        $query = strtolower(trim($query));
        if ($query === '') {
            return null;
        }

        //First check if the search already exists and is not expired or empty
        $search = $this->searchRepository->getByQueryAndGuestUuid($query, $guestUuid);

        //If the search does not exist, create a new one and then fetch the movies from the API
        if (!$search || $search->isEmpty() || $search->isExpired()) {
            //If the search does not exist, create a new one
            $search = Search::create([
                'query' => $query,
                'guest_uuid' => $guestUuid,
            ]);

            /** @var Collection<MovieApiSearchResponseStructure> */
            $movies = $movieApiService->search($query);

            $movies->each(function (MovieApiSearchResponseStructure $movie) use ($search): void {
                $existingMovie = Movie::where('imdb_id', $movie->imdb_id)->first();
                if ($existingMovie) {
                    $search->movies()->attach($existingMovie->id);
                    return;
                }

                $search->movies()->create($movie->toArray());
            });
        }

        return $search->load('movies');
    }

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

        $movie = Movie::updateOrCreate(['imdb_id' => $imdbId], [...$movieData->toArray(), 'full_data' => true]);

        return $movie;
    }
}
