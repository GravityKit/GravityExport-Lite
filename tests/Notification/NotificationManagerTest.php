<?php

namespace GFExcel\Tests\Notification;

use GFExcel\Notification\Notification;
use GFExcel\Notification\NotificationManager;
use GFExcel\Notification\NotificationManagerException;
use GFExcel\Notification\NotificationRepositoryInterface;
use GFExcel\Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for {@see NotificationManager}.
 * @since $ver$
 */
class NotificationManagerTest extends TestCase
{
    /*
     * The class under test.
     * @since $ver$
     * @var NotificationManager
     */
    private $manager;

    /**
     * A mocked instance of a notification repository.
     * @since $ver$
     * @var NotificationRepositoryInterface|MockObject
     */
    private $repository;

    /**
     * @inheritdoc
     * @since $ver$
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(NotificationRepositoryInterface::class);
        $this->manager = new NotificationManager($this->repository);
    }

    /**
     * Test case for {@see NotificationManager::__construct()}.
     * @since $ver$
     * @throws NotificationManagerException
     */
    public function testConstruct(): void
    {
        $notification = new Notification('1', 'Message');
        $this->repository->expects($this->once())->method('getNotifications')->willReturn([$notification]);
        $manager = new NotificationManager($this->repository);

        $this->assertSame([$notification], $manager->getNotifications());
    }

    /**
     * Test case for {@see NotificationManager::add()}.
     * @since $ver$
     * @throws NotificationManagerException
     */
    public function testAdd(): void
    {
        $notification = new Notification('1', 'Test message');
        $this->manager->add($notification, $notification);

        $this->assertCount(1, $this->manager->getNotifications());
    }

    /**
     * Test case for {@see NotificationManager::getNotifications()}.
     * @since $ver$
     * @throws NotificationManagerException
     */
    public function testGeNotifications(): void
    {
        $success = new Notification('1', 'Success', Notification::TYPE_SUCCESS);
        $error = new Notification('2', 'Error', Notification::TYPE_ERROR);

        $this->manager->add($success, $error);
        $this->assertSame([$success, $error], $this->manager->getNotifications());
        $this->assertSame([$success], $this->manager->getNotifications(Notification::TYPE_SUCCESS));
        $this->assertSame([$error], $this->manager->getNotifications(Notification::TYPE_ERROR));
        $this->assertSame([], $this->manager->getNotifications(Notification::TYPE_WARNING));
    }

    /**
     * Test case for {@see NotificationManager::getNotifications()} with invalid notification type.
     * @since $ver$
     * @throws NotificationManagerException
     */
    public function testGetNotificationWithInvalidType(): void
    {
        $this->expectExceptionObject(
            new NotificationManagerException('Notification type "invalid" does not exist.')
        );

        $this->manager->getNotifications('invalid');
    }

    /**
     * Test case for {@see NotificationManager::dismiss()}.
     * @since $ver$
     * @throws NotificationManagerException
     */
    public function testDismiss(): void
    {
        $this->repository->expects($this->once())->method('markAsDismissed')->with('1');
        $notification = new Notification('1', 'The message');
        $this->manager->dismiss($notification);
    }

    /**
     * Test case for {@see NotificationManager::dismiss()}.
     * @since $ver$
     * @throws NotificationManagerException
     */
    public function testDismissWithException(): void
    {
        $not_dismissible = new Notification('1', ' Can\'t dismiss me.', Notification::TYPE_ERROR, false);
        $this->expectExceptionObject(
            new NotificationManagerException('Notification is not dismissible.')
        );
        $this->manager->dismiss($not_dismissible);
    }
}