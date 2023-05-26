<?php

namespace GFExcel\Notification\Repository;

use GFExcel\Notification\Exception\NotificationRepositoryException;
use GFExcel\Notification\Notification;

/**
 * Notification repository that retrieves the notifications from a transient value.
 * @since 1.8.0
 */
class NotificationRepository implements NotificationRepositoryInterface
{
    /**
     * The transient key that holds the notifications.
     * @since 1.8.0
     */
    public const NOTIFICATIONS_TRANSIENT = 'gfexcel_notifications';

    /**
     * @inheritdoc
     * @since 1.8.0
     */
    public function getNotifications(): array
    {
        $notifications = get_transient(self::NOTIFICATIONS_TRANSIENT);
        if (!$notifications || !is_array($notifications)) {
            $notifications = [];
        }

        // Only return instances of {@see Notification}.
        return array_filter($notifications, static function ($notification): bool {
            return $notification instanceof Notification;
        });
    }

    /**
     * @inheritdoc
     * @since 1.8.0
     */
    public function markAsDismissed(Notification $notification): void
    {
        // Remove the notification from the transient and update it.
        $notifications = array_values(array_filter(
            $this->getNotifications(),
            static function (Notification $other) use ($notification): bool {
                // Keep all notifications that are not $id.
                return $other->getId() !== $notification->getId();
            }
        ));

        if (!set_transient(self::NOTIFICATIONS_TRANSIENT, $notifications)) {
            throw $this->createNewStoreException();
        }
    }

    /**
     * @inheritdoc
     * @since 1.8.0
     */
    public function storeNotification(Notification ...$notification): void
    {
        $notifications = array_merge($this->getNotifications(), $notification);
        if (!set_transient(self::NOTIFICATIONS_TRANSIENT, $notifications)) {
            throw $this->createNewStoreException();
        }
    }

    /**
     * Helper method that creates a new exception instance.
     * @since 1.8.0
     * @param string $message The exception message.
     * @return NotificationRepositoryException The exception.
     */
    private function createNewStoreException(
        string $message = 'Notifications could not be stored.'
    ): NotificationRepositoryException {
        return new NotificationRepositoryException($message);
    }
}
