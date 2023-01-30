<?php

namespace GFExcel\Migration\Repository;

use GFExcel\Migration\Migration\Migration;

/**
 * Migration repository backed by migrations from the migration folder.
 * @since $ver$
 */
final class FileSystemMigrationRepository implements MigrationRepositoryInterface {
	/**
	 * The option key of the migration version.
	 * @since $ver$
	 * @var string
	 */
	public const OPTION_MIGRATION_VERSION = 'gfexcel_migration_version';

	/**
	 * The transient key holding the migration running status.
	 * @since $ver$
	 * @var string
	 */
	public const TRANSIENT_MIGRATION_RUNNING = 'gfexcel_migration_running';

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function getMigrations(): array {
		// Change directory for glob. We do this here, so we can better test `getMigrations()`.
		chdir( dirname( GFEXCEL_PLUGIN_FILE ) . '/src/Migration/Migration/' );

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
	 * @since $ver$
	 */
	public function isRunning(): bool {
		return (bool) get_transient( self::TRANSIENT_MIGRATION_RUNNING );
	}

	/**
	 * @inheritDoc
	 *
	 * When true, we keep a fail-safe of 5 minutes in case the migrations fail.
	 *
	 * @since $ver$
	 */
	public function setRunning( bool $is_running ): void {
		$is_running
			? set_transient( self::TRANSIENT_MIGRATION_RUNNING, true, 300 )
			: delete_transient( self::TRANSIENT_MIGRATION_RUNNING );
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function getLatestVersion(): string {
		return get_option( self::OPTION_MIGRATION_VERSION, '0.0.0' ) ?: '0.0.0';
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function setLatestVersion( string $version ): void {
		if ( version_compare( $version, $this->getLatestVersion(), '>' ) ) {
			update_option( self::OPTION_MIGRATION_VERSION, $version );
		}
	}
}
