<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\OMDbMovieApiService;
use App\Structures\MovieApiSingleMovieResponseStructure;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Support\Collection;
use Mockery\MockInterface;
use Mockery;
use Tests\TestCase;

class OMDbMovieApiServiceTest extends TestCase
{
    private OMDbMovieApiService $omdbService;

    /** @var HttpFactory | MockInterface */
    private HttpFactory $mockHttpFactory;
    /** @var HttpFactory | PendingRequest */
    private PendingRequest $mockPendingRequest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockHttpFactory = Mockery::mock(HttpFactory::class);
        $this->mockPendingRequest = Mockery::mock(PendingRequest::class);
        $this->omdbService = new OMDbMovieApiService($this->mockHttpFactory);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_search_returns_empty_collection_on_blank_query(): void
    {
        $result = $this->omdbService->search('   ');

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(0, $result);
    }

    public function test_search_paginates_and_maps_results(): void
    {
        $this->mockHttpFactory->shouldReceive('timeout')->twice()->with(10)->andReturn($this->mockPendingRequest);

        $firstPageResponse = Mockery::mock(HttpResponse::class);
        $firstPageResponse->shouldReceive('ok')->andReturn(true);
        $firstPageResponse->shouldReceive('json')->andReturn([
            'Response' => 'True',
            'totalResults' => 15,
            'Search' => array_map(function (int $i) {
                return [
                    'imdbID' => 'tt000000' . $i,
                    'Title' => 'Movie ' . $i,
                    'Year' => '200' . $i,
                    'Type' => 'movie',
                    'Poster' => 'https://example.com/' . $i . '.jpg',
                ];
            }, range(1, 10)),
        ]);

        $secondPageResponse = Mockery::mock(HttpResponse::class);
        $secondPageResponse->shouldReceive('ok')->andReturn(true);
        $secondPageResponse->shouldReceive('json')->andReturn([
            'Response' => 'True',
            'totalResults' => 15,
            'Search' => array_map(function (int $i) {
                return [
                    'imdbID' => 'tt000000' . $i,
                    'Title' => 'Movie ' . $i,
                    'Year' => '200' . $i,
                    'Type' => 'movie',
                    'Poster' => 'https://example.com/' . $i . '.jpg',
                ];
            }, range(11, 15)),
        ]);

        $this->mockPendingRequest->shouldReceive('get')->twice()->andReturn($firstPageResponse, $secondPageResponse);
        $result = $this->omdbService->search('Batman');

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(15, $result);
        $this->assertEquals('tt0000001', $result->first()->imdb_id);
        $this->assertEquals('tt00000015', $result->last()->imdb_id);
    }

    public function test_get_movie_by_imdb_id_returns_null_on_http_error(): void
    {
        $this->mockHttpFactory->shouldReceive('timeout')->once()->with(10)->andReturn($this->mockPendingRequest);

        $response = Mockery::mock(HttpResponse::class);
        $response->shouldReceive('ok')->andReturn(false);

        $this->mockPendingRequest->shouldReceive('get')->once()->andReturn($response);

        $movie = $this->omdbService->getMovieByImdbId('tt1234567');

        $this->assertNull($movie);
    }

    public function test_get_movie_by_imdb_id_returns_structure_on_success(): void
    {
        $this->mockHttpFactory->shouldReceive('timeout')->once()->with(10)->andReturn($this->mockPendingRequest);

        $response = Mockery::mock(HttpResponse::class);
        $response->shouldReceive('ok')->andReturn(true);
        $response->shouldReceive('json')->andReturn([
            'imdbID' => 'tt7654321',
            'Title' => 'Some Movie',
            'Year' => '2020',
            'Type' => 'movie',
            'Poster' => 'https://example.com/poster.jpg',
        ]);

        $this->mockPendingRequest->shouldReceive('get')->once()->andReturn($response);

        $movie = $this->omdbService->getMovieByImdbId('tt7654321');

        $this->assertInstanceOf(MovieApiSingleMovieResponseStructure::class, $movie);
        $this->assertNotNull($movie);
        $this->assertEquals('tt7654321', $movie->imdbID);
        $this->assertEquals('Some Movie', $movie->title);
    }
}
