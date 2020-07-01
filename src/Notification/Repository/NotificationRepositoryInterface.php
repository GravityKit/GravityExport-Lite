<?php

namespace GFExcel\Notification\Repository;

use GFExcel\Notification\Exception\NotificationRepositoryException;
use GFExcel\Notification\Notification;

/**
 * Interface that represents a repository for the notifications.
 * @since $ver$
 */
interface NotificationRepositoryInterface
{
    /**
     * Should return the undismissed notifications.
     * @since $ver$
     * @return Notification[] The notifications.
     */
    public function getNotifications(): array;

    /**
     * Should mark the notification as dismissed.
     * @since $ver$
     * @param string $id The unique identifier of the notification.
     * @throws NotificationRepositoryException When something went wrong during dismissing.
     */
    public function markAsDismissed(string $id): void;

    /**
     * Should store the notification.
     * @since $ver$
     * @param Notification ...$notification The notification(s) to store.
     * @throws NotificationRepositoryException When something went wrong during storing.
     */
    public function storeNotification(Notification ...$notification): void;
}
