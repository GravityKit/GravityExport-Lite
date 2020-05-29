<?php

namespace GFExcel\Notification;

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
        $mapped = [];
        foreach ($notifications as $notification) {
            $mapped[$notification->getId()] = $notification;
        }

        $this->notifications = array_merge($this->notifications, $mapped);
    }

    /**
     * Dismisses the notification.
     * @since $ver$
     * @param Notification $notification
     * @throws NotificationManagerException When something went wrong during the dismissal.
     */
    public function dismiss(Notification $notification): void
    {
        if (!$notification->isDismissible()) {
            throw new NotificationManagerException('Notification is not dismissible.');
        }

        $this->repository->markAsDismissed($notification->getId());
    }

    /**
     * @since $ver$
     * @param string|null $notification_type The type to filter the notifications on.
     * @return Notification[] The notifications.
     * @throws NotificationManagerException When the notification type does not exist.
     */
    public function getNotifications(?string $notification_type = null): array
    {
        if (!$notification_type) {
            return $this->notifications;
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
}
