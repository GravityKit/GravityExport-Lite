<?php

namespace GFExcel\ServiceProvider;

use GFExcel\Action\ActionAwareInterface;
use GFExcel\Action\ActionInterface;
use GFExcel\Generator\HashGenerator;
use GFExcel\Generator\HashGeneratorInterface;
use GFExcel\Routing\Router;
use GFExcel\Template\TemplateAwareInterface;
use GFExcel\Repository\FormRepository;
use GFExcel\Repository\FormRepositoryInterface;
use League\Container\Container;

/**
 * The service provider for the base of GFExcel.
 * @since 2.4.0
 */
class BaseServiceProvider extends AbstractServiceProvider {
	/**
	 * {@inheritdoc}
	 * @since 2.4.0
	 */
	protected $provides = [
		FormRepositoryInterface::class,
		HashGeneratorInterface::class,
	];

	/**
	 * {@inheritdoc}
	 * @since 2.4.0
	 */
	public function register(): void {
		$container = $this->getContainer();

		if ( ! $container instanceof Container ) {
			return;
		}

		$container->add(
			FormRepositoryInterface::class,
			FormRepository::class
		)
		          ->addArgument( \GFAPI::class )
		          ->addArgument( Router::class );

		$container->add( HashGeneratorInterface::class, HashGenerator::class );
	}

	/**
	 * Retrieve all tagged actions from the container.
	 * @since 2.4.0
	 * @return ActionInterface[] The actions.
	 */
	protected function getActions(): array {
		$container = $this->getContainer();
		if ( ! $container->has( ActionAwareInterface::ACTION_TAG ) ) {
			return [];
		}

		return $container->get( ActionAwareInterface::ACTION_TAG );
	}

	/**
	 * @inheritdoc
	 * @since 2.4.0
	 */
	public function boot(): void {
		$container = $this->getContainer();
		if ( ! $container instanceof Container ) {
			return;
		}

		$container
			->inflector( ActionAwareInterface::class, function ( ActionAwareInterface $instance ) {
				$instance->setActions( $this->getActions() );
			} );
		$container
			->inflector( TemplateAwareInterface::class )
			->invokeMethod( 'addTemplateFolder', [ dirname( GFEXCEL_PLUGIN_FILE ) . '/templates/' ] );
	}
}
