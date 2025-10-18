<?php

namespace App\Http\Controllers;

use Inertia\Response as InertiaResponse;
use App\Repositories\SearchRepository;
use App\Services\OMDbMovieApiService;
use App\Services\MovieSearchService;
use App\Services\MovieService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function __construct(
        private readonly MovieSearchService $movieSearchService,
        private readonly SearchRepository $searchRepository,
        private readonly MovieService $movieService,
    ) {}

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
                $searchResult = $this->movieSearchService->getSearchByIdAndSessionId($searchId, session()->getId());
            }
            if (!$searchResult) {
                $searchResult = $this->movieSearchService->search($query, app(OMDbMovieApiService::class), session()->getId());
            }
        }

        // List of latest searches to be displayed in index page
        $latestSearches = $this->searchRepository->getLatestSearches(sessionId: session()->getId())->map(fn($search) => [
            'id' => $search->id,
            'query' => $search->query,
            'movies_count' => $search->movies_count,
        ]);

        return Inertia::render('Home', [
            'query' => $query,
            'latestSearches' => $latestSearches,
            'searchResult' => $searchResult,
        ]);
    }

    public function show(string $movieImdbId): InertiaResponse
    {
        $movie = $this->movieService->getMovieByImdbId($movieImdbId, app(OMDbMovieApiService::class));

        return Inertia::render('Show', [
            'movie' => $movie,
        ]);
    }
}
