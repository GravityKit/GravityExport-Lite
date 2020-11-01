<?php

namespace GFExcel\Addon;

/**
 * Interface that represents a GFExcel add-on
 * @since $ver$
 */
interface AddonInterface
{
    /**
     * Internal method to set the instance.
     * @since $ver$
     * @param AddonInterface $addon The single add-on instance.
     * @throws \RuntimeException When the wrong instance type was set.
     */
    public static function set_instance(AddonInterface $addon): void;

    /**
     * Retrieve the current instance.
     * @since $ver$
     * @return AddonInterface The current instance of this add on.
     */
    public static function get_instance(): AddonInterface;
}