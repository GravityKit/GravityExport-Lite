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
	public function setUp(): void {
		parent::setUp();

		$this->router = new WordPressRouter();
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
