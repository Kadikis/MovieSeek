<?php

namespace App\Services;

use App\Models\Search;
use App\Repositories\SearchRepository;
use Illuminate\Support\Collection;

class MovieSearchService
{
    public function search(string $query, MovieApiService $movieApiService, string $sessionId): ?Search
    {
        $query = strtolower(trim($query));
        if ($query === '') {
            return null;
        }

        //First check if the search already exists and is not expired or empty
        $search = app(SearchRepository::class)->getSearchByQueryAndSessionId($query, $sessionId);

        if (!$search || $search->isEmpty() || $search->isExpired()) {
            //If the search does not exist, create a new one
            $search = Search::create([
                'query' => $query,
                'session_id' => $sessionId,
            ]);

            /** @var Collection<OMDbResponseStructure> */
            $movies = $movieApiService->search($query);

            foreach ($movies as $movie) {
                $search->movies()->create([
                    'title' => $movie->title,
                    'year' => $movie->year,
                    'imdb_id' => $movie->imdb_id,
                    'type' => $movie->type,
                    'poster' => $movie->poster,
                ]);
            }
        }

        return $search->load('movies');
    }

    public function getSearchByIdAndSessionId(int $id, string $sessionId): ?Search
    {
        $search = app(SearchRepository::class)->getSearchByIdAndSessionId($id, $sessionId);
        if (!$search || $search->session_id !== $sessionId ||  $search->isEmpty() || $search->isExpired()) {
            return null;
        }

        return $search->load('movies');
    }
}
