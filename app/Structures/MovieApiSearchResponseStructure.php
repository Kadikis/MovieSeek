<?php

declare(strict_types=1);

namespace App\Structures;

class MovieApiSearchResponseStructure
{
    public function __construct(
        public readonly ?string $title = null,
        public readonly ?string $year = null,
        public readonly ?string $imdb_id = null,
        public readonly ?string $type = null,
        public readonly ?string $poster = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['Title'],
            year: $data['Year'],
            imdb_id: $data['imdbID'],
            type: $data['Type'],
            poster: $data['Poster'],
        );
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'year' => $this->year,
            'imdb_id' => $this->imdb_id,
            'type' => $this->type,
            'poster' => $this->poster,
        ];
    }
}
