<?php

namespace App\Services;

use Illuminate\Support\Collection;

abstract class MovieApiService
{
    abstract public function search(string $query): Collection;
}
