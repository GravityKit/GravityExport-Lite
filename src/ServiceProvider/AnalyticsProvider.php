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
use GFExcel\Analytics\SiteScan;
use GFExcel\Analytics\SuperProps;
use GFExcel\Container\ContainerInterface;
use GFExcel\Upsell\DownloadMilestone;
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
		SiteScan::class,
		ConsentCard::class,
		DownloadCompletedListener::class,
		DownloadMilestone::class,
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

		$container->add( SiteScan::class )
		          ->addArgument( Consent::class )
		          ->setShared( true );

		$container->add( SuperProps::class )
		          ->addArgument( SiteIdentity::class )
		          ->addArgument( SiteScan::class )
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
		$container->add( DownloadMilestone::class )->setShared( true );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since $ver$
	 */
	public function boot(): void {
		// Resolution is deferred to `gfexcel_container_loaded`, which fires after every
		// provider has been added. League's ServiceProviderAggregate::add() calls boot()
		// BEFORE appending the provider, so resolving anything here finds no definitions
		// and falls through to the reflection container: the Client would capture into an
		// auto-wired Queue while shutdown flushed a different, shared one, and nothing
		// would ever be sent.
		add_action( 'gfexcel_container_loaded', [ $this, 'wire' ] );
	}

	/**
	 * Resolves the analytics services and attaches them to the request.
	 *
	 * @since $ver$
	 *
	 * @param ContainerInterface $container The container.
	 *
	 * @return void
	 */
	public function wire( ContainerInterface $container ): void {
		$client = $container->get( Client::class );

		if ( ! $client instanceof Client ) {
			return;
		}

		Analytics::setLocalClient( $client );

		// Instantiating registers the hooks; these constructors are hook-only.
		$container->get( DownloadCompletedListener::class );

		// Deliberately outside the consent gate and outside the analytics
		// lifecycle: it reads a local counter, sends nothing, and must keep
		// working for the installs that decline telemetry and after the
		// analytics client is deleted at handover.
		$container->get( DownloadMilestone::class );

		if ( is_admin() ) {
			$container->get( ConsentCard::class );

			// Admin-side only: the scan runs three aggregate queries and no
			// visitor should ever pay for our measurement.
			add_action( 'admin_init', [ $container->get( SiteScan::class ), 'maybeRefresh' ] );
		}

		// Flush after the listener has had its shutdown turn, which runs at 10.
		add_action( 'shutdown', static function () use ( $container ): void {
			$container->get( Queue::class )->flush();
			$container->get( Allowlist::class )->flush();
		}, 20 );
	}
}

