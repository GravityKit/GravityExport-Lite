<?php

namespace GFExcel\Tests\Action;

use GFExcel\Action\NotificationsAction;
use GFExcel\Notification\Exception\NotificationManagerException;
use GFExcel\Notification\Manager\NotificationManager;
use GFExcel\Notification\Notification;
use GFExcel\Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for {@see NotificationsAction}.
 * @since $ver$
 */
class NotificationsActionTest extends TestCase
{
    /**
     * A mocked instance of {@see NotificationManager}.
     * @since $ver$
     * @var NotificationManager|MockObject
     */
    private $manager;

    /**
     * The class under test.
     * @since $ver$
     * @var NotificationsAction
     */
    private $action;

    /**
     * @inheritdoc
     * @since $ver$
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->manager = $this->createMock(NotificationManager::class);
        $this->action = new NotificationsAction($this->manager);
    }

    /**
     * Test case for {@see NotificationsAction::getNotifications()}.
     * @since $ver$
     * @throws NotificationManagerException
     */
    public function testGetNotifications(): void
    {
        $this->manager->expects($this->once())->method('getNotifications')->willReturn([]);
        $this->assertSame([], $this->action->getNotifications());
    }

    /**
     * Test case for {@see NotificationsAction::getNotifications()}.
     * @since $ver$
     * @throws NotificationManagerException
     */
    public function testGetNotificationsWithException(): void
    {
        $this->manager->expects($this->once())->method('getNotifications')->willThrowException(
            new NotificationManagerException('test exception')
        );

        $notifications = $this->action->getNotifications();
        $this->assertCount(1, $notifications);
        $this->assertSame('The notifications could not be retrieved.', $notifications[0]->getMessage());
        $this->assertSame(Notification::TYPE_ERROR, $notifications[0]->getType());
        $this->assertSame('manager-error', $notifications[0]->getId());
    }

    /**
     * Test case for {@see NotificationsAction::dismissNotification()}.
     * @since $ver$
     */
    public function testDismissNotification(): void
    {
        $_POST['notification_key'] = 'key-1';
        $_POST['nonce'] = 'test-nonce';

        \WP_Mock::userFunction('wp_verify_nonce', [
            'args' => ['test-nonce', NotificationsAction::KEY_NONCE],
            'return' => true,
        ]);

        \WP_Mock::userFunction('wp_die', [
            'args' => []
        ]);

        $this->manager->expects($this->once())->method('dismiss')->with('key-1');

        $this->action->dismissNotification();
    }

    /**
     * Test case for {@see NotificationsAction::dismissNotification()}.
     * @since $ver$
     */
    public function testDismissNotificationWithInvalidValues(): void
    {
        \WP_Mock::userFunction('wp_die', [
            'args' => [
                'No key or (valid) nonce provided.',
                'Something went wrong.',
                ['response' => 400],
            ],
        ]);

        $this->assertNull($this->action->dismissNotification());
    }

    /**
     * Test case for {@see NotificationsAction::dismissNotification()} with an exception..
     * @since $ver$
     */
    public function testDismissNotificationWithException(): void
    {
        $_POST['notification_key'] = 'key-1';
        $_POST['nonce'] = 'test-nonce';

        \WP_Mock::userFunction('wp_verify_nonce', [
            'args' => ['test-nonce', NotificationsAction::KEY_NONCE],
            'return' => true,
        ]);

        \WP_Mock::userFunction('wp_die', [
            'args' => [
                'test message',
                'Something went wrong.',
                ['response' => 400]
            ]
        ]);

        $this->manager->expects($this->once())->method('dismiss')->with('key-1')->willThrowException(
            new NotificationManagerException('test message')
        );

        $this->action->dismissNotification();
    }

    /**
     * Test case for {@see NotificationsAction::registerScripts()}.
     * @since $ver$
     */
    public function testRegisterScripts(): void
    {
        if (!defined('GFEXCEL_PLUGIN_FILE')) {
            define('GFEXCEL_PLUGIN_FILE', 'test');
        }

        \WP_Mock::userFunction('plugin_dir_url', [
            'args' => ['test'],
            'return' => 'test-path/',
        ]);

        \WP_Mock::userFunction('wp_enqueue_script', [
            'args' => [
                'gfexcel-notifications',
                'test-path/public/js/notifications.js',
                ['jquery'],
            ],
        ]);

        $this->assertNull($this->action->registerScripts());
    }
}
