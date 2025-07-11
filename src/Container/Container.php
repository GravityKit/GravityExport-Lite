<?php

namespace GFExcel\Container;

use League\Container\Container as LeagueContainer;
use League\Container\ReflectionContainer;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Container implementation backed by the League Container package.
 * @since $ver$
 */
final class Container implements ContainerInterface
{
    /**
     * The league container class.
     * @since $ver$
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
     * @since $ver$
     */
    public function addServiceProvider(ServiceProviderInterface $provider) : ContainerInterface
    {
        $this->container->addServiceProvider($provider);

        return $this;
    }

    /**
     * @inheritDoc
     * @since $ver$
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
     * @since $ver$
     */
    public function has(string $id) : bool
    {
        return $this->container->has($id);
    }
}
