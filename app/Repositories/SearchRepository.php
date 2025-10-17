<?php

namespace App\Repositories;

use App\Models\Search;
use Illuminate\Support\Collection;

class SearchRepository
{
    public function getSearchById(int $id): Search
    {
        return Search::with('movies')->find($id);
    }

    /**
     * @return Collection<Search>
     */
    public function getLatestSearches(int $limit = 5): Collection
    {
        return Search::withCount('movies')->latest()->take($limit)->get();
    }
}
