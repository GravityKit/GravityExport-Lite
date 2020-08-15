<?php

namespace GFExcel\Migration;

use GFExcel\Migration\Exception\MigrationException;

/**
 * A base migration.
 * @since 1.8.0
 */
abstract class Migration
{
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
     *
     */
    abstract public function run(): void;
}
