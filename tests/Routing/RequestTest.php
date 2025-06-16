<?php

namespace GFExcel\Tests\Routing;

use GFExcel\Routing\Request;
use GFExcel\Routing\Router;
use GFExcel\Tests\TestCase;

/**
 * Unit tests for {@see Request}.
 * @since $ver$
 */
final class RequestTest extends TestCase {
	/**
	 * Test case for {@see Request::from_query_vars()}.
	 *
	 * @since $ver$
	 */
	public function test_from_query_vars(): void {
		$query_vars = [
			Router::KEY_ACTION => 'gravityexport-lite',
			Router::KEY_HASH   => 'test-hash-123'
		];

		$request = Request::from_query_vars( $query_vars );

		$this->assertSame( 'gravityexport-lite', $request->action() );
		$this->assertSame( 'test-hash-123', $request->hash() );
	}

	/**
	 * Test case for {@see Request::from_query_vars()} with empty query vars.
	 *
	 * @since $ver$
	 */
	public function test_from_query_vars_with_empty_vars(): void {
		$request = Request::from_query_vars( [] );

		$this->assertSame( '', $request->action() );
		$this->assertSame( '', $request->hash() );
	}

	/**
	 * Test case for {@see Request::from_query_vars()} with partial query vars.
	 *
	 * @since $ver$
	 */
	public function test_from_query_vars_with_partial_vars(): void {
		$query_vars = [
			Router::KEY_ACTION => 'gravityexport-lite'
		];

		$request = Request::from_query_vars( $query_vars );

		$this->assertSame( 'gravityexport-lite', $request->action() );
		$this->assertSame( '', $request->hash() );
	}

	/**
	 * Test case for {@see Request::hash()} without extension.
	 *
	 * @since $ver$
	 */
	public function test_hash_without_extension(): void {
		$query_vars = [
			Router::KEY_HASH => 'simple-hash'
		];

		$request = Request::from_query_vars( $query_vars );

		$this->assertSame( 'simple-hash', $request->hash() );
	}

	/**
	 * Test case for {@see Request::hash()} with extension.
	 *
	 * @since $ver$
	 */
	public function test_hash_with_extension(): void {
		$query_vars = [
			Router::KEY_HASH => 'hash-with-extension.xlsx'
		];

		$request = Request::from_query_vars( $query_vars );

		$this->assertSame( 'hash-with-extension', $request->hash() );
	}

	/**
	 * Data provider for extension test cases.
	 *
	 * @since $ver$
	 *
	 * @return array
	 */
	public function extension_data_provider(): array {
		return [
			'without extension'        => [
				'simple-hash',
				null
			],
			'with xlsx extension'      => [
				'hash-with-extension.xlsx',
				'xlsx'
			],
			'with csv extension'       => [
				'hash-with-extension.csv',
				'csv'
			],
			'with uppercase extension' => [
				'hash-with-extension.XLSX',
				'xlsx'
			],
			'with empty extension'     => [
				'hash-with-dot.',
				null
			],
		];
	}

	/**
	 * Test case for {@see Request::extension()}.
	 *
	 * @since $ver$
	 *
	 * @dataProvider extension_data_provider
	 */
	public function test_extension( string $hash, ?string $expected ): void {
		$query_vars = [
			Router::KEY_HASH => $hash
		];

		$request = Request::from_query_vars( $query_vars );

		$this->assertSame( $expected, $request->extension() );
	}

	/**
	 * Test case for {@see Request::action()}.
	 *
	 * @since $ver$
	 */
	public function test_action(): void {
		$query_vars = [
			Router::KEY_ACTION => 'gf-entries-in-excel'
		];

		$request = Request::from_query_vars( $query_vars );

		$this->assertSame( 'gf-entries-in-excel', $request->action() );
	}
}
