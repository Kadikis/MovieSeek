<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\OMDbMovieApiService;
use App\Structures\MovieApiSearchResponseStructure;
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

        config(['services.omdb.key' => 'test-key']);

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

        $this->assertInstanceOf(MovieApiSearchResponseStructure::class, $result);
        $this->assertCount(0, $result->movies);
        $this->assertEquals(0, $result->total_results);
        $this->assertFalse($result->no_results);
    }

    public function test_search_returns_structure_with_movies(): void
    {
        $this->mockHttpFactory->shouldReceive('timeout')->once()->with(10)->andReturn($this->mockPendingRequest);

        $response = Mockery::mock(HttpResponse::class);
        $response->shouldReceive('ok')->andReturn(true);
        $response->shouldReceive('json')->andReturn([
            'Response' => 'True',
            'totalResults' => '15',
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

        $this->mockPendingRequest->shouldReceive('get')->once()->andReturn($response);
        $result = $this->omdbService->search('Batman');

        $this->assertInstanceOf(MovieApiSearchResponseStructure::class, $result);
        $this->assertCount(10, $result->movies);
        $this->assertEquals(15, $result->total_results);
        $this->assertEquals(2, $result->total_pages);
        $this->assertFalse($result->no_results);
        $this->assertEquals('tt0000001', $result->movies->first()->imdb_id);
        $this->assertEquals('tt00000010', $result->movies->last()->imdb_id);
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

    public function test_search_handles_api_error_response(): void
    {
        $this->mockHttpFactory->shouldReceive('timeout')->once()->with(10)->andReturn($this->mockPendingRequest);

        $response = Mockery::mock(HttpResponse::class);
        $response->shouldReceive('ok')->andReturn(true);
        $response->shouldReceive('json')->andReturn([
            'Response' => 'False',
            'Error' => 'Movie not found!'
        ]);

        $this->mockPendingRequest->shouldReceive('get')->once()->andReturn($response);

        $result = $this->omdbService->search('nonexistent');

        $this->assertInstanceOf(MovieApiSearchResponseStructure::class, $result);
        $this->assertTrue($result->no_results);
        $this->assertCount(0, $result->movies);
        $this->assertEquals(0, $result->total_results);
    }

    public function test_search_handles_http_error(): void
    {
        $this->mockHttpFactory->shouldReceive('timeout')->once()->with(10)->andReturn($this->mockPendingRequest);

        $response = Mockery::mock(HttpResponse::class);
        $response->shouldReceive('ok')->andReturn(false);

        $this->mockPendingRequest->shouldReceive('get')->once()->andReturn($response);

        $result = $this->omdbService->search('Batman');

        $this->assertInstanceOf(MovieApiSearchResponseStructure::class, $result);
        $this->assertEquals('Failed to fetch movies', $result->error);
        $this->assertCount(0, $result->movies);
    }

    public function test_search_with_different_pages(): void
    {
        $this->mockHttpFactory->shouldReceive('timeout')->once()->with(10)->andReturn($this->mockPendingRequest);

        $response = Mockery::mock(HttpResponse::class);
        $response->shouldReceive('ok')->andReturn(true);
        $response->shouldReceive('json')->andReturn([
            'Response' => 'True',
            'totalResults' => '25',
            'Search' => array_map(function (int $i) {
                return [
                    'imdbID' => 'tt000000' . $i,
                    'Title' => 'Movie ' . $i,
                    'Year' => '200' . $i,
                    'Type' => 'movie',
                    'Poster' => 'https://example.com/' . $i . '.jpg',
                ];
            }, range(11, 20)),
        ]);

        $this->mockPendingRequest->shouldReceive('get')->once()->andReturn($response);

        $result = $this->omdbService->search('Batman', 2);

        $this->assertInstanceOf(MovieApiSearchResponseStructure::class, $result);
        $this->assertCount(10, $result->movies);
        $this->assertEquals(25, $result->total_results);
        $this->assertEquals(3, $result->total_pages);
    }

    public function test_get_movie_by_imdb_id_handles_missing_data(): void
    {
        $this->mockHttpFactory->shouldReceive('timeout')->once()->with(10)->andReturn($this->mockPendingRequest);

        $response = Mockery::mock(HttpResponse::class);
        $response->shouldReceive('ok')->andReturn(true);
        $response->shouldReceive('json')->andReturn([
            'imdbID' => 'tt7654321',
            'Title' => 'Some Movie',
            // Missing other fields
        ]);

        $this->mockPendingRequest->shouldReceive('get')->once()->andReturn($response);

        $movie = $this->omdbService->getMovieByImdbId('tt7654321');

        $this->assertInstanceOf(MovieApiSingleMovieResponseStructure::class, $movie);
        $this->assertNotNull($movie);
        $this->assertEquals('tt7654321', $movie->imdbID);
        $this->assertEquals('Some Movie', $movie->title);
        $this->assertEquals('', $movie->year); // Should default to empty string
        $this->assertEquals('', $movie->director); // Should default to empty string
    }
}
