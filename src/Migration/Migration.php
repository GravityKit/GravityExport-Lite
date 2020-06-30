<?php

namespace GFExcel\Migration;

use GFExcel\Migration\Exception\MigrationException;

/**
 * A base migration.
 * @since $ver$
 */
abstract class Migration
{
    /**
     * The version for this migration.
     * @since $ver$
     * @var string
     */
    protected static $version = '0.0.0';

    /**
     * The version
     * @since $ver$
     * @return string The version
     */
    public static function getVersion(): string
    {
        return static::$version;
    }

    /**
     * Runs the migration.
     * @since $ver$
     * @throws MigrationException when something went wrong during the migration.
     *
     */
    abstract public function run(): void;
}
