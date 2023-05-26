<?php

namespace GFExcel\Notification\Repository;

use GFExcel\Notification\Notification;

/**
 * A notification repository backed by an array.
 * @since 1.8.0
 */
class ArrayNotificationRepository implements NotificationRepositoryInterface
{
    /**
     * Holds the notifications.
     * @since 1.8.0
     * @var Notification[]
     */
    private $notifications = [];

    /**
     * @inheritdoc
     * @since 1.8.0
     */
    public function getNotifications(): array
    {
        return $this->notifications;
    }

    /**
     * @inheritdoc
     * @since 1.8.0
     */
    public function markAsDismissed(Notification $notification): void
    {
        if (array_key_exists($id = $notification->getId(), $this->notifications)) {
            unset($this->notifications[$id]);
        }
    }

    /**
     * @inheritdoc
     * @since 1.8.0
     */
    public function storeNotification(Notification ...$notification): void
    {
        foreach ($notification as $note) {
            $this->notifications[$note->getId()] = $note;
        }
    }
}
