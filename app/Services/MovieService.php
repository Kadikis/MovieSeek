<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Movie;
use App\Models\Search;
use App\Repositories\SearchRepository;
use App\Structures\MovieApiSearchMovieResponseStructure;

class MovieService
{
    public function __construct(
        private readonly SearchRepository $searchRepository,
    ) {}

    public function search(string $query, MovieApiService $movieApiService, string $guestUuid): ?Search
    {
        $query = strtolower(trim($query));
        if ($query === '') {
            return null;
        }

        //First check if the search already exists and is not expired or empty
        $search = $this->searchRepository->getByQueryAndGuestUuid($query, $guestUuid);

        //If the search does not exist, create a new one and then fetch the movies from the API
        if (!$search || $search->isEmpty() || $search->isExpired()) {
            //If the search does not exist, create a new one
            $searchData = $movieApiService->search($query, 1);

            $search = Search::create([
                'query' => $query,
                'guest_uuid' => $guestUuid,
                'total_results' => $searchData->total_results,
                'total_pages' => $searchData->total_pages,
                'no_results' => $searchData->no_results,
                'pages_loaded' => 1,
            ]);

            $searchData->movies->each(function (MovieApiSearchMovieResponseStructure $movie) use ($search): void {
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

    public function loadMorePages(Search $search, MovieApiService $movieApiService, int $pagesToLoad = 2): Search
    {
        if ($search->pages_loaded >= $search->total_pages) {
            return $search;
        }

        $currentPage = $search->pages_loaded + 1;
        $endPage = min($currentPage + $pagesToLoad - 1, $search->total_pages);

        for ($page = $currentPage; $page <= $endPage; $page++) {
            $searchData = $movieApiService->search($search->query, $page);

            $searchData->movies->each(function (MovieApiSearchMovieResponseStructure $movie) use ($search): void {
                $existingMovie = Movie::where('imdb_id', $movie->imdb_id)->first();
                if ($existingMovie) {
                    $search->movies()->attach($existingMovie->id);
                    return;
                }

                $search->movies()->create($movie->toArray());
            });
        }

        $search->update([
            'pages_loaded' => $endPage,
        ]);

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
