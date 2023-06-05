<?php

namespace GFExcel\Migration\Manager;

use GFExcel\Addon\GravityExportAddon;
use GFExcel\Migration\Exception\MigrationException;
use GFExcel\Migration\Exception\NonBreakingMigrationException;
use GFExcel\Migration\Migration\Migration;
use GFExcel\Migration\Repository\MigrationRepositoryInterface;
use GFExcel\Notification\Manager\NotificationManager;

/**
 * The migration manager.
 * @since 1.8.0
 */
class MigrationManager {
	/**
	 * The migrations to run.
	 * @since 1.8.0
	 * @var Migration[]|null
	 */
	private $migrations;

	/**
	 * The notification manager.
	 * @since 1.9.0
	 * @var NotificationManager
	 */
	private $notification_manager;

	/**
	 * The migration repository.
	 * @since 2.0.0
	 * @var MigrationRepositoryInterface
	 */
	private $repository;

	/**
	 * Creates the manager.
	 * @since 1.8.0
	 *
	 * @param NotificationManager $notification_manager The notification manager.
	 * @param MigrationRepositoryInterface $repository The migration repository.
	 */
	public function __construct( NotificationManager $notification_manager, MigrationRepositoryInterface $repository ) {
		$this->notification_manager = $notification_manager;
		$this->repository           = $repository;

		add_action( 'admin_init', \Closure::fromCallable( [ $this, 'maybe_migrate' ] ) );
	}

	/**
	 * Returns the notification manager.
	 * @since 1.9.0
	 * @return NotificationManager The notification manager.
	 */
	public function getNotificationManager(): NotificationManager {
		return $this->notification_manager;
	}


	/**
	 * Entry point for possibly starting migrations.
	 * @since 2.0.0
	 */
	private function maybe_migrate(): void {
		if ( ! $this->repository->shouldMigrate() ) {
			return;
		}

		try {
			$this->migrate();
		} catch ( MigrationException $e ) {
			GravityExportAddon::get_instance()->log_error( sprintf( 'Migration error: %s', $e->getMessage() ) );
		}
	}

	/**
	 * @since 1.8.0
	 * @throws MigrationException When something went wrong in a migration.
	 */
	public function migrate(): void {
		// Prevent concurrent running of migrations.
		if ( ! $this->repository->isRunning() ) {
			$this->repository->setRunning( true );

			// Run migrations.
			foreach ( $this->getMigrations() as $migration ) {
				try {
					$migration->run();
				} catch ( NonBreakingMigrationException $e ) {
					// Log the exception, but keep migrating.
					GravityExportAddon::get_instance()->log_error( sprintf( 'Non breaking migration error: %s', $e->getMessage() ) );
				}
			}

			// Update version.
			$this->repository->setLatestVersion( GFEXCEL_PLUGIN_VERSION );
		}

		// Clear running status.
		$this->repository->setRunning( false );
	}

	/**
	 * Sets the migrations on the manager.
	 * @since 1.8.0
	 *
	 * @param string[] $migrations Migration classes.
	 */
	public function setMigrations( array $migrations ): void {
		$this->migrations = [];
		$latest_version   = $this->repository->getLatestVersion();

		foreach ( $migrations as $migration ) {
			if (
				! is_subclass_of( $migration, Migration::class, true )
				|| ! version_compare( $migration::getVersion(), $latest_version, '>' )
			) {
				// Skip any migrations that already ran, based on their version number.
				continue;
			}

			$this->migrations[] = $instance = new $migration;
			// Inject manager.
			$instance->setManager( $this );
		}

		// sort migrations based on the version.
		usort( $this->migrations, static function ( Migration $migration_a, Migration $migration_b ): int {
			return version_compare( $migration_a::getVersion(), $migration_b::getVersion() );
		} );
	}

	/**
	 * Returns the migrations to run based on the current version.
	 * @since 1.8.0
	 * @return Migration[] The migrations.
	 */
	public function getMigrations(): array {
		if ( $this->migrations === null ) {
			$migrations = $this->repository->getMigrations();

			$this->setMigrations( $migrations );
		}

		return $this->migrations;
	}
}
