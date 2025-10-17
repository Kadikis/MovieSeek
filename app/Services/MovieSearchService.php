<?php

namespace App\Services;

use App\Models\Search;
use App\Repositories\SearchRepository;
use Illuminate\Support\Collection;

class MovieSearchService
{
    public function search(string $query, MovieApiService $movieApiService, string $sessionId): Search
    {
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

        return $search->load('movies');
    }

    public function getSearchByIdAndSessionId(int $id, string $sessionId): ?Search
    {
        $search = app(SearchRepository::class)->getSearchByIdAndSessionId($id, $sessionId);
        if (!$search || $search->session_id !== $sessionId || $search->movies_count === 0 || $search->created_at->diffInDays(now()) > 3) {
            return null;
        }

        return $search->load('movies');
    }
}
