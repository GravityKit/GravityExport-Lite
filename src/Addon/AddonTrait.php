<?php

namespace GFExcel\Addon;

/**
 * A trait to satisfy {@see AddonInterface}.
 * @since 2.4.0
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
     * @since 2.4.0
     * @var string|null
     */
    private $assets_dir;

    /**
     * @inheritdoc
     * @since 2.4.0
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
     * @since 2.4.0
     * @return static
     */
    public static function get_instance(): AddonInterface
    {
	    if ( ! self::$_instance instanceof static ) {
		    throw new \RuntimeException( sprintf(
			    'No instance of "%s" provided.',
			    self::class
		    ) );
	    }

        return self::$_instance;
    }

    /**
     * @inheritdoc
     * @since 2.4.0
     */
    public function setAssetsDir(string $assets_dir): void
    {
        $this->assets_dir = $assets_dir;
    }
}
