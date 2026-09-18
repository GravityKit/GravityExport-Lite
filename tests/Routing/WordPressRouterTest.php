<?php

namespace GFExcel\Tests\Routing;

use GFExcel\Routing\Request;
use GFExcel\Routing\Router;
use GFExcel\Routing\WordPressRouter;
use GFExcel\Tests\TestCase;

/**
 * Unit tests for {@see WordPressRouter}.
 *
 * @since 2.4.0
 */
final class WordPressRouterTest extends TestCase {
	/**
	 * The class under test.
	 *
	 * @since 2.4.0
	 *
	 * @var WordPressRouter
	 */
	private $router;

	/**
	 * {@inheritdoc}
	 * @since 2.4.0
	 */
	/**
	 * The global `$wpdb` as it was before a test replaced it.
	 *
	 * @since TBD
	 *
	 * @var mixed
	 */
	private $original_wpdb;

	/**
	 * {@inheritdoc}
	 * @since 2.4.0
	 */
	public function setUp(): void {
		parent::setUp();

		$this->original_wpdb = $GLOBALS['wpdb'] ?? null;
		$this->router        = new WordPressRouter();
	}

	/**
	 * {@inheritdoc}
	 * @since TBD
	 */
	public function tearDown(): void {
		$GLOBALS['wpdb'] = $this->original_wpdb;

		parent::tearDown();
	}

	/**
	 * Data provider for matches test cases.
	 * @since 2.4.0
	 * @return array
	 */
	public function dataprovider_for_matches_test(): array {
		return [
			[ 'gravityexport', true ],
			[ 'gravityexport-lite', true ],
			[ 'gf-entries-in-excel', true ],
			[ 'invalid-action', false ],
		];
	}

	/**
	 * Test case for {@see WordPressRouter::matches()}.
	 * @since 2.4.0
	 * @dataProvider dataprovider_for_matches_test
	 */
	public function test_matches( string $action, bool $expected ): void {
		$request = Request::from_query_vars( [
			Router::KEY_ACTION => $action,
			Router::KEY_HASH   => 'test-hash'
		] );

		$this->assertSame( $expected, $this->router->matches( $request ) );
	}

	/**
	 * Test case for {@see WordPressRouter::matches()} with a non-string action.
	 *
	 * Query vars can arrive as an array. `Request::action()` declares a string return type, so an array
	 * must be discarded when the request is built rather than fatalling when the action is read.
	 *
	 * @since TBD
	 * @covers \GFExcel\Routing\Request::from_query_vars
	 * @see https://linear.app/gravitykit/issue/GEXPLIT-24
	 */
	public function test_matches_with_a_non_string_action(): void {
		$request = Request::from_query_vars( [
			Router::KEY_ACTION => [ 'gravityexport-lite' ],
			Router::KEY_HASH   => 'some-hash',
		] );

		$this->assertSame( '', $request->action() );
		$this->assertFalse( $this->router->matches( $request ) );
	}

	/**
	 * Test case for {@see WordPressRouter::endpoints()}.
	 * @since 2.4.0
	 */
	public function test_endpoints(): void {
		$expected = [
			'gf-entries-in-excel',
			'gravityexport-lite',
			'gravityexport',
		];

		$this->assertSame( $expected, $this->router->endpoints() );
	}

	/**
	 * Data provider of hashes that must never reach the feed lookup.
	 * @since TBD
	 * @return array
	 */
	public function dataprovider_for_empty_hash_test(): array {
		return [
			'missing'          => [ [ Router::KEY_ACTION => 'gravityexport-lite' ] ],
			'empty string'     => [ [ Router::KEY_ACTION => 'gravityexport-lite', Router::KEY_HASH => '' ] ],
			'extension only'   => [ [ Router::KEY_ACTION => 'gravityexport-lite', Router::KEY_HASH => '.xlsx' ] ],
			'csv extension'    => [ [ Router::KEY_ACTION => 'gravityexport-lite', Router::KEY_HASH => '.csv' ] ],
			'single dot'       => [ [ Router::KEY_ACTION => 'gravityexport-lite', Router::KEY_HASH => '.' ] ],
			'double dot'       => [ [ Router::KEY_ACTION => 'gravityexport-lite', Router::KEY_HASH => '..' ] ],
			'dot dot extension' => [ [ Router::KEY_ACTION => 'gravityexport-lite', Router::KEY_HASH => '..xlsx' ] ],
			'array'            => [ [ Router::KEY_ACTION => 'gravityexport-lite', Router::KEY_HASH => [ 'x' ] ] ],
			'array action'     => [ [ Router::KEY_ACTION => [ 'gravityexport-lite' ], Router::KEY_HASH => '' ] ],
		];
	}

