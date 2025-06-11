<?php

namespace GFExcel\Container;

/**
 * The container contract.
 * @since $ver$
 */
interface ContainerInterface
{
    /**
     * Registers a service provider which registers services with the container.
     * @since $ver$
     * @return static
     */
    public function addServiceProvider(ServiceProviderInterface $provider) : self;

    /**
     * Returns the service by the service ID.
     * @since $ver$
     * @param string $id The service ID.
     * @return mixed|null The service or `null`.
     */
    public function get(string $id);

    /**
     * Whether the container holds the provided service ID.
     * @since $ver$
     * @param string $id The service ID.
     * @return bool
     */
    public function has(string $id) : bool;
}
