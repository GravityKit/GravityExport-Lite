<?php

namespace GFExcel\Notification\Repository;

use GFExcel\Notification\Exception\NotificationRepositoryException;
use GFExcel\Notification\Notification;

/**
 * Interface that represents a repository for the notifications.
 * @since 1.8.0
 */
interface NotificationRepositoryInterface
{
    /**
     * Should return the undismissed notifications.
     * @since 1.8.0
     * @return Notification[] The notifications.
     */
    public function getNotifications(): array;

    /**
     * Should mark the notification as dismissed.
     * @since 2.0.0
     * @param Notification $notification The notification.
     * @throws NotificationRepositoryException When something went wrong during dismissing.
     */
    public function markAsDismissed(Notification $notification): void;

    /**
     * Should store the notification.
     * @since 1.8.0
     * @param Notification ...$notification The notification(s) to store.
     * @throws NotificationRepositoryException When something went wrong during storing.
     */
    public function storeNotification(Notification ...$notification): void;
}
