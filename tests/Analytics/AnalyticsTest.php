<?php

namespace GFExcel\Tests\Analytics;

use GFExcel\Analytics\Analytics;
use GFExcel\Analytics\Client;
use GFExcel\Tests\TestCase;

/**
 * Unit tests for {@see Analytics}, the permanent facade.
 *
 * These are the tests that hold the design together. The whole point of the
 * facade is that Lite's analytics implementation can be deleted without editing
 * a single call site, and that only holds if delegation is duck-typed and
 * resolution happens late.
 *
 * @since $ver$
 */
class AnalyticsTest extends TestCase {
	/**
	 * @inheritdoc
	 * @since $ver$
	 */
	public function setUp(): void {
		parent::setUp();

		Analytics::reset();
		FakeFoundationService::$captured = [];
		FakeFoundation::$service         = null;
	}

	/**
	 * @inheritdoc
	 * @since $ver$
	 */
	public function tearDown(): void {
		Analytics::reset();
		FakeFoundation::$service = null;

		parent::tearDown();
	}

	/**
	 * With no backend at all, calls are silently inert rather than fatal.
	 * A call site must never be able to bring down a page.
	 *
	 * @since $ver$
	 */
	public function testCaptureIsInertWithNoBackend(): void {
		Analytics::capture( 'export_completed', [ 'file_format' => 'csv' ] );

		self::assertFalse( Analytics::isEnabled() );
	}

	/**
	 * @since $ver$
	 */
	public function testFallsBackToTheLocalClientWhenFoundationIsAbsent(): void {
		$client = $this->createMock( Client::class );
		$client->expects( self::once() )
		       ->method( 'capture' )
		       ->with( 'export_completed', [ 'file_format' => 'csv' ] );

		Analytics::setLocalClient( $client );
		Analytics::capture( 'export_completed', [ 'file_format' => 'csv' ] );

		self::assertFalse( Analytics::isDelegating() );
	}

	/**
	 * The handover, simulated: Foundation is present, so it wins, and the local
	 * client is never called even though it is still registered.
	 *
	 * @since $ver$
	 */
	public function testDelegatesToFoundationWhenPresent(): void {
		FakeFoundation::$service = new FakeFoundationService();

		$client = $this->createMock( Client::class );
		$client->expects( self::never() )->method( 'capture' );

		Analytics::setLocalClient( $client );
		Analytics::capture( 'export_completed', [ 'file_format' => 'pdf' ] );

		self::assertTrue( Analytics::isDelegating() );
		self::assertSame(
			[ [ 'export_completed', [ 'file_format' => 'pdf' ] ] ],
			FakeFoundationService::$captured
		);
	}

	/**
	 * The deletion proof. With no local client registered at all — the state
	 * after Client and its collaborators are removed from the plugin — the same
	 * unchanged call site still reaches Foundation.
	 *
	 * @since $ver$
	 */
	public function testCallSitesKeepWorkingAfterTheLocalClientIsDeleted(): void {
		FakeFoundation::$service = new FakeFoundationService();

		Analytics::capture( 'export_completed', [ 'file_format' => 'xlsx' ] );

		self::assertSame(
			[ [ 'export_completed', [ 'file_format' => 'xlsx' ] ] ],
			FakeFoundationService::$captured
		);
	}

	/**
	 * A Foundation copy that does not answer the expected shape must be ignored
	 * rather than called. Foundation is Strauss-prefixed per product, so the
	 * only safe check on what the alias returns is method_exists().
	 *
	 * @since $ver$
	 */
	public function testIgnoresAFoundationServiceOfTheWrongShape(): void {
		FakeFoundation::$service = new \stdClass();

		$client = $this->createMock( Client::class );
		$client->expects( self::once() )->method( 'capture' );

		Analytics::setLocalClient( $client );
		Analytics::capture( 'export_completed' );

		self::assertFalse( Analytics::isDelegating() );
	}

	/**
	 * Resolution must not be cached on a miss. Foundation builds its components
	 * at init priority 100, so a capture earlier in the request would otherwise
	 * bind this request to the local client for good.
	 *
	 * @since $ver$
	 */
	public function testAMissIsNotCached(): void {
		$client = $this->createMock( Client::class );
		Analytics::setLocalClient( $client );

		self::assertFalse( Analytics::isDelegating() );

		FakeFoundation::$service = new FakeFoundationService();

		self::assertTrue( Analytics::isDelegating(), 'A later Foundation boot must still win.' );
	}

	/**
	 * @since $ver$
	 */
	public function testOptOutReachesTheBackend(): void {
		$client = $this->createMock( Client::class );
		$client->expects( self::once() )->method( 'opt_out' );

		Analytics::setLocalClient( $client );
		Analytics::optOut();
	}
}

/**
 * Stands in for Foundation's unprefixed alias.
 *
 * @since $ver$
 */
class FakeFoundation {
	/**
	 * @since $ver$
	 * @var object|null
	 */
	public static $service;

	/**
	 * @since $ver$
	 *
	 * @return object|null The analytics service.
	 */
	public static function analytics() {
		return self::$service;
	}
}

/**
 * Stands in for Foundation's analytics service.
 *
 * @since $ver$
 */
class FakeFoundationService {
	/**
	 * @since $ver$
	 * @var array[]
	 */
	public static $captured = [];

	/**
	 * @since $ver$
	 *
	 * @param string $event The event name.
	 * @param array  $props The properties.
	 *
	 * @return void
	 */
	public function capture( string $event, array $props = [] ): void {
		self::$captured[] = [ $event, $props ];
	}

	/**
	 * @since $ver$
	 *
	 * @return bool Whether enabled.
	 */
	public function is_enabled(): bool {
		return true;
	}
}

class_alias( FakeFoundation::class, 'GravityKitFoundation' );
