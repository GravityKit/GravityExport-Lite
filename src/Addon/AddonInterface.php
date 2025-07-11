<?php

namespace GFExcel\Addon;

/**
 * Interface that represents a GFExcel add-on
 * @since 2.4.0
 */
interface AddonInterface
{
    /**
     * Internal method to set the instance.
     * @since 2.4.0
     * @param AddonInterface $addon The single add-on instance.
     * @throws \RuntimeException When the wrong instance type was set.
     */
    public static function set_instance(AddonInterface $addon): void;

    /**
     * Retrieve the current instance.
     * @since 2.4.0
     * @return static The current instance of this add-on.
     */
    public static function get_instance(): AddonInterface;

    /**
     * Sets the assets directory for the current plugin.
     * @since 2.4.0
     * @param string $assets_dir The assets' directory.
     */
    public function setAssetsDir(string $assets_dir): void;
}
