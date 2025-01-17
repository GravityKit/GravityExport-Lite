<?php

namespace GFExcel\Migration\Repository;

use GFExcel\Migration\Migration\Migration;

/**
 * Migration repository backed by migrations from the migration folder.
 * @since 2.0.0
 */
final class FileSystemMigrationRepository implements MigrationRepositoryInterface {
	/**
	 * The option key of the migration version.
	 * @since 2.0.0
	 * @var string
	 */
	public const OPTION_MIGRATION_VERSION = 'gravityexport_migration_version';

	/**
	 * The transient key holding the migration running status.
	 * @since 2.0.0
	 * @var string
	 */
	public const TRANSIENT_MIGRATION_RUNNING = 'gravityexport_migration_running';

	/**
	 * The absolute path that contains the migrations.
	 * @since 2.0.0
	 * @var string
	 */
	private $migration_path;

	/**
	 * Creates the Repository.
	 * @since 2.0.0
	 */
	public function __construct( string $migration_path ) {
		$this->migration_path = $migration_path;
	}

	/**
	 * @inheritDoc
	 * @since 2.0.0
	 */
	public function shouldMigrate(): bool {
		return version_compare( GFEXCEL_PLUGIN_VERSION, $this->getLatestVersion(), '>' );
	}

	/**
	 * @inheritDoc
	 * @since 2.0.0
	 */
	public function getMigrations(): array {
		if ( ! is_readable( $this->migration_path ) ) {
			return [];
		}

		// Change directory for glob. We do this here, so we can better test `getMigrations()`.
		chdir( $this->migration_path );

		// Retrieve migrations from folder.
		return array_reduce(
			glob( '*.php' ) ?: [],
			static function ( array $migrations, string $filename ): array {
				$filename  = str_replace( [ '../', '.php' ], '', $filename );
				$classname = sprintf( 'GFExcel\\Migration\\Migration\\%s', $filename );
				if ( $classname === Migration::class || ! class_exists( $classname ) ) {
					return $migrations;
				}

				$migrations[] = $classname;

				return $migrations;
			},
			[]
		);
	}

	/**
	 * @inheritDoc
	 * @since 2.0.0
	 */
	public function isRunning(): bool {
		return (bool) get_transient( self::TRANSIENT_MIGRATION_RUNNING );
	}

	/**
	 * @inheritDoc
	 *
	 * When true, we keep a fail-safe of 5 minutes in case the migrations fail.
	 *
	 * @since 2.0.0
	 */
	public function setRunning( bool $is_running ): void {
		$is_running
			? set_transient( self::TRANSIENT_MIGRATION_RUNNING, true, 300 )
			: delete_transient( self::TRANSIENT_MIGRATION_RUNNING );
	}

	/**
	 * @inheritDoc
	 * @since 2.0.0
	 */
	public function getLatestVersion(): string {
		return get_option( self::OPTION_MIGRATION_VERSION, '0.0.0' ) ?: '0.0.0';
	}

	/**
	 * @inheritDoc
	 * @since 2.0.0
	 */
	public function setLatestVersion( string $version ): void {
		if ( version_compare( $version, $this->getLatestVersion(), '>' ) ) {
			update_option( self::OPTION_MIGRATION_VERSION, $version );
		}
	}
}
