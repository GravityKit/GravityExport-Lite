<?php

namespace GFExcel\Tests\Notification\Repository;

use GFExcel\Notification\Exception\NotificationRepositoryException;
use GFExcel\Notification\Notification;
use GFExcel\Notification\Repository\NotificationRepository;
use GFExcel\Tests\TestCase;

/**
 * Unit tests for {@see NotificationRepository}.
 * @since 1.8.0
 */
class NotificationRepositoryTest extends TestCase
{
    /**
     * The class under test.
     * @since 1.8.0
     * @var NotificationRepository
     */
    private $repository;

    /**
     * @inheritdoc
     * @since 1.8.0
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->repository = new NotificationRepository();
    }

    /**
     * Data provider for {@see NotificationRepositoryTest::testGetNotifications()}.
     * @since 1.8.0
     * @return mixed[] The provided data.
     */
    public function dataProviderForGetNotifications(): array
    {
        $notifications = $this->getNotifications();
        $wrong_array = array_merge($notifications, [
            new \stdClass(), // a class that isn't a notification.
        ]);

        return [
            'null' => [null, []],
            'empty string' => ['', []],
            'empty array' => [[], []],
            'dumb string' => ['dumb', []],
            'filled array' => [$notifications, $notifications],
            'filter filled array' => [$wrong_array, $notifications],
        ];
    }

    /**
     * Test case for {@see NotificationRepository::getNotifications()}.
     * @since 1.8.0
     * @param mixed $transient_return The return value from the transient.
     * @param array $expected_result The expected result.
     * @dataProvider dataProviderForGetNotifications
     */
    public function testGetNotifications($transient_return, array $expected_result): void
    {
        $this->setupGetNotifications($transient_return);
        $this->assertSame($expected_result, $this->repository->getNotifications());
    }

    /**
     * Test case for {@see NotificationRepository::markAsDismissed()}.
     * @since 1.8.0
     * @throws NotificationRepositoryException
     */
    public function testMarkAsDismissed(): void
    {
        $notifications = $this->getNotifications();
        $this->setupGetNotifications($notifications);

        \WP_Mock::userFunction('set_transient', [
            'args' => [
                NotificationRepository::NOTIFICATIONS_TRANSIENT,
                [$notifications[1]],
            ],
            'return' => true,
        ]);

        $this->assertNull($this->repository->markAsDismissed($notifications[0]));
    }

    /**
     * Test case for {@see NotificationRepository::markAsDismissed()} with an exception.
     * @since 1.8.0
     */
    public function testMarkAsDismissedWithException(): void
    {
        $this->setupGetNotifications([]);
        \WP_Mock::userFunction('set_transient', ['return' => false]);

        $this->expectExceptionObject(
            new NotificationRepositoryException('Notifications could not be stored.')
        );
        $this->repository->markAsDismissed(new Notification('wrong','wrong'));
    }

    /**
     * Test case for {@see NotificationRepository::storeNotification()}.
     * @since 1.8.0
     * @throws NotificationRepositoryException
     */
    public function testStoreNotification(): void
    {
        $notifications = $this->getNotifications();
        $this->setupGetNotifications($notifications);
        $new_notifications = [
            new Notification('third', 'Third notification'),
            new Notification('fourth', 'Fourth notification'),
        ];

        \WP_Mock::userFunction('set_transient', [
            'args' => [
                NotificationRepository::NOTIFICATIONS_TRANSIENT,
                array_merge($notifications, $new_notifications),
            ],
            'return' => true,
        ]);

        $this->assertNull($this->repository->storeNotification(...$new_notifications));
    }

    /**
     * Test case for {@see NotificationRepository::storeNotification()} with an exception.
     * @since 1.8.0
     * @throws NotificationRepositoryException
     */
    public function testStoreNotificationWithException(): void
    {
        $this->setupGetNotifications([]);
        \WP_Mock::userFunction('set_transient', ['return' => false]);

        $this->expectExceptionObject(
            new NotificationRepositoryException('Notifications could not be stored.')
        );

        $this->repository->storeNotification();
    }

    /**
     * Helper method to mock the `get_transient` return value.
     * @since 1.8.0
     * @param mixed $transient_return The return value.
     */
    public function setupGetNotifications($transient_return): void
    {
        \WP_Mock::userFunction('get_transient', [
            'args' => [NotificationRepository::NOTIFICATIONS_TRANSIENT],
            'return' => $transient_return,
        ]);
    }

    /**
     * Helper method that returns 2 notifications.
     * @since 1.8.0
     * @return Notification[] The notifications.
     */
    private function getNotifications(): array
    {
        return [
            new Notification('first', 'First notification'),
            new Notification('second', 'Second notification'),
        ];
    }
}
