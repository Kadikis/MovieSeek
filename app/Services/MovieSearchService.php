<?php

namespace App\Services;

use App\Models\Search;
use App\Repositories\SearchRepository;
use Illuminate\Support\Collection;

class MovieSearchService
{
    public function search(string $query, MovieApiService $movieApiService, ?int $userId = null): Search
    {
        $search = Search::create([
            'query' => $query,
            'user_id' => $userId,
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

    public function getSearchById(int $id): ?Search
    {
        $search = app(SearchRepository::class)->getSearchById($id);
        if (!$search || $search->movies_count === 0 || $search->created_at->diffInDays(now()) > 3) {
            return null;
        }

        return $search->load('movies');
    }
}
