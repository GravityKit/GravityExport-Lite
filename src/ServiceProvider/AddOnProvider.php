<?php

namespace GFExcel\ServiceProvider;

use GFExcel\Action\CountDownloads;
use GFExcel\Action\FilterRequest;
use GFExcel\Action\NotificationsAction;
use GFExcel\GFExcelConfigConstants;
use GFExcel\Migration\Manager\MigrationManager;
use GFExcel\Notification\Manager\NotificationManager;
use GFExcel\Notification\Repository\NotificationRepository;
use GFExcel\Notification\Repository\NotificationRepositoryInterface;
use GFExcel\Shorttag\DownloadUrl;
use League\Container\Definition\DefinitionInterface;
use League\Container\ServiceProvider\AbstractServiceProvider;

/**
 * Service provider for the gravity forms add-on.
 * @since $ver$
 */
class AddOnProvider extends AbstractServiceProvider
{
    /**
     * @inheritdoc
     * @since $ver$
     */
    protected $provides = [
        GFExcelConfigConstants::GFEXCEL_ACTION_TAG,
        NotificationRepositoryInterface::class,
        NotificationManager::class,
        CountDownloads::class,
        FilterRequest::class,
        NotificationsAction::class,
        MigrationManager::class,
    ];

    /**
     * @inheritdoc
     * @since $ver$
     */
    public function register(): void
    {
        $container = $this->getLeagueContainer();

        $container->add(NotificationRepositoryInterface::class, NotificationRepository::class);
        $container->add(NotificationManager::class)->addArgument(NotificationRepositoryInterface::class);

        $this->addAction(CountDownloads::class);
        $this->addAction(DownloadUrl::class);
        $this->addAction(FilterRequest::class);
        $this->addAction(MigrationManager::class)->addArgument(NotificationManager::class);
        $this->addAction(NotificationsAction::class)->addArgument(NotificationManager::class);
    }

    /**
     * Helper method to quickly add an action.
     * @since $ver$
     * @param string $id The id of the definition.
     * @param mixed $concrete The concrete implementation.
     * @param bool|null $shared Whether this is a shared instance.
     * @return DefinitionInterface The definition.
     */
    private function addAction(string $id, $concrete = null, ?bool $shared = null): DefinitionInterface
    {
        return $this->getLeagueContainer()
            ->add($id, $concrete, $shared)
            ->addTag(GFExcelConfigConstants::GFEXCEL_ACTION_TAG);
    }
}
