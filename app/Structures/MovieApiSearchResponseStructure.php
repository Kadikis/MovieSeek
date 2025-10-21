<?php

declare(strict_types=1);

namespace App\Structures;

use Illuminate\Support\Collection;

class MovieApiSearchResponseStructure
{
    public function __construct(
        public readonly ?string $error = null,
        public readonly Collection $movies,
        public readonly int $total_results,
        public readonly int $total_pages,
        public readonly bool $no_results,
    ) {}

    public static function fromArray(array $data): self
    {
        $movies = collect($data['Search'] ?? [])->map(function (array $movie) {
            return MovieApiSearchMovieResponseStructure::fromArray($movie);
        });

        return new self(
            error: $data['error'] ?? null,
            movies: $movies,
            total_results: (int) ($data['totalResults'] ?? 0),
            total_pages: (int) ceil(($data['totalResults'] ?? 0) / 10),
            no_results: ($data['Response'] ?? 'True') === 'False',
        );
    }

    public function toArray(): array
    {
        return [
            'error' => $this->error,
            'movies' => $this->movies,
            'total_results' => $this->total_results,
            'total_pages' => $this->total_pages,
            'no_results' => $this->no_results,
        ];
    }
}
