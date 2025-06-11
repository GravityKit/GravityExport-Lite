<?php

namespace GFExcel\Addon;

/**
 * A trait to satisfy {@see AddonInterface}.
 * @since $ver$
 */
trait AddonTrait
{
    /**
     * The addon instance.
     * @since 1.0.0
     * @var AddonInterface
     */
    private static $_instance;

    /**
     * The assets directory for this plugin.
     * @since $ver$
     * @var string|null
     */
    private $assets_dir;

    /**
     * @inheritdoc
     * @since $ver$
     */
    public static function set_instance(AddonInterface $addon): void
    {
        if (!is_a($addon, self::class)) {
            throw new \InvalidArgumentException(
                sprintf('Add-on instance must be of type "%s".', self::class)
            );
        }

        self::$_instance = $addon;
    }

    /**
     * @inheritdoc
     * @since $ver$
     * @return static
     */
    public static function get_instance(): AddonInterface
    {
        if (self::$_instance === null) {
            throw new \RuntimeException(sprintf(
                'No instance of "%s" provided.',
                self::class
            ));
        }

        return self::$_instance;
    }

    /**
     * @inheritdoc
     * @since $ver$
     */
    public function setAssetsDir(string $assets_dir): void
    {
        $this->assets_dir = $assets_dir;
    }
}
