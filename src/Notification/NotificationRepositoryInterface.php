<?php

namespace GFExcel\Notification;

/**
 * Interface that represents a repository for the notifications.
 * @since $ver$
 */
interface NotificationRepositoryInterface
{
    /**
     * Should mark the notification as dismissed.
     * @since $ver$
     * @param string $id The unique identifier of the notification.
     */
    public function markAsDismissed(string $id): void;

    /**
     * Should return the undismissed notifications.
     * @since $ver$
     * @return Notification[] The notifications.
     */
    public function getNotifications(): array;
}
