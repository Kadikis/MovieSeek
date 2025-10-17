<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use App\Structures\OMDbResponseStructure;

class OMDbMovieApiService extends MovieApiService
{
    protected string $baseUrl = 'https://www.omdbapi.com/';
    protected ?string $apiKey = null;

    public function __construct()
    {
        $this->apiKey = config('services.omdb.key');

        if (!$this->apiKey) {
            throw new Exception('OMDB API key is not set');
        }
    }

    /**
     * Search for movies using the OMDb API.
     *
     * @return Collection<OMDbResponseStructure>
     */
    public function search(string $query): Collection
    {
        $trimmedQuery = trim($query);
        if ($trimmedQuery === '') {
            return collect();
        }
        /** @var Collection<OMDbResponseStructure> $results */
        $results = collect();

        $page = 1;
        $pageSize = 10;
        $totalPages = 1;
        $noResults = false;
        while ($page <= $totalPages && !$noResults) {
            $response = Http::timeout(10)->get($this->baseUrl, [
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
                $item = OMDbResponseStructure::fromArray($item);
                $results->push($item);
            });

            $page++;
        }

        return $results;
    }
}
