<?php

namespace GFExcel\Migration\Migration;

use GFExcel\Migration\Exception\MigrationException;
use GFExcel\Migration\Manager\MigrationManager;

/**
 * A base migration.
 * @since 1.8.0
 */
abstract class Migration
{
    /**
     * The migration manager.
     * @since 1.9.0
     * @var null|MigrationManager $manager
     */
    protected $manager;

    /**
     * The version for this migration.
     * @since 1.8.0
     * @var string
     */
    protected static $version = '0.0.0';

    /**
     * The version
     * @since 1.8.0
     * @return string The version
     */
    public static function getVersion(): string
    {
        return static::$version;
    }

    /**
     * Runs the migration.
     * @since 1.8.0
     * @throws MigrationException when something went wrong during the migration.
     */
    abstract public function run(): void;

    /**
     * Sets the migration manager on the migration.
     * @since 1.9.0
     * @param MigrationManager $manager The migration manager.
     */
    public function setManager(MigrationManager $manager): void
    {
        $this->manager = $manager;
    }
}