	/**
	 * Test case proving a non-empty hash is not refused by the empty-hash guard.
	 *
	 * Pins the choice of `'' === $hash` over `empty()`, which would also reject a hash of "0".
	 *
	 * @since TBD
	 * @covers \GFExcel\Routing\WordPressRouter::get_feed_by_request
	 */
	public function test_get_feed_by_request_does_not_refuse_a_zero_hash(): void {
		global $wpdb;

		$wpdb = $this->wpdb_serving_feed( [ 'hash' => '0' ] );

		$result = $this->router->get_feed_by_request( Request::from_query_vars( [
			Router::KEY_ACTION => 'gravityexport-lite',
			Router::KEY_HASH   => '0',
		] ) );

		$this->assertIsArray( $result, 'A hash of "0" is not empty and must still be looked up.' );
	}

	/**
	 * Test case for {@see WordPressRouter::get_feed_by_request()} refusing an empty hash.
	 *
	 * An empty hash turns the feed lookup's `meta LIKE '%<hash>%'` into `'%%'`, which matches every active
	 * feed of every add-on, and then equals the stored hash of any feed whose hash was cleared or never
	 * set. Such a request must be refused before the database is touched.
	 *
	 * @since TBD
	 * @dataProvider dataprovider_for_empty_hash_test
	 * @covers \GFExcel\Routing\WordPressRouter::get_feed_by_request
	 * @see https://linear.app/gravitykit/issue/GEXPLIT-24
	 */
	public function test_get_feed_by_request_refuses_empty_hash( array $query_vars ): void {
		global $wpdb;

		$wpdb = new class {
			/**
			 * @var string The table prefix.
			 */
			public $prefix = 'wp_';

			/**
			 * @var bool Whether the database was consulted.
			 */
			public $was_queried = false;

			public function prepare( $query, ...$args ) {
				$this->was_queried = true;

				return $query;
			}

			public function esc_like( $text ) {
				$this->was_queried = true;

				return $text;
			}

			public function get_results( $query, $output = null ) {
				$this->was_queried = true;

				return [];
			}
		};

		$result = $this->router->get_feed_by_request( Request::from_query_vars( $query_vars ) );

		$this->assertNull( $result, 'An empty hash must never resolve to a feed.' );
		$this->assertFalse( $wpdb->was_queried, 'An empty hash must be refused before the feed lookup runs.' );
	}

	/**
	 * Test case for {@see WordPressRouter::get_feed_by_request()} skipping the filter on an empty hash.
	 *
	 * The not-found path hands `gfexcel_hash_feed` a chance to resolve the feed. An empty hash must not
	 * reach it, otherwise a third-party callback could reintroduce the very bypass this guard closes.
	 *
	 * @since TBD
	 * @covers \GFExcel\Routing\WordPressRouter::get_feed_by_request
	 * @see https://linear.app/gravitykit/issue/GEXPLIT-24
	 */
	public function test_get_feed_by_request_skips_the_filter_on_an_empty_hash(): void {
		global $wpdb;

		$wpdb = $this->wpdb_serving_feed( [] );

		\WP_Mock::onFilter( 'gfexcel_hash_feed' )
			->with( null, '' )
			->reply( [ 'id' => '1', 'form_id' => '1', 'meta' => [] ] );

		$result = $this->router->get_feed_by_request( Request::from_query_vars( [
			Router::KEY_ACTION => 'gravityexport-lite',
			Router::KEY_HASH   => '',
		] ) );

		$this->assertNull( $result, 'A filter must not be able to resolve an empty hash to a feed.' );
	}

	/**
	 * Test case for {@see WordPressRouter::get_feed_by_request()} still looking up a real hash.
	 *
	 * Guards the guard: refusing an empty hash must not stop a genuine download URL from being resolved.
	 *
	 * @since TBD
	 * @covers \GFExcel\Routing\WordPressRouter::get_feed_by_request
	 */
	public function test_get_feed_by_request_still_queries_a_real_hash(): void {
		global $wpdb;

		$wpdb = new class {
			/**
			 * @var string The table prefix.
			 */
			public $prefix = 'wp_';

			/**
			 * @var bool Whether the database was consulted.
			 */
			public $was_queried = false;

			public function prepare( $query, ...$args ) {
				return $query;
			}

			public function esc_like( $text ) {
				return $text;
			}

			public function get_results( $query, $output = null ) {
				$this->was_queried = true;

				return [];
			}
		};

		\WP_Mock::onFilter( 'gfexcel_hash_feed' )->with( null, 'a-real-looking-hash' )->reply( null );

		$this->router->get_feed_by_request( Request::from_query_vars( [
			Router::KEY_ACTION => 'gravityexport-lite',
			Router::KEY_HASH   => 'a-real-looking-hash',
		] ) );

		$this->assertTrue( $wpdb->was_queried, 'A non-empty hash must still be looked up.' );
	}

