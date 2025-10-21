<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Search;
use Illuminate\Support\Collection;

class SearchRepository
{
    public function getByIdAndGuestUuid(int $id, string $guestUuid): ?Search
    {
        $search = Search::where('guest_uuid', $guestUuid)
            ->with('movies')
            ->find($id);

        if (!$search || $search->guest_uuid !== $guestUuid ||  $search->isEmpty() || $search->isExpired()) {
            return null;
        }

        return $search;
    }

    /**
     * @return Collection<Search>
     */
    public function getLatestByGuestUuid(string $guestUuid, int $limit = 5): Collection
    {
        return Search::where('guest_uuid', $guestUuid)
            ->withCount('movies')
            ->latest()
            ->take($limit)
            ->get();
    }

    public function getByQueryAndGuestUuid(string $query, string $guestUuid): ?Search
    {
        return Search::where('query', $query)
            ->where('guest_uuid', $guestUuid)
            ->first();
    }
}
