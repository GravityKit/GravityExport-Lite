<?php

namespace GFExcel\Migration;

use GFExcel\GFExcel;
use GFExcel\Migration\Exception\MigrationException;
use GFExcel\Notification\Exception\NotificationException;
use GFExcel\Notification\Exception\NotificationManagerException;
use GFExcel\Notification\Manager\NotificationManager;
use GFExcel\Notification\Notification;

/**
 * Migration for version 1.8.0
 * @since 1.8.0
 * @codeCoverageIgnore Don't test every migration.
 */
class Migration_1_8_0 extends Migration
{
    /**
     * @inheritdoc
     * @since 1.8.0
     */
    protected static $version = '1.8.0';

    /**
     * The notification manager.
     * @since 1.8.0
     * @var NotificationManager
     */
    private $notification_manager;

    /**
     * Creates the migration.
     * @since 1.8.0
     */
    public function __construct()
    {
        $this->notification_manager = GFExcel::getNotificationManager();
    }

    /**
     * @inheritdoc
     * @since 1.8.0
     */
    public function run(): void
    {
        try {
            $this->notification_manager->storeNotification(
                new Notification('gfexcel-pro', __(
                    'Hi! I wanted to let you know that a <strong>pro add-on</strong> is being developed to complement <strong>Entries in Excel</strong>. Please <a target="_blank" rel="nofollow" href="https://subscribe.gfexcel.com/pro-add-on">visit this page</a> if you want to learn more.',
                    GFExcel::$slug
                ))
            );
        } catch (NotificationManagerException | NotificationException $e) {
            throw new MigrationException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
