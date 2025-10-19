<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Search;
use Illuminate\Support\Collection;

class SearchRepository
{
    public function getByIdAndSessionId(int $id, string $sessionId): ?Search
    {
        $search = Search::where('session_id', $sessionId)
            ->with('movies')
            ->find($id);

        if (!$search || $search->session_id !== $sessionId ||  $search->isEmpty() || $search->isExpired()) {
            return null;
        }

        return $search;
    }

    /**
     * @return Collection<Search>
     */
    public function getLatestBySessionId(string $sessionId, int $limit = 5): Collection
    {
        return Search::where('session_id', $sessionId)
            ->withCount('movies')->latest()->take($limit)->get();
    }

    public function getByQueryAndSessionId(string $query, string $sessionId): ?Search
    {
        return Search::where('query', $query)
            ->where('session_id', $sessionId)
            ->first();
    }
}
