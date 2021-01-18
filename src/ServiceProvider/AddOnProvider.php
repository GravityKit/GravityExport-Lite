<?php

namespace GFExcel\ServiceProvider;

use GFExcel\Action\ActionAwareInterface;
use GFExcel\Action\CountDownloads;
use GFExcel\Action\FilterRequest;
use GFExcel\Action\NotificationsAction;
use GFExcel\GFExcelConfigConstants;
use GFExcel\Migration\Manager\MigrationManager;
use GFExcel\Notification\Manager\NotificationManager;
use GFExcel\Notification\Repository\NotificationRepository;
use GFExcel\Notification\Repository\NotificationRepositoryInterface;
use GFExcel\Shorttag\DownloadUrl;

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
        ActionAwareInterface::ACTION_TAG,
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
}
