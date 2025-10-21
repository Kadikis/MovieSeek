<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Movie;
use App\Models\Search;
use App\Repositories\SearchRepository;
use App\Services\MovieApiService;
use App\Services\MovieService;
use App\Structures\MovieApiSearchMovieResponseStructure;
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

    private function createGuestRecord(?string $guestUuid = null): void
    {
        if (!$guestUuid) {
            throw new Exception("Guest not found!");
        }

        DB::insert('insert into guests (uuid, ip_address, user_agent, last_seen, expires_at) values (?, ?, ?, ?, ?)', [
            $guestUuid,
            '127.0.0.1',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            now(),
            now()->addDays(30),
        ]);
    }

    public function test_search_returns_null_for_empty_query(): void
    {
        $result = $this->movieService->search('', $this->mockMovieApiService, 'guest-uuid-123');

        $this->assertNull($result);
    }

    public function test_search_returns_null_for_whitespace_only_query(): void
    {
        $result = $this->movieService->search('   ', $this->mockMovieApiService, 'guest-uuid-123');

        $this->assertNull($result);
    }

    public function test_search_returns_existing_valid_search(): void
    {
        $query = 'batman';
        $guestUuid = 'guest-uuid-123';

        $this->createGuestRecord($guestUuid);

        $search = Search::create([
            'query' => $query,
            'guest_uuid' => $guestUuid,
        ]);

        $movie = Movie::create([
            'title' => 'Batman Begins',
            'year' => '2005',
            'imdb_id' => 'tt0372784',
            'type' => 'movie',
            'poster' => 'https://example.com/poster.jpg',
        ]);

        $search->movies()->attach($movie->id);

        $this->mockSearchRepository->shouldReceive('getByQueryAndGuestUuid')->with($query, $guestUuid)->andReturn($search);

        $result = $this->movieService->search($query, $this->mockMovieApiService, $guestUuid);

        $this->assertInstanceOf(Search::class, $result);
        $this->assertEquals($search->id, $result->id);
        $this->assertTrue($result->relationLoaded('movies'));
    }

    public function test_search_creates_new_search_when_none_exists(): void
    {
        $query = 'superman';
        $guestUuid = 'guest-uuid-456';

        $this->createGuestRecord($guestUuid);

        $this->mockSearchRepository
            ->shouldReceive('getByQueryAndGuestUuid')
            ->with($query, $guestUuid)
            ->andReturn(null);

        $mockSearchResponse = new MovieApiSearchResponseStructure(
            error: null,
            movies: collect([
                new MovieApiSearchMovieResponseStructure(
                    title: 'Superman',
                    year: '1978',
                    imdb_id: 'tt0078346',
                    type: 'movie',
                    poster: 'https://example.com/superman.jpg'
                ),
                new MovieApiSearchMovieResponseStructure(
                    title: 'Superman II',
                    year: '1980',
                    imdb_id: 'tt0081573',
                    type: 'movie',
                    poster: 'https://example.com/superman2.jpg'
                ),
            ]),
            total_results: 2,
            total_pages: 1,
            no_results: false
        );

        $this->mockMovieApiService
            ->shouldReceive('search')
            ->with($query, 1)
            ->andReturn($mockSearchResponse);

        $result = $this->movieService->search($query, $this->mockMovieApiService, $guestUuid);

        $this->assertInstanceOf(Search::class, $result);
        $this->assertEquals($query, $result->query);
        $this->assertEquals($guestUuid, $result->guest_uuid);
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
        $guestUuid = 'guest-uuid-789';

        $this->createGuestRecord($guestUuid);

        $expiredSearch = Search::create([
            'query' => $query,
            'guest_uuid' => $guestUuid,
            'created_at' => now()->subDays(4), // Expired (older than 3 days)
        ]);

        $this->mockSearchRepository
            ->shouldReceive('getByQueryAndGuestUuid')
            ->with($query, $guestUuid)
            ->andReturn($expiredSearch);

        $mockSearchResponse = new MovieApiSearchResponseStructure(
            error: null,
            movies: collect([
                new MovieApiSearchMovieResponseStructure(
                    title: 'Spider-Man',
                    year: '2002',
                    imdb_id: 'tt0145487',
                    type: 'movie',
                    poster: 'https://example.com/spiderman.jpg'
                ),
            ]),
            total_results: 1,
            total_pages: 1,
            no_results: false
        );

        $this->mockMovieApiService
            ->shouldReceive('search')
            ->with($query, 1)
            ->andReturn($mockSearchResponse);

        $result = $this->movieService->search($query, $this->mockMovieApiService, $guestUuid);

        $this->assertInstanceOf(Search::class, $result);
        $this->assertNotEquals($expiredSearch->id, $result->id);
        $this->assertEquals($query, $result->query);
        $this->assertEquals($guestUuid, $result->guest_uuid);
    }

    public function test_search_creates_new_search_when_existing_is_empty(): void
    {
        $query = 'wonder woman';
        $guestUuid = 'guest-uuid-101';

        $this->createGuestRecord($guestUuid);

        $emptySearch = Search::create([
            'query' => $query,
            'guest_uuid' => $guestUuid,
        ]);

        $this->mockSearchRepository
            ->shouldReceive('getByQueryAndGuestUuid')
            ->with($query, $guestUuid)
            ->andReturn($emptySearch);

        $mockSearchResponse = new MovieApiSearchResponseStructure(
            error: null,
            movies: collect([
                new MovieApiSearchMovieResponseStructure(
                    title: 'Wonder Woman',
                    year: '2017',
                    imdb_id: 'tt0451279',
                    type: 'movie',
                    poster: 'https://example.com/wonderwoman.jpg'
                ),
            ]),
            total_results: 1,
            total_pages: 1,
            no_results: false
        );

        $this->mockMovieApiService
            ->shouldReceive('search')
            ->with($query, 1)
            ->andReturn($mockSearchResponse);

        $result = $this->movieService->search($query, $this->mockMovieApiService, $guestUuid);

        $this->assertInstanceOf(Search::class, $result);
        $this->assertNotEquals($emptySearch->id, $result->id);
        $this->assertCount(1, $result->movies);
    }

    public function test_search_attaches_existing_movie_to_search(): void
    {
        $query = 'batman';
        $guestUuid = 'guest-uuid-202';

        $this->createGuestRecord($guestUuid);

        $existingMovie = Movie::create([
            'title' => 'The Dark Knight',
            'year' => '2008',
            'imdb_id' => 'tt0468569',
            'type' => 'movie',
            'poster' => 'https://example.com/darkknight.jpg',
        ]);

        $this->mockSearchRepository
            ->shouldReceive('getByQueryAndGuestUuid')
            ->with($query, $guestUuid)
            ->andReturn(null);

        $mockSearchResponse = new MovieApiSearchResponseStructure(
            error: null,
            movies: collect([
                new MovieApiSearchMovieResponseStructure(
                    title: 'The Dark Knight',
                    year: '2008',
                    imdb_id: 'tt0468569',
                    type: 'movie',
                    poster: 'https://example.com/darkknight.jpg'
                ),
            ]),
            total_results: 1,
            total_pages: 1,
            no_results: false
        );

        $this->mockMovieApiService
            ->shouldReceive('search')
            ->with($query, 1)
            ->andReturn($mockSearchResponse);

        $result = $this->movieService->search($query, $this->mockMovieApiService, $guestUuid);

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
        $guestUuid = 'guest-uuid-404';

        $this->createGuestRecord($guestUuid);

        $this->mockSearchRepository
            ->shouldReceive('getByQueryAndGuestUuid')
            ->with('batman', $guestUuid)
            ->andReturn(null);

        $mockSearchResponse = new MovieApiSearchResponseStructure(
            error: null,
            movies: collect([
                new MovieApiSearchMovieResponseStructure(
                    title: 'Batman',
                    year: '1989',
                    imdb_id: 'tt0096895',
                    type: 'movie',
                    poster: 'https://example.com/batman.jpg'
                ),
            ]),
            total_results: 1,
            total_pages: 1,
            no_results: false
        );

        $this->mockMovieApiService
            ->shouldReceive('search')
            ->with('batman', 1) // Should be trimmed
            ->andReturn($mockSearchResponse);

        $result = $this->movieService->search($query, $this->mockMovieApiService, $guestUuid);

        $this->assertInstanceOf(Search::class, $result);
        $this->assertEquals('batman', $result->query); // Should be stored as trimmed
    }

    public function test_load_more_pages_loads_additional_pages(): void
    {
        $guestUuid = 'guest-uuid-505';
        $this->createGuestRecord($guestUuid);

        $search = Search::create([
            'query' => 'superhero',
            'guest_uuid' => $guestUuid,
            'total_results' => 30,
            'total_pages' => 3,
            'no_results' => false,
            'pages_loaded' => 1,
        ]);

        $page2Response = new MovieApiSearchResponseStructure(
            error: null,
            movies: collect([
                new MovieApiSearchMovieResponseStructure(
                    title: 'Superman II',
                    year: '1980',
                    imdb_id: 'tt0081573',
                    type: 'movie',
                    poster: 'https://example.com/superman2.jpg'
                ),
                new MovieApiSearchMovieResponseStructure(
                    title: 'Superman III',
                    year: '1983',
                    imdb_id: 'tt0086393',
                    type: 'movie',
                    poster: 'https://example.com/superman3.jpg'
                ),
            ]),
            total_results: 30,
            total_pages: 3,
            no_results: false
        );

        $page3Response = new MovieApiSearchResponseStructure(
            error: null,
            movies: collect([
                new MovieApiSearchMovieResponseStructure(
                    title: 'Superman IV',
                    year: '1987',
                    imdb_id: 'tt0094074',
                    type: 'movie',
                    poster: 'https://example.com/superman4.jpg'
                ),
            ]),
            total_results: 30,
            total_pages: 3,
            no_results: false
        );

        $this->mockMovieApiService
            ->shouldReceive('search')
            ->with('superhero', 2)
            ->andReturn($page2Response);

        $this->mockMovieApiService
            ->shouldReceive('search')
            ->with('superhero', 3)
            ->andReturn($page3Response);

        $result = $this->movieService->loadMorePages($search, $this->mockMovieApiService);

        $this->assertInstanceOf(Search::class, $result);
        $this->assertEquals(3, $result->pages_loaded);
        $this->assertTrue($result->relationLoaded('movies'));
        $this->assertCount(3, $result->movies);

        $this->assertDatabaseHas('movies', [
            'title' => 'Superman II',
            'imdb_id' => 'tt0081573',
        ]);
        $this->assertDatabaseHas('movies', [
            'title' => 'Superman III',
            'imdb_id' => 'tt0086393',
        ]);
        $this->assertDatabaseHas('movies', [
            'title' => 'Superman IV',
            'imdb_id' => 'tt0094074',
        ]);
    }

    public function test_load_more_pages_returns_unchanged_when_all_pages_loaded(): void
    {
        $guestUuid = 'guest-uuid-606';
        $this->createGuestRecord($guestUuid);

        $search = Search::create([
            'query' => 'action',
            'guest_uuid' => $guestUuid,
            'total_results' => 10,
            'total_pages' => 2,
            'no_results' => false,
            'pages_loaded' => 2,
        ]);

        $result = $this->movieService->loadMorePages($search, $this->mockMovieApiService);

        $this->assertInstanceOf(Search::class, $result);
        $this->assertEquals(2, $result->pages_loaded);
        $this->assertEquals($search->id, $result->id);

        $this->mockMovieApiService->shouldNotHaveReceived('search');
    }

    public function test_load_more_pages_attaches_existing_movies(): void
    {
        $guestUuid = 'guest-uuid-808';
        $this->createGuestRecord($guestUuid);

        $existingMovie = Movie::create([
            'title' => 'Existing Movie',
            'year' => '2019',
            'imdb_id' => 'tt9999999',
            'type' => 'movie',
            'poster' => 'https://example.com/existing.jpg',
        ]);

        $search = Search::create([
            'query' => 'drama',
            'guest_uuid' => $guestUuid,
            'total_results' => 20,
            'total_pages' => 2,
            'no_results' => false,
            'pages_loaded' => 1,
        ]);

        $page2Response = new MovieApiSearchResponseStructure(
            error: null,
            movies: collect([
                new MovieApiSearchMovieResponseStructure(
                    title: 'Existing Movie',
                    year: '2019',
                    imdb_id: 'tt9999999',
                    type: 'movie',
                    poster: 'https://example.com/existing.jpg'
                ),
                new MovieApiSearchMovieResponseStructure(
                    title: 'New Movie',
                    year: '2021',
                    imdb_id: 'tt8888888',
                    type: 'movie',
                    poster: 'https://example.com/new.jpg'
                ),
            ]),
            total_results: 20,
            total_pages: 2,
            no_results: false
        );

        $this->mockMovieApiService
            ->shouldReceive('search')
            ->with('drama', 2)
            ->andReturn($page2Response);

        $result = $this->movieService->loadMorePages($search, $this->mockMovieApiService);

        $this->assertInstanceOf(Search::class, $result);
        $this->assertEquals(2, $result->pages_loaded);
        $this->assertCount(2, $result->movies);

        $this->assertDatabaseCount('movies', 2); // Should not create duplicate
        $this->assertDatabaseHas('search_movies', [
            'search_id' => $search->id,
            'movie_id' => $existingMovie->id,
        ]);
    }

    public function test_load_more_pages_with_custom_pages_to_load(): void
    {
        $guestUuid = 'guest-uuid-909';
        $this->createGuestRecord($guestUuid);

        $search = Search::create([
            'query' => 'thriller',
            'guest_uuid' => $guestUuid,
            'total_results' => 50,
            'total_pages' => 5,
            'no_results' => false,
            'pages_loaded' => 1,
        ]);

        $page2Response = new MovieApiSearchResponseStructure(
            error: null,
            movies: collect([
                new MovieApiSearchMovieResponseStructure(
                    title: 'Thriller 2',
                    year: '2020',
                    imdb_id: 'tt2222222',
                    type: 'movie',
                    poster: 'https://example.com/thriller2.jpg'
                ),
            ]),
            total_results: 50,
            total_pages: 5,
            no_results: false
        );

        $page3Response = new MovieApiSearchResponseStructure(
            error: null,
            movies: collect([
                new MovieApiSearchMovieResponseStructure(
                    title: 'Thriller 3',
                    year: '2021',
                    imdb_id: 'tt3333333',
                    type: 'movie',
                    poster: 'https://example.com/thriller3.jpg'
                ),
            ]),
            total_results: 50,
            total_pages: 5,
            no_results: false
        );

        $this->mockMovieApiService
            ->shouldReceive('search')
            ->with('thriller', 2)
            ->andReturn($page2Response);

        $this->mockMovieApiService
            ->shouldReceive('search')
            ->with('thriller', 3)
            ->andReturn($page3Response);

        $result = $this->movieService->loadMorePages($search, $this->mockMovieApiService, 2);

        $this->assertInstanceOf(Search::class, $result);
        $this->assertEquals(3, $result->pages_loaded);
        $this->assertCount(2, $result->movies);

        $this->assertDatabaseHas('movies', [
            'title' => 'Thriller 2',
            'imdb_id' => 'tt2222222',
        ]);
        $this->assertDatabaseHas('movies', [
            'title' => 'Thriller 3',
            'imdb_id' => 'tt3333333',
        ]);
    }
}
