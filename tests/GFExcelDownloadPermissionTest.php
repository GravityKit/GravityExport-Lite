<?php

namespace GFExcel\Tests;

use GFExcel\GFExcel;
use GFExcel\Routing\Router;

/**
 * Unit tests for {@see GFExcel::canDownloadForm()} and the `gk/gravityexport/download/can-download` filter.
 *
 * `GFCommon` and `GravityExportAddon` need Gravity Forms, so they are alias mocks, which need a fresh process.
 *
 * Extends PHPUnit's TestCase rather than WP_Mock's: on PHP 8.4, loading WP_Mock 0.4's TestCase prints a deprecation
 * notice, and PHPUnit fails every separate-process test that prints one.
 *
 * @since TBD
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class GFExcelDownloadPermissionTest extends \PHPUnit\Framework\TestCase {
	use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

	/**
	 * @inheritDoc
	 * @since TBD
	 */
	protected function setUp(): void {
		parent::setUp();
		\WP_Mock::setUp();
	}

	/**
	 * @inheritDoc
	 * @since TBD
	 */
	protected function tearDown(): void {
		\WP_Mock::tearDown();
		parent::tearDown();
	}

	/**
	 * The filter under test.
	 *
	 * @since TBD
	 */
	private const FILTER = 'gk/gravityexport/download/can-download';

	/**
	 * A secured feed for form 3.
	 *
	 * @since TBD
	 *
	 * @var array
	 */
	private $secured_feed = [
		'id'      => 5,
		'form_id' => 3,
		'meta'    => [ 'is_secured' => '1' ],
	];

	/**
	 * Replaces the add-on with a mock whose form feed is the given feed.
	 *
	 * @since TBD
	 *
	 * @param array $feed The feed the add-on returns for the form.
	 */
	private function mockAddon( array $feed ): void {
		$addon = \Mockery::mock( 'alias:GFExcel\Addon\GravityExportAddon' );
		$addon->shouldReceive( 'get_instance' )->andReturn( $addon );
		$addon->shouldReceive( 'get_feed_by_form_id' )->with( 3 )->andReturn( $feed );
	}

	/**
	 * Sets whether the current user has the Gravity Forms export capability.
	 *
	 * @since TBD
	 *
	 * @param bool $has_capability Whether the user has the capability.
	 */
	private function mockCapability( bool $has_capability ): void {
		\Mockery::mock( 'alias:GFCommon' )
			->shouldReceive( 'current_user_can_any' )
			->with( 'gravityforms_export_entries' )
			->andReturn( $has_capability );
	}

	/**
	 * Sets the current user's ID.
	 *
	 * @since TBD
	 *
	 * @param int $user_id The user ID.
	 */
	private function mockUser( int $user_id ): void {
		\WP_Mock::userFunction( 'get_current_user_id', [ 'return' => $user_id ] );
	}

	/**
	 * With no filter added, a secured download still follows the capability check.
	 *
	 * @since TBD
	 */
	public function test_secured_download_without_a_filter_follows_the_capability(): void {
		$this->mockAddon( $this->secured_feed );
		$this->mockCapability( true );
		$this->mockUser( 7 );

		self::assertTrue( GFExcel::canDownloadForm( 3, $this->secured_feed ) );
	}

	/**
	 * With no filter added, a user without the capability is refused.
	 *
	 * @since TBD
	 */
	public function test_secured_download_without_a_filter_refuses_a_user_without_the_capability(): void {
		$this->mockAddon( $this->secured_feed );
		$this->mockCapability( false );
		$this->mockUser( 7 );

		self::assertFalse( GFExcel::canDownloadForm( 3, $this->secured_feed ) );
	}

	/**
	 * A filter can allow a user without the capability, and receives the feed and the user ID.
	 *
	 * @since TBD
	 */
	public function test_filter_can_allow_a_user_without_the_capability(): void {
		$this->mockAddon( $this->secured_feed );
		$this->mockCapability( false );
		$this->mockUser( 7 );

		\WP_Mock::onFilter( self::FILTER )->with( false, $this->secured_feed, 7 )->reply( true );

		self::assertTrue( GFExcel::canDownloadForm( 3, $this->secured_feed ) );
	}

	/**
	 * A filter can refuse a user who has the capability.
	 *
	 * @since TBD
	 */
	public function test_filter_can_refuse_a_user_with_the_capability(): void {
		$this->mockAddon( $this->secured_feed );
		$this->mockCapability( true );
		$this->mockUser( 7 );

		\WP_Mock::onFilter( self::FILTER )->with( true, $this->secured_feed, 7 )->reply( false );

		self::assertFalse( GFExcel::canDownloadForm( 3, $this->secured_feed ) );
	}

	/**
	 * Called without a feed, the filter receives the form's feed.
	 *
	 * @since TBD
	 */
	public function test_filter_receives_the_forms_feed_when_none_is_passed(): void {
		$this->mockAddon( $this->secured_feed );
		$this->mockCapability( false );
		$this->mockUser( 7 );

		\WP_Mock::onFilter( self::FILTER )->with( false, $this->secured_feed, 7 )->reply( true );

		self::assertTrue( GFExcel::canDownloadForm( 3 ) );
	}

	/**
	 * A public download URL is served without asking the filter, so a filter cannot refuse it.
	 *
	 * @since TBD
	 */
	public function test_public_download_is_not_filtered(): void {
		$public_feed = [ 'id' => 5, 'form_id' => 3, 'meta' => [ 'is_secured' => '' ] ];
		$this->mockAddon( $public_feed );
		$this->mockCapability( false );
		$this->mockUser( 7 );

		\WP_Mock::onFilter( self::FILTER )->with( true, $public_feed, 7 )->reply( false );

		self::assertTrue( GFExcel::canDownloadForm( 3, $public_feed ) );
	}

	/**
	 * A secured feed is not served as public when the form's main feed is public.
	 *
	 * @since TBD
	 */
	public function test_secured_feed_on_a_form_with_a_public_main_feed_is_checked(): void {
		$public_feed = [ 'id' => 4, 'form_id' => 3, 'meta' => [ 'is_secured' => '' ] ];
		$this->mockAddon( $public_feed );
		$this->mockCapability( false );
		$this->mockUser( 7 );

		self::assertFalse( GFExcel::canDownloadForm( 3, $this->secured_feed ) );
	}

	/**
	 * The download request passes the feed it matched to the filter.
	 *
	 * @since TBD
	 */
	public function test_download_request_passes_its_feed_to_the_filter(): void {
		$this->mockAddon( $this->secured_feed );
		$this->mockCapability( false );
		$this->mockUser( 7 );

		$router = \Mockery::mock( Router::class );
		$router->shouldReceive( 'matches' )->andReturn( true );
		$router->shouldReceive( 'get_feed_by_request' )->andReturn( $this->secured_feed );

		if ( ! class_exists( \WP_Http::class ) ) {
			require_once __DIR__ . '/stubs/wp-http.php';
		}

		$plugin = new GFExcel( $router );

		$refused = $plugin->request( [] );
		self::assertSame( \WP_Http::FORBIDDEN, $refused['error'] ?? null );

		\WP_Mock::onFilter( self::FILTER )->with( false, $this->secured_feed, 7 )->reply( true );

		$allowed = $plugin->request( [] );
		self::assertArrayNotHasKey( 'error', $allowed );
		self::assertSame( 3, $allowed['gfexcel_download_form'] ?? null );
		self::assertSame( 5, $allowed['gfexcel_download_feed'] ?? null );
	}
}
