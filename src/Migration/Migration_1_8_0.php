<?php

namespace GFExcel\Migration;

use GFExcel\GFExcel;
use GFExcel\Migration\Exception\MigrationException;
use GFExcel\Notification\Exception\NotificationException;
use GFExcel\Notification\Manager\NotificationManager;
use GFExcel\Notification\Notification;

/**
 * Migration for version 1.8.0
 * @since $ver$
 */
class Migration_1_8_0 extends Migration
{
    /**
     * @inheritdoc
     * @since $ver$
     */
    protected $version = '1.8.0';

    /**
     * The notification manager.
     * @since $ver$
     * @var NotificationManager
     */
    private $notification_manager;

    /**
     * Creates the migration.
     * @since $ver$
     * @param NotificationManager|null $notification_manager The notification manager.
     */
    public function __construct(?NotificationManager $notification_manager)
    {
        $this->notification_manager = $notification_manager ?? GFExcel::getNotificationManager();
    }

    /**
     * @inheritdoc
     * @since $ver$
     */
    public function run(): void
    {
        try {
            $this->notification_manager->add(
                new Notification('gfexcel-pro', __(
                    'Hi! I wanted to let you know that a <strong>premium add-on</strong> is being developed to complement <strong>Entries in Excel</strong>, Please <a href="https://subscribe.gfexcel.com/pro-add-on">visit this page</a> if you want to learn more.',
                    GFExcel::$slug
                ))
            );
        } catch (NotificationException $e) {
            throw new MigrationException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
