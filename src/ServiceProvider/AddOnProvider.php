<?php

namespace GFExcel\ServiceProvider;

use GFExcel\Action\CountDownloads;
use GFExcel\Action\DownloadUrlDisableAction;
use GFExcel\Action\DownloadUrlEnableAction;
use GFExcel\Action\DownloadUrlResetAction;
use GFExcel\Action\FilterRequest;
use GFExcel\Action\NotificationAttachmentAction;
use GFExcel\Action\NotificationsAction;
use GFExcel\Action\DownloadCountResetAction;
use GFExcel\Component\MetaBoxes;
use GFExcel\Component\Plugin;
use GFExcel\Generator\HashGeneratorInterface;
use GFExcel\Migration\Manager\MigrationManager;
use GFExcel\Migration\Repository\FileSystemMigrationRepository;
use GFExcel\Migration\Repository\MigrationRepositoryInterface;
use GFExcel\Notification\Manager\NotificationManager;
use GFExcel\Notification\Repository\NotificationRepository;
use GFExcel\Notification\Repository\NotificationRepositoryInterface;
use GFExcel\Routing\Router;
use GFExcel\Routing\WordPressRouter;
use GFExcel\Shortcode\DownloadUrl;
use League\Container\Container;
use League\Container\Definition\DefinitionInterface;
use Psr\Container\ContainerInterface;

/**
 * Service provider for the gravity forms add-on.
 * @since 1.9.0
 */
class AddOnProvider extends AbstractServiceProvider {
	/**
	 * The string an automatically started service must be tagged with.
	 * @since 1.9.0
	 * @var string
	 */
	public const AUTOSTART_TAG = 'gfexcel.autostart';

	/**
	 * @inheritdoc
	 * @since 1.9.0
	 */
	protected $provides = [
		self::AUTOSTART_TAG,
		Router::class,
		WordPressRouter::class,
		NotificationRepositoryInterface::class,
		NotificationManager::class,
		CountDownloads::class,
		FilterRequest::class,
		NotificationsAction::class,
		MigrationManager::class,
		NotificationAttachmentAction::class,
	];

	/**
	 * @inheritdoc
	 * @since 1.9.0
	 */
	public function register(): void {
		$container = $this->getContainer();

		$container->add( Router::class, WordPressRouter::class );

		$container->add( MigrationRepositoryInterface::class, FileSystemMigrationRepository::class )
		          ->addArgument( GFEXCEL_SRC_FOLDER . '/Migration/Migration/' );
		$container->add( NotificationRepositoryInterface::class, NotificationRepository::class );
		$container->add( NotificationManager::class )->addArgument( NotificationRepositoryInterface::class );

		$this->addAutoStart( CountDownloads::class );
		$this->addAutoStart( DownloadUrl::class )
		     ->addArgument( Router::class );
		$this->addAutoStart( FilterRequest::class )
		     ->addArgument( Router::class );
		$this->addAutoStart( MetaBoxes::class )
			->addArgument( Router::class );

		$this->addAutoStart( MigrationManager::class )
		     ->addArgument( NotificationManager::class )
		     ->addArgument( MigrationRepositoryInterface::class );

		$this->addAutoStart( NotificationsAction::class )
		     ->addArgument( NotificationManager::class );

		$this->addAutoStart( NotificationAttachmentAction::class );
		$this->addAutoStart( Plugin::class );
	}

	/**
	 * Helper method to quickly add an auto started service.
	 * @since 1.9.0
	 *
	 * @param string $id The id of the definition.
	 * @param mixed $concrete The concrete implementation.
	 * @param bool|null $shared Whether this is a shared instance.
	 *
	 * @return DefinitionInterface The definition.
	 */
	private function addAutoStart( string $id, $concrete = null, ?bool $shared = null ): DefinitionInterface {
		return $this->getContainer()
		            ->add( $id, $concrete, $shared )
		            ->addTag( self::AUTOSTART_TAG );
	}

	/**
	 * @inheritdoc
	 * @since 2.0.0
	 */
	public function boot(): void {
		$this->addAction( DownloadUrlEnableAction::class )->addArgument( HashGeneratorInterface::class );
		$this->addAction( DownloadUrlResetAction::class )->addArgument( HashGeneratorInterface::class );
		$this->addAction( DownloadUrlDisableAction::class );
		$this->addAction( DownloadCountResetAction::class );
	}

	/**
	 * {@inheritDoc}
	 * Overwritten to ensure proper container type.
	 * @since 2.3.2
	 * @return Container
	 */
	public function getContainer(): ContainerInterface {
		if ( ! $this->container instanceof Container ) {
			throw new \InvalidArgumentException( 'Wrong container type provided.' );
		}

		return $this->container;
	}
}
