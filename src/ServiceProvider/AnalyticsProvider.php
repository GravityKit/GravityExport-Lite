<?php

namespace GFExcel\ServiceProvider;

use GFExcel\Analytics\Allowlist;
use GFExcel\Analytics\Analytics;
use GFExcel\Analytics\Client;
use GFExcel\Analytics\Consent;
use GFExcel\Analytics\ConsentCard;
use GFExcel\Analytics\DownloadCompletedListener;
use GFExcel\Analytics\Queue;
use GFExcel\Analytics\Scrub;
use GFExcel\Analytics\SiteIdentity;
use GFExcel\Analytics\SuperProps;
use League\Container\Container;

/**
 * Wires the analytics stack.
 *
 * Everything registered here except the facade and the emitters is deleted
 * when Foundation reaches wordpress.org and takes over. The facade resolves
 * Foundation on its own, so at that point this provider shrinks to registering
 * the listener and the card.
 *
 * @since $ver$
 */
class AnalyticsProvider extends AbstractServiceProvider {
	/**
	 * {@inheritdoc}
	 *
	 * @since $ver$
	 */
	protected $provides = [
		Client::class,
		Consent::class,
		Queue::class,
		SiteIdentity::class,
		ConsentCard::class,
		DownloadCompletedListener::class,
	];

	/**
	 * {@inheritdoc}
	 *
	 * @since $ver$
	 */
	public function register(): void {
		$container = $this->getContainer();

		if ( ! $container instanceof Container ) {
			return;
		}

		$container->add( SiteIdentity::class )->setShared( true );
		$container->add( Consent::class )->setShared( true );
		$container->add( Allowlist::class )->setShared( true );
		$container->add( Scrub::class )->setShared( true );
		$container->add( Queue::class )->setShared( true );

		$container->add( SuperProps::class )
		          ->addArgument( SiteIdentity::class )
		          ->setShared( true );

		$container->add( Client::class )
		          ->addArgument( Consent::class )
		          ->addArgument( Allowlist::class )
		          ->addArgument( Scrub::class )
		          ->addArgument( SuperProps::class )
		          ->addArgument( Queue::class )
		          ->setShared( true );

		$container->add( ConsentCard::class )
		          ->addArgument( Consent::class )
		          ->setShared( true );

		$container->add( DownloadCompletedListener::class )->setShared( true );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since $ver$
	 */
	public function boot(): void {
		$container = $this->getContainer();

		if ( ! $container instanceof Container ) {
			return;
		}

		Analytics::setLocalClient( $container->get( Client::class ) );

		// Instantiating registers the hooks; both constructors are hook-only.
		$container->get( DownloadCompletedListener::class );

		if ( is_admin() ) {
			$container->get( ConsentCard::class );
		}

		// Flush after the listener has had its shutdown turn (it runs at 10).
		add_action( 'shutdown', function () use ( $container ): void {
			$container->get( Queue::class )->flush();
			$container->get( Allowlist::class )->flush();
		}, 20 );
	}
}
