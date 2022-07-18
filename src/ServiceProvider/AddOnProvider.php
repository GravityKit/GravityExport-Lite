<?php

namespace GFExcel\ServiceProvider;

use GFExcel\Action\CountDownloads;
use GFExcel\Action\FilterRequest;
use GFExcel\Action\NotificationsAction;
use GFExcel\Migration\Manager\MigrationManager;
use GFExcel\Notification\Manager\NotificationManager;
use GFExcel\Notification\Repository\NotificationRepository;
use GFExcel\Notification\Repository\NotificationRepositoryInterface;
use GFExcel\Shorttag\DownloadUrl;
use League\Container\Definition\DefinitionInterface;

/**
 * Service provider for the gravity forms add-on.
 * @since 1.9.0
 */
class AddOnProvider extends AbstractServiceProvider
{
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
        NotificationRepositoryInterface::class,
        NotificationManager::class,
        CountDownloads::class,
        FilterRequest::class,
        NotificationsAction::class,
        MigrationManager::class,
    ];

    /**
     * @inheritdoc
     * @since 1.9.0
     */
    public function register(): void
    {
        $container = $this->getLeagueContainer();

        $container->add(NotificationRepositoryInterface::class, NotificationRepository::class);
        $container->add(NotificationManager::class)->addArgument(NotificationRepositoryInterface::class);

        $this->addAutoStart(CountDownloads::class);
        $this->addAutoStart(DownloadUrl::class);
        $this->addAutoStart(FilterRequest::class);
        $this->addAutoStart(MigrationManager::class)->addArgument(NotificationManager::class);
        $this->addAutoStart(NotificationsAction::class)->addArgument(NotificationManager::class);
    }

    /**
     * Helper method to quickly add an auto started service.
     * @since 1.9.0
     * @param string $id The id of the definition.
     * @param mixed $concrete The concrete implementation.
     * @param bool|null $shared Whether this is a shared instance.
     * @return DefinitionInterface The definition.
     */
    private function addAutoStart(string $id, $concrete = null, ?bool $shared = null): DefinitionInterface
    {
        return $this->getLeagueContainer()
            ->add($id, $concrete, $shared)
            ->addTag(self::AUTOSTART_TAG);
    }
}
