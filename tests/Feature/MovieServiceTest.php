<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Movie;
use App\Models\Search;
use App\Repositories\SearchRepository;
use App\Services\MovieApiService;
use App\Services\MovieService;
use App\Structures\MovieApiSearchResponseStructure;
use App\Structures\MovieApiSingleMovieResponseStructure;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class MovieServiceTest extends TestCase
{
    use RefreshDatabase;

    /** @var MovieService */
    private MovieService $movieService;
    /** @var MovieApiService | MockInterface */
    private MovieApiService $mockMovieApiService;
    /** @var SearchRepository | MockInterface */
    private SearchRepository $mockSearchRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockMovieApiService = Mockery::mock(MovieApiService::class);
        $this->mockSearchRepository = Mockery::mock(SearchRepository::class);
        $this->movieService = new MovieService($this->mockSearchRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function createSessionRecord(?string $sessionId = null): void
    {
        if (!$sessionId) {
            throw new Exception("Session not found!");
        }

        DB::insert('insert into sessions (id, user_id, ip_address, user_agent, payload, last_activity) values (?, ?, ?, ?, ?, ?)', [
            $sessionId,
            null,
            '127.0.0.1',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            '{"foo":"bar"}',
            time(),
        ]);
    }

    public function test_search_returns_null_for_empty_query(): void
    {
        $result = $this->movieService->search('', $this->mockMovieApiService, 'session-123');

        $this->assertNull($result);
    }

    public function test_search_returns_null_for_whitespace_only_query(): void
    {
        $result = $this->movieService->search('   ', $this->mockMovieApiService, 'session-123');

        $this->assertNull($result);
    }

    public function test_search_returns_existing_valid_search(): void
    {
        $query = 'batman';
        $sessionId = 'session-123';

        $this->createSessionRecord($sessionId);

        $search = Search::create([
            'query' => $query,
            'session_id' => $sessionId,
        ]);

        $movie = Movie::create([
            'title' => 'Batman Begins',
            'year' => '2005',
            'imdb_id' => 'tt0372784',
            'type' => 'movie',
            'poster' => 'https://example.com/poster.jpg',
        ]);

        $search->movies()->attach($movie->id);

        $this->mockSearchRepository->shouldReceive('getByQueryAndSessionId')->with($query, $sessionId)->andReturn($search);

        $result = $this->movieService->search($query, $this->mockMovieApiService, $sessionId);

        $this->assertInstanceOf(Search::class, $result);
        $this->assertEquals($search->id, $result->id);
        $this->assertTrue($result->relationLoaded('movies'));
    }


    public function test_search_creates_new_search_when_none_exists(): void
    {
        $query = 'superman';
        $sessionId = 'session-456';

        $this->createSessionRecord($sessionId);

        $this->mockSearchRepository
            ->shouldReceive('getByQueryAndSessionId')
            ->with($query, $sessionId)
            ->andReturn(null);

        $mockMovies = collect([
            new MovieApiSearchResponseStructure(
                title: 'Superman',
                year: '1978',
                imdb_id: 'tt0078346',
                type: 'movie',
                poster: 'https://example.com/superman.jpg'
            ),
            new MovieApiSearchResponseStructure(
                title: 'Superman II',
                year: '1980',
                imdb_id: 'tt0081573',
                type: 'movie',
                poster: 'https://example.com/superman2.jpg'
            ),
        ]);

        $this->mockMovieApiService
            ->shouldReceive('search')
            ->with($query)
            ->andReturn($mockMovies);

        $result = $this->movieService->search($query, $this->mockMovieApiService, $sessionId);

        $this->assertInstanceOf(Search::class, $result);
        $this->assertEquals($query, $result->query);
        $this->assertEquals($sessionId, $result->session_id);
        $this->assertTrue($result->relationLoaded('movies'));
        $this->assertCount(2, $result->movies);

        $this->assertDatabaseHas('movies', [
            'title' => 'Superman',
            'imdb_id' => 'tt0078346',
        ]);
        $this->assertDatabaseHas('movies', [
            'title' => 'Superman II',
            'imdb_id' => 'tt0081573',
        ]);
    }

    public function test_search_creates_new_search_when_existing_is_expired(): void
    {
        $query = 'spiderman';
        $sessionId = 'session-789';

        $this->createSessionRecord($sessionId);

        $expiredSearch = Search::create([
            'query' => $query,
            'session_id' => $sessionId,
            'created_at' => now()->subDays(4), // Expired (older than 3 days)
        ]);

        $this->mockSearchRepository
            ->shouldReceive('getByQueryAndSessionId')
            ->with($query, $sessionId)
            ->andReturn($expiredSearch);

        $mockMovies = collect([
            new MovieApiSearchResponseStructure(
                title: 'Spider-Man',
                year: '2002',
                imdb_id: 'tt0145487',
                type: 'movie',
                poster: 'https://example.com/spiderman.jpg'
            ),
        ]);

        $this->mockMovieApiService
            ->shouldReceive('search')
            ->with($query)
            ->andReturn($mockMovies);

        $result = $this->movieService->search($query, $this->mockMovieApiService, $sessionId);

        $this->assertInstanceOf(Search::class, $result);
        $this->assertNotEquals($expiredSearch->id, $result->id);
        $this->assertEquals($query, $result->query);
        $this->assertEquals($sessionId, $result->session_id);
    }

    public function test_search_creates_new_search_when_existing_is_empty(): void
    {
        $query = 'wonder woman';
        $sessionId = 'session-101';

        $this->createSessionRecord($sessionId);

        $emptySearch = Search::create([
            'query' => $query,
            'session_id' => $sessionId,
        ]);

        $this->mockSearchRepository
            ->shouldReceive('getByQueryAndSessionId')
            ->with($query, $sessionId)
            ->andReturn($emptySearch);

        $mockMovies = collect([
            new MovieApiSearchResponseStructure(
                title: 'Wonder Woman',
                year: '2017',
                imdb_id: 'tt0451279',
                type: 'movie',
                poster: 'https://example.com/wonderwoman.jpg'
            ),
        ]);

        $this->mockMovieApiService
            ->shouldReceive('search')
            ->with($query)
            ->andReturn($mockMovies);

        $result = $this->movieService->search($query, $this->mockMovieApiService, $sessionId);

        $this->assertInstanceOf(Search::class, $result);
        $this->assertNotEquals($emptySearch->id, $result->id);
        $this->assertCount(1, $result->movies);
    }

    public function test_search_attaches_existing_movie_to_search(): void
    {
        $query = 'batman';
        $sessionId = 'session-202';

        $this->createSessionRecord($sessionId);

        $existingMovie = Movie::create([
            'title' => 'The Dark Knight',
            'year' => '2008',
            'imdb_id' => 'tt0468569',
            'type' => 'movie',
            'poster' => 'https://example.com/darkknight.jpg',
        ]);

        $this->mockSearchRepository
            ->shouldReceive('getByQueryAndSessionId')
            ->with($query, $sessionId)
            ->andReturn(null);

        $mockMovies = collect([
            new MovieApiSearchResponseStructure(
                title: 'The Dark Knight',
                year: '2008',
                imdb_id: 'tt0468569',
                type: 'movie',
                poster: 'https://example.com/darkknight.jpg'
            ),
        ]);

        $this->mockMovieApiService
            ->shouldReceive('search')
            ->with($query)
            ->andReturn($mockMovies);

        $result = $this->movieService->search($query, $this->mockMovieApiService, $sessionId);

        $this->assertInstanceOf(Search::class, $result);
        $this->assertCount(1, $result->movies);
        $this->assertEquals($existingMovie->id, $result->movies->first()->id);

        $this->assertDatabaseCount('movies', 1);
        $this->assertDatabaseHas('search_movies', [
            'search_id' => $result->id,
            'movie_id' => $existingMovie->id,
        ]);
    }

    public function test_get_movie_by_imdb_id_returns_existing_valid_movie(): void
    {
        $imdbId = 'tt0372784';

        $movie = Movie::create([
            'title' => 'Batman Begins',
            'year' => '2005',
            'imdb_id' => $imdbId,
            'type' => 'movie',
            'poster' => 'https://example.com/poster.jpg',
            'full_data' => true,
        ]);

        $result = $this->movieService->getMovieByImdbId($imdbId, $this->mockMovieApiService);

        $this->assertInstanceOf(Movie::class, $result);
        $this->assertEquals($movie->id, $result->id);
        $this->assertEquals($imdbId, $result->imdb_id);
    }

    public function test_get_movie_by_imdb_id_fetches_from_api_when_movie_not_found(): void
    {
        $imdbId = 'tt0372784';

        $mockMovieData = new MovieApiSingleMovieResponseStructure(
            title: 'Batman Begins',
            year: '2005',
            rated: 'PG-13',
            released: '15 Jun 2005',
            runtime: '140 min',
            genre: 'Action, Crime, Drama',
            director: 'Christopher Nolan',
            writer: 'Bob Kane, David S. Goyer',
            actors: 'Christian Bale, Michael Caine, Liam Neeson',
            plot: 'After training with his mentor, Batman begins his fight to free crime-ridden Gotham City from corruption.',
            language: 'English, Urdu, Mandarin',
            country: 'United States, United Kingdom',
            poster: 'https://example.com/poster.jpg',
            imdbRating: '8.2',
            imdbVotes: '1,234,567',
            imdbID: $imdbId,
            type: 'movie'
        );

        $this->mockMovieApiService
            ->shouldReceive('getMovieByImdbId')
            ->with($imdbId)
            ->andReturn($mockMovieData);

        $result = $this->movieService->getMovieByImdbId($imdbId, $this->mockMovieApiService);

        $this->assertInstanceOf(Movie::class, $result);
        $this->assertEquals($imdbId, $result->imdb_id);
        $this->assertEquals('Batman Begins', $result->title);
        $this->assertTrue($result->hasFullData());

        $this->assertDatabaseHas('movies', [
            'imdb_id' => $imdbId,
            'title' => 'Batman Begins',
            'full_data' => true,
        ]);
    }

    public function test_get_movie_by_imdb_id_fetches_from_api_when_movie_is_expired(): void
    {
        $imdbId = 'tt0372784';

        $expiredMovie = Movie::create([
            'title' => 'Batman Begins',
            'year' => '2005',
            'imdb_id' => $imdbId,
            'type' => 'movie',
            'poster' => 'https://example.com/poster.jpg',
            'full_data' => true,
            'created_at' => now()->subDays(8), // Expired
        ]);

        $mockMovieData = new MovieApiSingleMovieResponseStructure(
            title: 'Batman Begins',
            year: '2005',
            rated: 'PG-13',
            released: '15 Jun 2005',
            runtime: '140 min',
            genre: 'Action, Crime, Drama',
            director: 'Christopher Nolan',
            writer: 'Bob Kane, David S. Goyer',
            actors: 'Christian Bale, Michael Caine, Liam Neeson',
            plot: 'After training with his mentor, Batman begins his fight to free crime-ridden Gotham City from corruption.',
            language: 'English, Urdu, Mandarin',
            country: 'United States, United Kingdom',
            poster: 'https://example.com/poster.jpg',
            imdbRating: '8.2',
            imdbVotes: '1,234,567',
            imdbID: $imdbId,
            type: 'movie'
        );

        $this->mockMovieApiService
            ->shouldReceive('getMovieByImdbId')
            ->with($imdbId)
            ->andReturn($mockMovieData);

        $result = $this->movieService->getMovieByImdbId($imdbId, $this->mockMovieApiService);

        $this->assertInstanceOf(Movie::class, $result);
        $this->assertEquals($imdbId, $result->imdb_id);
        $this->assertTrue($result->hasFullData());

        $expiredMovie->refresh();
        $this->assertTrue($expiredMovie->hasFullData());
    }

    public function test_get_movie_by_imdb_id_fetches_from_api_when_movie_has_no_full_data(): void
    {
        $imdbId = 'tt0372784';

        $movie = Movie::create([
            'title' => 'Batman Begins',
            'year' => '2005',
            'imdb_id' => $imdbId,
            'type' => 'movie',
            'poster' => 'https://example.com/poster.jpg',
            'full_data' => false,
        ]);

        $mockMovieData = new MovieApiSingleMovieResponseStructure(
            title: 'Batman Begins',
            year: '2005',
            rated: 'PG-13',
            released: '15 Jun 2005',
            runtime: '140 min',
            genre: 'Action, Crime, Drama',
            director: 'Christopher Nolan',
            writer: 'Bob Kane, David S. Goyer',
            actors: 'Christian Bale, Michael Caine, Liam Neeson',
            plot: 'After training with his mentor, Batman begins his fight to free crime-ridden Gotham City from corruption.',
            language: 'English, Urdu, Mandarin',
            country: 'United States, United Kingdom',
            poster: 'https://example.com/poster.jpg',
            imdbRating: '8.2',
            imdbVotes: '1,234,567',
            imdbID: $imdbId,
            type: 'movie'
        );

        $this->mockMovieApiService
            ->shouldReceive('getMovieByImdbId')
            ->with($imdbId)
            ->andReturn($mockMovieData);

        $result = $this->movieService->getMovieByImdbId($imdbId, $this->mockMovieApiService);

        $this->assertInstanceOf(Movie::class, $result);
        $this->assertEquals($imdbId, $result->imdb_id);
        $this->assertTrue($result->hasFullData());

        $movie->refresh();
        $this->assertTrue($movie->hasFullData());
    }

    public function test_search_trims_whitespace_from_query(): void
    {
        $query = '  batman  ';
        $sessionId = 'session-404';

        $this->createSessionRecord($sessionId);

        $this->mockSearchRepository
            ->shouldReceive('getByQueryAndSessionId')
            ->with('batman', $sessionId)
            ->andReturn(null);

        $mockMovies = collect([
            new MovieApiSearchResponseStructure(
                title: 'Batman',
                year: '1989',
                imdb_id: 'tt0096895',
                type: 'movie',
                poster: 'https://example.com/batman.jpg'
            ),
        ]);

        $this->mockMovieApiService
            ->shouldReceive('search')
            ->with('batman') // Should be trimmed
            ->andReturn($mockMovies);

        $result = $this->movieService->search($query, $this->mockMovieApiService, $sessionId);

        $this->assertInstanceOf(Search::class, $result);
        $this->assertEquals('batman', $result->query); // Should be stored as trimmed
    }
}
