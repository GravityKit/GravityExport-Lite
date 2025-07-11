<?php

namespace GFExcel\Container;

/**
 * The container contract.
 * @since 2.4.0
 */
interface ContainerInterface
{
    /**
     * Registers a service provider which registers services with the container.
     * @since 2.4.0
     * @return static
     */
    public function addServiceProvider(ServiceProviderInterface $provider) : self;

    /**
     * Returns the service by the service ID.
     * @since 2.4.0
     * @param string $id The service ID.
     * @return mixed|null The service or `null`.
     */
    public function get(string $id);

    /**
     * Whether the container holds the provided service ID.
     * @since 2.4.0
     * @param string $id The service ID.
     * @return bool
     */
    public function has(string $id) : bool;
}