	/**
	 * Returns a `$wpdb` double that serves one feed row.
	 *
	 * @since TBD
	 *
	 * @param array $meta The feed's stored meta, as an array.
	 *
	 * @return object The double.
	 */
	private function wpdb_serving_feed( array $meta ) {
		$wpdb = new class {
			/**
			 * @var string The table prefix.
			 */
			public $prefix = 'wp_';

			/**
			 * @var string The encoded meta to serve.
			 */
			public $meta = '{}';

			public function prepare( $query, ...$args ) {
				return $query;
			}

			public function esc_like( $text ) {
				return $text;
			}

			public function get_results( $query, $output = null ) {
				return [ [ 'id' => '7', 'form_id' => '3', 'meta' => $this->meta ] ];
			}
		};

		$wpdb->meta = json_encode( $meta );

		return $wpdb;
	}

	/**
	 * Data provider of stored feed meta that must not authorise a request.
	 * @since TBD
	 * @return array
	 */
	public function dataprovider_for_non_matching_feed_test(): array {
		return [
			'no hash key'    => [ [ 'feedName' => 'Some other add-on' ] ],
			'poisoned row'   => [ [ 'download_count' => 1 ] ],
			'hash cleared'   => [ [ 'hash' => '' ] ],
			'hash null'      => [ [ 'hash' => null ] ],
			'empty meta'     => [ [] ],
			'different hash' => [ [ 'hash' => 'a-completely-different-hash' ] ],
			'substring hit'  => [ [ 'hash' => 'xa-real-looking-hashx' ] ],
		];
	}

	/**
	 * Data provider of hash pairs that a loose comparison would wrongly treat as equal.
	 *
	 * Hashes are hexadecimal, so a value of the form `0e<digits>` is possible. PHP compares two numeric
	 * strings numerically, which makes every such value loosely equal to every other.
	 *
	 * @since TBD
	 * @return array
	 */
	public function dataprovider_for_type_juggling_test(): array {
		return [
			'exponent notation' => [ '0e11111111111111111111111111111', '0e22222222222222222222222222222' ],
			'leading zeroes'    => [ '0000000000000000000000000000001', '1' ],
		];
	}

	/**
	 * Test case for {@see WordPressRouter::get_feed_by_request()} comparing hashes strictly.
	 *
	 * @since TBD
	 * @dataProvider dataprovider_for_type_juggling_test
	 * @covers \GFExcel\Routing\WordPressRouter::get_feed_by_request
	 * @see https://linear.app/gravitykit/issue/GEXPLIT-24
	 */
	public function test_get_feed_by_request_compares_hashes_strictly( string $stored, string $requested ): void {
		global $wpdb;

		$wpdb = $this->wpdb_serving_feed( [ 'hash' => $stored ] );

		$result = $this->router->get_feed_by_request( Request::from_query_vars( [
			Router::KEY_ACTION => 'gravityexport-lite',
			Router::KEY_HASH   => $requested,
		] ) );

		$this->assertNull( $result, 'Hashes must be compared strictly, never with loose equality.' );
	}

	/**
	 * Test case for {@see WordPressRouter::get_feed_by_request()} authorising against the stored hash.
	 *
	 * The feed table is shared by every Gravity Forms add-on and the lookup matches on a substring, so the
	 * row that comes back is not necessarily the one that owns the requested hash. This comparison is the
	 * control that refuses it, and it must stay strict.
	 *
	 * @since TBD
	 * @dataProvider dataprovider_for_non_matching_feed_test
	 * @covers \GFExcel\Routing\WordPressRouter::get_feed_by_request
	 * @see https://linear.app/gravitykit/issue/GEXPLIT-24
	 */
	public function test_get_feed_by_request_refuses_a_feed_that_does_not_own_the_hash( array $meta ): void {
		global $wpdb;

		$wpdb = $this->wpdb_serving_feed( $meta );

		$result = $this->router->get_feed_by_request( Request::from_query_vars( [
			Router::KEY_ACTION => 'gravityexport-lite',
			Router::KEY_HASH   => 'the-requested-hash',
		] ) );

		$this->assertNull( $result, 'Only the feed that stores the requested hash may be returned.' );
	}

