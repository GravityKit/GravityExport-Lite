<?php

namespace GFExcel\Plugin;

use GFExcel\Addon\AddonInterface;
use GFExcel\Container\ContainerInterface;

/**
 * A base for a plugin to extend from.
 * @since 2.4.0
 */
abstract class BasePlugin
{
    /**
     * The {@see AddonInterface} classes.
     * @since 2.4.0
     * @var string[]
     */
    protected $addons = [];

    /**
     * The container instance.
     * @since 2.4.0
     * @var ContainerInterface
     */
    protected $container;

    /**
     * The assets directory for this plugin.
     * @since 2.4.0
     * @var string|null
     */
    private $assets_dir;

    /**
     * Creates the plugin.
     * @param ContainerInterface $container The service container.
     * @param string|null $assets_dir The assets directory.
     */
    public function __construct(ContainerInterface $container, string $assets_dir = null)
    {
        $this->container = $container;
        $this->assets_dir = $assets_dir;
    }

    /**
     * Register the available add-ons.
     * @since 2.4.0
     */
    public function registerAddOns(): self
    {
        foreach ($this->addons as $addon) {
            if (!$this->container->has($addon)) {
                throw new \RuntimeException('This add-on does not exist.');
            }

            $instance = $this->container->get($addon);
            if (!$instance instanceof AddonInterface) {
                throw new \RuntimeException(
                    sprintf('This add-on does not implement the "%s" interface.', AddonInterface::class)
                );
            }

            $instance::set_instance($instance);
            \GFAddOn::register($addon);

            if ($this->assets_dir) {
                $instance->setAssetsDir($this->assets_dir);
            }
        }

        return $this;
    }
}
