<?php

namespace App\Http\Controllers;

use Inertia\Response as InertiaResponse;
use App\Repositories\SearchRepository;
use App\Services\OMDbMovieApiService;
use App\Services\MovieSearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $request->validate([
            'search_id' => 'nullable|integer|exists:searches,id',
            'query' => 'nullable|string|max:255|min:3',
        ]);

        $searchId = $request->input('search_id');
        $query = $request->input('query');

        $searchResult = null;
        if ($searchId || $query) {
            if ($searchId) {
                $searchResult = app(MovieSearchService::class)->getSearchById($searchId);
            }
            if (!$searchResult) {
                $searchResult = app(MovieSearchService::class)->search($query, app(OMDbMovieApiService::class), $request->user()->id ?? null);
            }
        }

        // List of latest searches to be displayed in index page
        $latestSearches = app(SearchRepository::class)->getLatestSearches()->map(fn($search) => [
            'id' => $search->id,
            'query' => $search->query,
            'movies_count' => $search->movies_count,
        ]);

        return Inertia::render('Home', [
            'latestSearches' => $latestSearches,
            'searchResult' => $searchResult,
        ]);
    }
}
