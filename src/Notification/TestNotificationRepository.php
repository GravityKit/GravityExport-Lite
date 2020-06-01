<?php

namespace GFExcel\Notification;

/**
 * @codeCoverageIgnore
 * @todo remove me.
 */
class TestNotificationRepository implements NotificationRepositoryInterface
{
    /**
     * @inheritdoc
     * @since $ver$
     */
    public function markAsDismissed(string $id): void
    {
    }

    /**
     * @inheritdoc
     * @since $ver$
     */
    public function getNotifications(): array
    {
        return [
            new Notification(
                'test',
                'Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus.',
                Notification::TYPE_SUCCESS
            ),
            new Notification(
                'test2',
                'Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus.'
            ),
        ];
    }
}
