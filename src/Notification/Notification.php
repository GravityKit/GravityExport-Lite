<?php

namespace GFExcel\Notification;

use GFExcel\Notification\Exception\NotificationException;

/**
 * Entity that represents a notification.
 * @since 1.8.0
 */
class Notification
{
    /**
     * Notification type that represents regular info.
     * @since 1.8.0
     * @var string
     */
    public const TYPE_INFO = 'info';

    /**
     * Notification type that represents an error.
     * @since 1.8.0
     * @var string
     */
    public const TYPE_ERROR = 'error';

    /**
     * Notification type that represents a warning.
     * @since 1.8.0
     * @var string
     */
    public const TYPE_WARNING = 'warning';

    /**
     * Notification type that represents success.
     * @since 1.8.0
     * @var string
     */
    public const TYPE_SUCCESS = 'success';

    /**
     * The unique identifier of the notification.
     * @since 1.8.0
     * @var string
     */
    protected $id;

    /**
     * Whether this is a dismissible notification.
     * @since 1.8.0
     * @var bool
     */
    protected $dismissible;

    /**
     * The notification type.
     * @since 1.8.0
     * @var string
     */
    protected $type;

    /**
     * The notification message.
     * @since 1.8.0
     * @var string
     */
    protected $message;

    /**
     * Notification constructor.
     * @param string $id The unique identifier of the notification.
     * @param string $message The notification message.
     * @param string $type The notification type.
     * @param bool $dismissible Whether this is a dismissible notification.
     * @throws NotificationException When the type is invalid.
     */
    public function __construct(string $id, string $message, string $type = self::TYPE_INFO, bool $dismissible = true)
    {
        if (!in_array($type, [
            self::TYPE_INFO,
            self::TYPE_ERROR,
            self::TYPE_SUCCESS,
            self::TYPE_WARNING,
        ], true)) {
            throw new NotificationException(
                sprintf('Notification type "%s" does not exist.', $type)
            );
        }

        $this->type = $type;
        $this->id = $id;
        $this->message = $message;
        $this->dismissible = $dismissible;
    }

    /**
     * Returns the unique identifier of the notification.
     * @since 1.8.0
     * @return string The identifier.
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Returns whether the notification is dismissible
     * @since 1.8.0
     * @return bool Whether the notification is dismissible.
     *
     */
    public function isDismissible(): bool
    {
        return $this->dismissible;
    }

    /**
     * Returns the notification type.
     * @since 1.8.0
     * @return string The notification type.
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Returns the message of the notification.
     * @since 1.8.0
     * @return string The message.
     */
    public function getMessage(): string
    {
        return $this->message;
    }
}
