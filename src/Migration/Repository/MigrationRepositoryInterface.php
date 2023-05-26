<?php

namespace GFExcel\Migration\Repository;

use GFExcel\Migration\Migration\Migration;

/**
 * Interface a migration repository needs to adhere to.
 * @since 2.0.0
 */
interface MigrationRepositoryInterface {
	/**
	 * Whether potential migrations should run.
	 * @since 2.0.0
	 */
	public function shouldMigrate(): bool;

	/**
	 * Returns the classnames of the migrations to run.
	 *
	 * Classes should extend {@see Migration}
	 *
	 * @since 2.0.0
	 * @return string[]
	 */
	public function getMigrations(): array;

	/**
	 * Returns whether the migrations are currently running.
	 * @since 2.0.0
	 * @return bool
	 */
	public function isRunning(): bool;

	/**
	 * Updates the running state.
	 * @since 2.0.0
	 *
	 * @param bool $is_running Whether the migrations are running.
	 */
	public function setRunning( bool $is_running ): void;

	/**
	 * The latest version for which migrations were run.
	 * @since 2.0.0
	 * @return string
	 */
	public function getLatestVersion(): string;

	/**
	 * Sets the latest version, if it is later than the current latest version.
	 * @since $ve$
	 *
	 * @param string $version The version to set.
	 */
	public function setLatestVersion( string $version ): void;
}
