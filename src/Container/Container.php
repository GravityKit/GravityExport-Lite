<?php

namespace GFExcel\Container;

use League\Container\Container as LeagueContainer;
use League\Container\ReflectionContainer;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Container implementation backed by the League Container package.
 * @since 2.4.0
 */
final class Container implements ContainerInterface
{
    /**
     * The league container class.
     * @since 2.4.0
     * @var LeagueContainer
     */
    private $container;

    public function __construct()
    {
        $this->container = new LeagueContainer();
        $this->container
            ->defaultToShared()
            ->delegate(new ReflectionContainer());
    }

    /**
     * @inheritDoc
     * @since 2.4.0
     */
    public function addServiceProvider(ServiceProviderInterface $provider) : ContainerInterface
    {
        $this->container->addServiceProvider($provider);

        return $this;
    }

    /**
     * @inheritDoc
     * @since 2.4.0
     */
    public function get(string $id)
    {
        try {
            return $this->container->get($id);
        } catch (NotFoundExceptionInterface|ContainerExceptionInterface $e) {
            return null;
        }
    }

    /**
     * @inheritDoc
     * @since 2.4.0
     */
    public function has(string $id) : bool
    {
        return $this->container->has($id);
    }
}
