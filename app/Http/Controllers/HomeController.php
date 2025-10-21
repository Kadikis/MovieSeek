<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Response as InertiaResponse;
use App\Repositories\SearchRepository;
use App\Services\OMDbMovieApiService;
use App\Services\MovieService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function __construct(
        private readonly SearchRepository $searchRepository,
        private readonly MovieService $movieService,
    ) {}

    public function index(Request $request): InertiaResponse
    {
        $request->validate([
            'search_id' => 'nullable|integer|exists:searches,id',
            'query' => 'nullable|string|max:255|min:4',
        ]);

        $searchId = (int) $request->input('search_id');
        $query = (string) $request->input('query');
        $guestUuid = (string) $request->cookie('guest_id');

        $searchResult = null;
        if ($searchId || $query) {
            if ($searchId) {
                $searchResult = $this->searchRepository->getByIdAndGuestUuid($searchId, $guestUuid);

                if ($searchResult && $request->has('load_more')) {
                    $searchResult = $this->movieService->loadMorePages($searchResult, app(OMDbMovieApiService::class));
                }
            }
            if (!$searchResult) {
                $searchResult = $this->movieService->search($query, app(OMDbMovieApiService::class), $guestUuid);
            }
        }

        // List of latest searches to be displayed in index page
        $latestSearches = $this->searchRepository->getLatestByGuestUuid(guestUuid: $guestUuid)->map(fn($search) => [
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

    public function show(string $movieImdbId, Request $request): InertiaResponse
    {
        $searchId = (int) $request->input('search_id') ?? null;

        $movie = $this->movieService->getMovieByImdbId($movieImdbId, app(OMDbMovieApiService::class));

        return Inertia::render('Show', [
            'movie' => $movie,
            'searchId' => $searchId,
        ]);
    }
}
