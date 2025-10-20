<?php

declare(strict_types=1);

namespace App\Structures;

class MovieApiSingleMovieResponseStructure
{
    public function __construct(
        public readonly string $title,
        public readonly string $year,
        public readonly string $rated,
        public readonly string $released,
        public readonly string $runtime,
        public readonly string $genre,
        public readonly string $director,
        public readonly string $writer,
        public readonly string $actors,
        public readonly string $plot,
        public readonly string $language,
        public readonly string $country,
        public readonly string $poster,
        public readonly string $imdbRating,
        public readonly string $imdbVotes,
        public readonly string $imdbID,
        public readonly string $type,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['Title'] ?? '',
            year: $data['Year'] ?? '',
            rated: $data['Rated'] ?? '',
            released: $data['Released'] ?? '',
            runtime: $data['Runtime'] ?? '',
            genre: $data['Genre'] ?? '',
            director: $data['Director'] ?? '',
            writer: $data['Writer'] ?? '',
            actors: $data['Actors'] ?? '',
            plot: $data['Plot'] ?? '',
            language: $data['Language'] ?? '',
            country: $data['Country'] ?? '',
            poster: $data['Poster'] ?? '',
            imdbRating: $data['imdbRating'] ?? '',
            imdbVotes: $data['imdbVotes'] ?? '',
            imdbID: $data['imdbID'] ?? '',
            type: $data['Type'] ?? '',
        );
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'year' => $this->year,
            'rated' => $this->rated,
            'released' => $this->released,
            'runtime' => $this->runtime,
            'genre' => $this->genre,
            'director' => $this->director,
            'writer' => $this->writer,
            'actors' => $this->actors,
            'plot' => $this->plot,
            'language' => $this->language,
            'country' => $this->country,
            'poster' => $this->poster,
            'imdb_rating' => $this->imdbRating,
            'imdb_votes' => $this->imdbVotes,
            'imdb_id' => $this->imdbID,
            'type' => $this->type,
        ];
    }
}
