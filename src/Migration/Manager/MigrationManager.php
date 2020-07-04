<?php

namespace GFExcel\Migration\Manager;

use GFExcel\Migration\Exception\MigrationException;
use GFExcel\Migration\Migration;

/**
 * The migration manager.
 * @since $ver$
 */
class MigrationManager
{
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
     * The migrations to run.
     * @since $ver$
     * @var Migration[]|null
     */
    private $migrations;

    /**
     * Creates the manager.
     * @since $ver$
     */
    public function __construct()
    {
        add_action('upgrader_process_complete', [$this, 'migrate']);
    }

    /**
     *
     * @since $ver$
     * @throws MigrationException When something went wrong in a migration.
     */
    public function migrate(): void
    {
        // Prevent concurrent running of migrations.
        if (!get_transient(self::TRANSIENT_MIGRATION_RUNNING)) {
            set_transient(self::TRANSIENT_MIGRATION_RUNNING, true, 300);

            // Change directory for glob. We do this here so we can better test `getMigrations`.
            chdir(dirname(GFEXCEL_PLUGIN_FILE) . '/src/Migration/');

            // Run migrations.
            foreach ($this->getMigrations() as $migration) {
                $migration->run();

                // Update version.
                update_option(self::OPTION_MIGRATION_VERSION, $migration::getVersion());
            }

            // Clear running status.
            delete_transient(self::TRANSIENT_MIGRATION_RUNNING);
        }
    }

    /**
     * Sets the migrations on the manager.
     * @since $ver$
     * @param Migration[] $migrations Migrations.
     */
    public function setMigrations(array $migrations): void
    {
        $this->migrations = array_filter($migrations, function ($migration) {
            return $migration instanceof Migration &&
                version_compare($migration::getVersion(), $this->get_latest_version(), '>');
        });

        // sort migrations based on the version.
        usort($this->migrations, static function (Migration $migration_a, Migration $migration_b): int {
            if ($migration_a::getVersion() === $migration_b::getVersion()) {
                return 0;
            }

            return version_compare($migration_a::getVersion(), $migration_b::getVersion(), '<') ? -1 : 1;
        });
    }

    /**
     * Returns the migrations to run based on the current version.
     * @since $ver$
     * @return Migration[] The migrations.
     */
    public function getMigrations(): array
    {
        if ($this->migrations === null) {
            // Retrieve migrations from folder.
            $migrations = array_reduce(
                glob('*.php') ?: [],
                static function (array $migrations, string $filename): array {
                    $filename = str_replace(['../', '.php'], '', $filename);
                    $classname = sprintf('GFExcel\\Migration\\%s', $filename);
                    if ($classname === Migration::class || !class_exists($classname)) {
                        return $migrations;
                    }

                    $migrations[] = new $classname();

                    return $migrations;
                },
                []
            );

            $this->setMigrations($migrations);
        }

        return $this->migrations;
    }

    /**
     * Retrieve the latest version.
     * @since $ver$
     * @return string
     */
    private function get_latest_version(): string
    {
        return get_option(self::OPTION_MIGRATION_VERSION, '0.0.0');
    }
}
