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
     * @return Collection<MovieApiSearchResponseStructure>
     */
    public function search(string $query): Collection
    {
        $trimmedQuery = trim($query);
        if ($trimmedQuery === '') {
            return collect();
        }
        /** @var Collection<MovieApiSearchResponseStructure> $results */
        $results = collect();

        $page = 1;
        $pageSize = 10;
        $totalPages = 1;
        $noResults = false;
        while ($page <= $totalPages && !$noResults) {
            $response = $this->http->timeout(10)->get($this->baseUrl, [
                'apikey' => $this->apiKey,
                's' => $trimmedQuery,
                'page' => $page,
            ]);

            if (!$response->ok()) {
                return $results;
            }

            $data = $response->json();

            $totalPages = (int) ceil(($data['totalResults'] ?? 0) / $pageSize);

            if ($data['Response'] === 'False') {
                $noResults = true;
            }

            collect($data['Search'] ?? [])->each(function (array $item) use ($results) {
                $item = MovieApiSearchResponseStructure::fromArray($item);
                $results->push($item);
            });

            $page++;
        }

        return $results;
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
