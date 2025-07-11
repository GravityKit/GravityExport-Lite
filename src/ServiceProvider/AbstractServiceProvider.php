<?php

namespace GFExcel\ServiceProvider;

use GFExcel\Action\ActionAwareInterface;
use GFExcel\Container\ServiceProviderInterface;
use League\Container\Container;
use League\Container\Definition\DefinitionInterface;
use League\Container\ServiceProvider\AbstractServiceProvider as LeagueAbstractServiceProviderAlias;

/**
 * Abstract service provider that provides helper methods.
 * @since 2.4.0
 */
abstract class AbstractServiceProvider extends LeagueAbstractServiceProviderAlias implements
    ServiceProviderInterface
{
    /**
     * List of classes the service provider provides.
     * @since 2.4.0
     */
    protected $provides = [];

    /**
     * Helper method to quickly add an action.
     * @since 2.4.0
     * @param string $id The id of the definition.
     * @param mixed $concrete The concrete implementation.
     * @param bool|null $shared Whether this is a shared instance.
     * @return DefinitionInterface The definition.
     */
    protected function addAction(string $id, $concrete = null, ?bool $shared = null) : DefinitionInterface
    {
        $container = $this->getContainer();
	    if ( ! $container instanceof Container ) {
		    throw new \InvalidArgumentException( 'Wrong container type provided.' );
	    }

        $definition = $container->add($id, $concrete, $shared);

        return $definition->addTag(ActionAwareInterface::ACTION_TAG);
    }

    /**
     * Whether this service provide provides the requested service id.
     * @since 2.4.0
     */
    public function provides(string $id) : bool
    {
        return in_array($id, $this->provides, true);
    }

    /**
     * Backwards compatability for plugins.
     * @since 2.4.0
     * @return Container
     * @deprecated Use getContainer instead.
     */
    public function getLeagueContainer() : Container {
	    $container = $this->getContainer();
	    if ( ! $container instanceof Container ) {
		    throw new \InvalidArgumentException( 'Wrong container type provided.' );
	    }

	    return $container;
    }

    /**
     * Method will be invoked on registration of a service provider implementing
     * this interface. Provides ability for eager loading of Service Providers.
     *
     * @return void
     */
    public function boot() : void
    {
    }
}
