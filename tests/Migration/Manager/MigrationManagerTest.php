<?php

namespace GFExcel\Tests\Migration\Manager;

use GFExcel\Migration\Manager\MigrationManager;
use GFExcel\Migration\Migration\Migration;
use GFExcel\Migration\Repository\MigrationRepositoryInterface;
use GFExcel\Notification\Manager\NotificationManager;
use GFExcel\Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for {@see MigrationManager}.
 * @since 1.8.0
 */
class MigrationManagerTest extends TestCase {
	/**
	 * A mocked notification manager instance.
	 * @since 1.9.0
	 * @var MockObject|NotificationManager
	 */
	private $notification_manager;

	/**
	 * The manager under test.
	 * @since 1.8.0
	 * @var MigrationManager
	 */
	private $manager;

	/**
	 * Mocked repository instance.
	 * @since 2.0.0
	 * @var MigrationRepositoryInterface|MockObject
	 */
	private $repository;

	/**
	 * @inheritdoc
	 * @since 1.8.0
	 */
	public function setUp(): void {
		parent::setUp();

		$this->notification_manager = $this->createMock( NotificationManager::class );
		$this->repository           = $this->createMock( MigrationRepositoryInterface::class );
		$this->manager              = new MigrationManager( $this->notification_manager, $this->repository );
	}

	/**
	 * Test case for {@see MigrationManager::setMigrations()}.
	 * @since 1.8.0
	 */
	public function testSetMigrations(): void {
		$this->repository
			->expects( self::once() )
			->method( 'getLatestVersion' )
			->willReturn( '1.0.0' );

		$this->manager->setMigrations( [
			\stdClass::class, // skipped because of type
			Test_Migration_1_0_0::class, // skipped because of the current version
			Test_Migration_10_0_0::class,
			Test_Migration_2_0_0::class,
			Test_Migration_1_1_0::class,
			Test_Migration_2_0_0::class,
		] );

		// check to see if the order is correct.
		$this->assertSame(
			[ '1.1.0', '2.0.0', '2.0.0', '10.0.0' ],
			array_map( static function ( Migration $migration ): string {
				return $migration::getVersion();
			}, $this->manager->getMigrations() )
		);
	}

	/**
	 * Test case for {@see MigrationManager::getMigrations()}.
	 * @since 1.8.0
	 */
	public function testGetMigrations(): void {
		$this->assertSame( [], $this->manager->getMigrations() );
	}

	/**
	 * Test case for {@see MigrationManager::getNotificationManager()}.v
	 * @since 1.9.0
	 */
	public function testGetNotificationManager(): void {
		$this->assertSame( $this->notification_manager, $this->manager->getNotificationManager() );
	}

	/**
	 * Test case for {@see MigrationManager::migrate()}.
	 * @since 1.8.0
	 */
	public function testMigrate(): void {
		$this->repository
			->expects( self::once() )
			->method( 'getLatestVersion' )
			->willReturn( '1.0.0' );

		$this->repository
			->expects( self::once() )
			->method( 'isRunning' )
			->willReturn( false );

		$this->repository
			->expects( self::once() )
			->method( 'getMigrations' )
			->willReturn( [
				Test_Migration_10_0_0::class,
				Test_Migration_1_1_0::class,
				Test_Migration_2_0_0::class,
			] );

		$this->manager->migrate();

		$migrations = $this->manager->getMigrations();
		self::assertCount( 3, $migrations );

		foreach ( $migrations as $migration ) {
			self::assertTrue( $migration->ran );
			self::assertSame( $this->manager, $migration->getManager() );
		}
	}
}

class Test_Migration_1_0_0 extends Migration {
	protected static $version = '1.0.0';

	public $ran = false;

	public function getManager(): MigrationManager {
		return $this->manager;
	}

	public function run(): void {
		$this->ran = true;
	}
}

class Test_Migration_1_1_0 extends Test_Migration_1_0_0 {
	protected static $version = '1.1.0';
}

class Test_Migration_10_0_0 extends Test_Migration_1_0_0 {
	protected static $version = '10.0.0';
}

class Test_Migration_2_0_0 extends Test_Migration_1_0_0 {
	protected static $version = '2.0.0';
}
