<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Http\Client\Factory;
use App\Structures\MovieApiSearchResponseStructure;
use App\Structures\MovieApiSingleMovieResponseStructure;

class OMDbMovieApiService extends MovieApiService
{
    protected string $baseUrl = 'https://www.omdbapi.com/';
    protected ?string $apiKey = null;

    public function __construct(private readonly Factory $http)
    {
        $this->apiKey = config('services.omdb.key');

        if (!$this->apiKey) {
            throw new Exception('OMDB API key is not set');
        }
    }

    /**
     * Search for movies using the OMDb API.
     *
     * @return MovieApiSearchResponseStructure
     */
    public function search(string $query, int $page = 1): MovieApiSearchResponseStructure
    {
        $trimmedQuery = trim($query);
        if ($trimmedQuery === '') {
            return MovieApiSearchResponseStructure::fromArray([
                'Response' => 'True',
                'totalResults' => '0',
                'Search' => []
            ]);
        }

        $response = $this->http->timeout(10)->get($this->baseUrl, [
            'apikey' => $this->apiKey,
            's' => $trimmedQuery,
            'page' => $page,
        ]);

        if (!$response->ok()) {
            return MovieApiSearchResponseStructure::fromArray(['error' => 'Failed to fetch movies']);
        }

        $data = $response->json();

        return MovieApiSearchResponseStructure::fromArray($data);
    }

    public function getMovieByImdbId(string $imdbId): ?MovieApiSingleMovieResponseStructure
    {
        $response = $this->http->timeout(10)->get($this->baseUrl, [
            'apikey' => $this->apiKey,
            'i' => $imdbId,
        ]);

        if (!$response->ok()) {
            return null;
        }

        return MovieApiSingleMovieResponseStructure::fromArray($response->json());
    }
}
