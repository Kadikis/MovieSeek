<?php

namespace App\Repositories;

use App\Models\Search;
use Illuminate\Support\Collection;

class SearchRepository
{
    public function getSearchByIdAndSessionId(int $id, string $sessionId): ?Search
    {
        return Search::where('session_id', $sessionId)
            ->with('movies')
            ->find($id);
    }

    /**
     * @return Collection<Search>
     */
    public function getLatestSearches(string $sessionId, int $limit = 5): Collection
    {
        return Search::where('session_id', $sessionId)
            ->withCount('movies')->latest()->take($limit)->get();
    }
}
