<?php

namespace GFExcel\Notification\Manager;

use GFExcel\Notification\Exception\NotificationManagerException;
use GFExcel\Notification\Exception\NotificationRepositoryException;
use GFExcel\Notification\Notification;
use GFExcel\Notification\Repository\NotificationRepositoryInterface;

/**
 * Service to manage the notifications for the plugin.
 * @since $ver$
 */
class NotificationManager
{
    /**
     * The notifications
     * @since $ver$
     * @var Notification[]
     */
    protected $notifications = [];

    /**
     * The notification repository.
     * @since $ver$
     * @var NotificationRepositoryInterface
     */
    protected $repository;

    /**
     * Creates a new notification manager..
     * @since $ver$
     * @param NotificationRepositoryInterface $repository The notification repository.
     */
    public function __construct(NotificationRepositoryInterface $repository)
    {
        $this->repository = $repository;

        $this->add(...$this->repository->getNotifications());
    }

    /**
     * Adds a notification to the notification stack.
     * @since $ver$
     * @param Notification ...$notifications The notifications.
     */
    public function add(Notification ...$notifications): void
    {
        foreach ($notifications as $notification) {
            if ($this->hasNotification($notification->getId())) {
                continue;
            }

            $this->notifications[$notification->getId()] = $notification;
        }
    }

    /**
     * Dismisses the notification.
     * @since $ver$
     * @param string $id The notification id.
     * @throws NotificationManagerException When something went wrong during the dismissal.
     */
    public function dismiss(string $id): void
    {
        $notification = $this->getNotification($id);

        if (!$notification->isDismissible()) {
            throw new NotificationManagerException('Notification is not dismissible.');
        }

        try {
            $this->repository->markAsDismissed($id);
        } catch (NotificationRepositoryException $e) {
            throw new NotificationManagerException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Returns the notification by id.
     * @since $ver$
     * @param string $id The id of the notification.
     * @return Notification The notification.
     * @throws NotificationManagerException when the notification does not exist.
     */
    public function getNotification(string $id): Notification
    {
        if (!$this->hasNotification($id)) {
            throw new NotificationManagerException('Notification id does not exist.');
        }

        return $this->notifications[$id];
    }

    /**
     * Get all notifications (filtered on type).
     * @since $ver$
     * @param string|null $notification_type The type to filter the notifications on.
     * @return Notification[] The notifications.
     * @throws NotificationManagerException When the notification type does not exist.
     */
    public function getNotifications(?string $notification_type = null): array
    {
        if (!$notification_type) {
            return array_values($this->notifications);
        }

        if (!in_array($notification_type, [
            Notification::TYPE_INFO,
            Notification::TYPE_ERROR,
            Notification::TYPE_SUCCESS,
            Notification::TYPE_WARNING,
        ], true)) {
            throw new NotificationManagerException(
                sprintf('Notification type "%s" does not exist.', $notification_type)
            );
        }

        return array_values(array_filter(
            $this->notifications,
            static function (Notification $notification) use ($notification_type): bool {
                return $notification->getType() === $notification_type;
            }
        ));
    }

    /**
     * Returns whether the notification exists.
     * @since $ver$
     * @param string $id The id of the notification.
     * @return bool Whether the notification exists.
     */
    public function hasNotification(string $id): bool
    {
        return array_key_exists($id, $this->notifications);
    }
}