	/**
	 * Test case for {@see WordPressRouter::get_feed_by_request()} returning the owning feed.
	 *
	 * @since TBD
	 * @covers \GFExcel\Routing\WordPressRouter::get_feed_by_request
	 */
	public function test_get_feed_by_request_returns_the_feed_that_owns_the_hash(): void {
		global $wpdb;

		$wpdb = $this->wpdb_serving_feed( [ 'hash' => 'the-requested-hash' ] );

		$result = $this->router->get_feed_by_request( Request::from_query_vars( [
			Router::KEY_ACTION => 'gravityexport-lite',
			Router::KEY_HASH   => 'the-requested-hash',
		] ) );

		$this->assertIsArray( $result, 'The feed storing the requested hash must be returned.' );
		$this->assertSame( '3', $result['form_id'] );
		$this->assertIsArray( $result['meta'], 'The returned feed must carry decoded meta.' );
		$this->assertSame( 'the-requested-hash', $result['meta']['hash'] );
	}

	/**
	 * Test case for {@see WordPressRouter::get_feed_by_request()} with meta that is not valid JSON.
	 *
	 * @since TBD
	 * @covers \GFExcel\Routing\WordPressRouter::get_feed_by_request
	 */
	public function test_get_feed_by_request_refuses_a_feed_with_unreadable_meta(): void {
		global $wpdb;

		$wpdb = $this->wpdb_serving_feed( [] );
		$wpdb->meta = 'not json at all';

		\WP_Mock::onFilter( 'gfexcel_hash_feed' )->with( null, 'the-requested-hash' )->reply( null );

		$result = $this->router->get_feed_by_request( Request::from_query_vars( [
			Router::KEY_ACTION => 'gravityexport-lite',
			Router::KEY_HASH   => 'the-requested-hash',
		] ) );

		$this->assertNull( $result );
	}

	/**
	 * Test case proving the vendored Gravity Forms helpers were copied without drift.
	 *
	 * The authorisation check depends on the exact semantics of `rgar()`, so the copy in
	 * `tests/stubs/gravity-forms-functions.php` must stay identical to the vendored original.
	 *
	 * @since TBD
	 */
	public function test_gravity_forms_helper_stubs_match_the_vendored_source(): void {
		$vendored = file_get_contents( __DIR__ . '/../../vendor/gravityforms/gravityforms/gravityforms.php' );
		$stub     = file_get_contents( __DIR__ . '/../stubs/gravity-forms-functions.php' );

		$this->assertIsString( $vendored, 'The vendored Gravity Forms source must be readable.' );

		foreach ( [ 'rgar', 'rgars' ] as $function ) {
			$pattern = '/\n\tfunction ' . $function . '\(.*?\n\t\}\n/s';

			$this->assertSame(
				1,
				preg_match( $pattern, $vendored, $original ),
				sprintf( 'Could not find %s() in the vendored Gravity Forms source.', $function )
			);
			$this->assertSame(
				1,
				preg_match( $pattern, $stub, $copy ),
				sprintf( 'Could not find %s() in the stub.', $function )
			);
			$this->assertSame(
				preg_replace( '/\s+/', ' ', $original[0] ),
				preg_replace( '/\s+/', ' ', $copy[0] ),
				sprintf( '%s() has drifted from the vendored Gravity Forms implementation.', $function )
			);
		}
	}

	/**
	 * Test case pinning the Gravity Forms behavior the authorisation check depends on.
	 *
	 * `rgar()` returns an empty string, not null, for a missing key when no default is given. That is why a
	 * request carrying an empty hash used to satisfy the comparison against a feed that has no stored hash.
	 * If this ever stops being true, the guard's reasoning needs revisiting.
	 *
	 * @since TBD
	 */
	public function test_rgars_returns_empty_string_for_a_missing_key(): void {
		$this->assertSame( '', rgars( [ 'meta' => [ 'feedName' => 'x' ] ], 'meta/hash' ) );
		$this->assertSame( '', rgars( [ 'meta' => [] ], 'meta/hash' ) );
		$this->assertSame( '', rgars( [ 'meta' => [ 'hash' => null ] ], 'meta/hash' ) );
		$this->assertNull( rgars( [ 'meta' => 'not-an-array' ], 'meta/hash' ) );
	}

	/**
	 * Test case for {@see WordPressRouter::update_query_vars()}.
	 * @since 2.4.0
	 */
	public function test_update_query_vars(): void {
		$original_vars = [ 'post_type', 'page_id' ];
		$expected      = [
			'post_type',
			'page_id',
			Router::KEY_ACTION,
			Router::KEY_HASH,
		];

		$result = $this->router->update_query_vars( $original_vars );

		$this->assertSame( $expected, $result );
	}
}
