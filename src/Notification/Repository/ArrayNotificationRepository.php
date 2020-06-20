<?php

namespace GFExcel\Notification\Repository;

use GFExcel\Notification\Notification;

/**
 * A notification repository backed by an array.
 * @since $ver$
 */
class ArrayNotificationRepository implements NotificationRepositoryInterface
{
    /**
     * Holds the notifications.
     * @since $ver$
     * @var Notification[]
     */
    private $notifications = [];

    /**
     * @inheritdoc
     * @since $ver$
     */
    public function getNotifications(): array
    {
        return $this->notifications;
    }

    /**
     * @inheritdoc
     * @since $ver$
     */
    public function markAsDismissed(string $id): void
    {
        if (array_key_exists($id, $this->notifications)) {
            unset($this->notifications[$id]);
        }
    }

    /**
     * @inheritdoc
     * @since $ver$
     */
    public function storeNotification(Notification ...$notification): void
    {
        foreach ($notification as $note) {
            $this->notifications[$note->getId()] = $note;
        }
    }
}
