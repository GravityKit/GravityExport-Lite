<?php

namespace GFExcel\Tests\ServiceProvider;

use GFExcel\Analytics\Analytics;
use GFExcel\Analytics\Client;
use GFExcel\Analytics\Queue;
use GFExcel\Container\Container;
use GFExcel\ServiceProvider\AnalyticsProvider;
use GFExcel\Tests\TestCase;

/**
 * Unit tests for {@see AnalyticsProvider}.
 *
 * These exist because of a defect no other test could see. League's
 * ServiceProviderAggregate::add() calls boot() BEFORE appending the provider,
 * so anything resolved inside boot() misses the provider's own definitions and
 * falls through to the reflection container. The Client then captured events
 * into an auto-wired Queue while shutdown flushed a different, shared one, and
 * nothing was ever transmitted. Every unit test still passed.
 *
 * @since $ver$
 */
class AnalyticsProviderTest extends TestCase {
	/**
	 * @inheritdoc
	 * @since $ver$
	 */
	public function setUp(): void {
		parent::setUp();

		Analytics::reset();

		\WP_Mock::userFunction( 'is_admin' )->andReturn( false );
	}

	/**
	 * @inheritdoc
	 * @since $ver$
	 */
	public function tearDown(): void {
		Analytics::reset();

		parent::tearDown();
	}

	/**
	 * boot() must only register a hook. Resolving anything there is the defect.
	 *
	 * @since $ver$
	 */
	public function testBootResolvesNothing(): void {
		$container = ( new Container() )->addServiceProvider( new AnalyticsProvider() );

		$local = new \ReflectionProperty( Analytics::class, 'local' );
		$local->setAccessible( true );

		self::assertNull(
			$local->getValue(),
			'boot() resolved the client; it must defer until the provider is registered.'
		);

		// The complement: the provider IS usable, it simply has not been used yet.
		self::assertInstanceOf( Client::class, $container->get( Client::class ) );
	}

	/**
	 * The invariant that was broken: the Queue the Client captures into must be
	 * the same object the shutdown flush resolves, or events are silently lost.
	 *
	 * @since $ver$
	 */
	public function testWireGivesTheClientTheSameQueueTheFlushResolves(): void {
		$container = ( new Container() )->addServiceProvider( new AnalyticsProvider() );

		( new AnalyticsProvider() )->wire( $container );

		$local = new \ReflectionProperty( Analytics::class, 'local' );
		$local->setAccessible( true );
		$client = $local->getValue();

		self::assertInstanceOf( Client::class, $client, 'wire() did not register a local client.' );

		$queue = new \ReflectionProperty( Client::class, 'queue' );
		$queue->setAccessible( true );

		self::assertSame(
			$container->get( Queue::class ),
			$queue->getValue( $client ),
			'The Client captures into a different Queue than the one shutdown flushes.'
		);
	}

	/**
	 * @since $ver$
	 */
	public function testContainerResolvesTheClientAsShared(): void {
		$container = ( new Container() )->addServiceProvider( new AnalyticsProvider() );

		self::assertInstanceOf( Client::class, $container->get( Client::class ) );
		self::assertSame( $container->get( Client::class ), $container->get( Client::class ) );
	}
}
