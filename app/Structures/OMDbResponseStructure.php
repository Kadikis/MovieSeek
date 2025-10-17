<?php

namespace App\Structures;

class OMDbResponseStructure
{
    public ?string $title = null;
    public ?string $year = null;
    public ?string $imdb_id = null;
    public ?string $type = null;
    public ?string $poster = null;

    public function __construct(
        ?string $title = null,
        ?string $year = null,
        ?string $imdb_id = null,
        ?string $type = null,
        ?string $poster = null,
    ) {
        $this->title = $title;
        $this->year = $year;
        $this->imdb_id = $imdb_id;
        $this->type = $type;
        $this->poster = $poster;
    }

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
}
