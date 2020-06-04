<?php

namespace GFExcel\Notification;

/**
 * Notification repository that retrieves the notifications from a transient value.
 * @since $ver$
 */
class NotificationRepository implements NotificationRepositoryInterface
{
    /**
     * The transient key that holds the notifications.
     * @since $ver$
     */
    public const NOTIFICATIONS_TRANSIENT = 'gfexcel_notifications';

    /**
     * @inheritdoc
     * @since $ver$
     */
    public function getNotifications(): array
    {
        $notifications = get_transient(self::NOTIFICATIONS_TRANSIENT);
        if (!$notifications || !is_array($notifications)) {
            $notifications = [];
        }

        // Only return instances of {@see Notification}.
        return array_filter($notifications, static function ($notification) {
            return $notification instanceof Notification;
        });
    }

    /**
     * @inheritdoc
     * @since $ver$
     */
    public function markAsDismissed(string $id): void
    {
        // Remove the notification from the transient and update it.
        $notifications = array_values(array_filter(
            $this->getNotifications(),
            static function (Notification $notification) use ($id): bool {
                // Keep all notifications that are not $id.
                return $notification->getId() !== $id;
            }
        ));

        if (!set_transient(self::NOTIFICATIONS_TRANSIENT, $notifications)) {
            throw $this->createNewStoreException();
        }
    }

    /**
     * @inheritdoc
     * @since $ver$
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
     * @since $ver$
     * @param string $message The exception message.
     * @return NotificationRepositoryException The exception.
     */
    private function createNewStoreException(
        string $message = 'Notifications could not be stored.'
    ): NotificationRepositoryException {
        return new NotificationRepositoryException($message);
    }
}
