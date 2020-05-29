<?php

namespace GFExcel\Tests\Notification;

use GFExcel\Notification\Notification;
use GFExcel\Notification\NotificationManagerException;
use GFExcel\Tests\TestCase;

/**
 * Unit tests for {@see Notification}.
 * @since $ver$
 */
class NotificationTest extends TestCase
{
    /**
     * Test case for {@see Notification::getId()}.
     * @since $ver$
     */
    public function testGetId(): void
    {
        $this->assertSame('1', (new Notification('1', 'Message'))->getId());
    }

    /**
     * Test case for {@see Notification::getType()}.
     * @since $ver$
     */
    public function testGetType(): void
    {
        $this->assertSame(Notification::TYPE_ERROR,
            (new Notification('1', 'Message', Notification::TYPE_ERROR))->getType());
    }

    /**
     * Test case for {@see Notification::getType()} with an invalid type.
     * @since $ver$
     */
    public function testGetTypeWithInvalidType(): void
    {
        $this->expectExceptionObject(
            new NotificationManagerException('Notification type "invalid" does not exist.')
        );
        new Notification('1', 'Message', 'invalid');
    }

    /**
     * Test case for {@see Notification::isDismissible()}.
     * @since $ver$
     * @testWith [true]
     *           [false]
     */
    public function testIsDismissible(bool $is_dismissible): void
    {
        $this->assertSame(
            $is_dismissible,
            (new Notification('1', 'Message', Notification::TYPE_SUCCESS, $is_dismissible))->isDismissible()
        );
    }

    /**
     * Test case for {@see Notification::getMessage()}.
     * @since $ver$
     */
    public function testGetMessage(): void
    {
        $this->assertSame('The message', (new Notification('1', 'The message'))->getMessage());
    }
}
