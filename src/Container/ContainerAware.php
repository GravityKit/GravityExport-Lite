<?php

namespace GFExcel\Container;

/**
 * Trait that makes a class container aware.
 * @since 2.4.0
 */
trait ContainerAware
{
    /**
     * Holds the container instance.
     * @since 2.4.0
     * @var ContainerInterface
     */
    private $container;

    /**
     * Sets the container instance for a class.
     * @since 2.4.0
     * @param ContainerInterface $container The container instance
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Get the container instance for this class.
     * @since 2.4.0
     * @return ContainerInterface|null The container instance.
     */
    public function getContainer(): ?ContainerInterface
    {
        return $this->container;
    }
}
