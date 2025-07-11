<?php

namespace GFExcel\Container;

/**
 * Trait that makes a class container aware.
 * @since $ver$
 */
trait ContainerAware
{
    /**
     * Holds the container instance.
     * @since $ver$
     * @var ContainerInterface
     */
    private $container;

    /**
     * Sets the container instance for a class.
     * @since $ver$
     * @param ContainerInterface $container The container instance
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Get the container instance for this class.
     * @since $ver$
     * @return ContainerInterface|null The container instance.
     */
    public function getContainer(): ?ContainerInterface
    {
        return $this->container;
    }
}
