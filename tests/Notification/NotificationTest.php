<?php

namespace GFExcel\Tests\Notification;

use GFExcel\Notification\Exception\NotificationException;
use GFExcel\Notification\Notification;
use GFExcel\Tests\TestCase;

/**
 * Unit tests for {@see Notification}.
 * @since 1.8.0
 */
class NotificationTest extends TestCase
{
    /**
     * Test case for {@see Notification::getId()}.
     * @since 1.8.0
     */
    public function testGetId(): void
    {
        $this->assertSame('1', (new Notification('1', 'Message'))->getId());
    }

    /**
     * Test case for {@see Notification::getType()}.
     * @since 1.8.0
     */
    public function testGetType(): void
    {
        $this->assertSame(Notification::TYPE_ERROR,
            (new Notification('1', 'Message', Notification::TYPE_ERROR))->getType());
    }

    /**
     * Test case for {@see Notification::getType()} with an invalid type.
     * @since 1.8.0
     */
    public function testGetTypeWithInvalidType(): void
    {
        $this->expectExceptionObject(
            new NotificationException('Notification type "invalid" does not exist.')
        );
        new Notification('1', 'Message', 'invalid');
    }

    /**
     * Test case for {@see Notification::isDismissible()}.
     * @since 1.8.0
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
     * @since 1.8.0
     */
    public function testGetMessage(): void
    {
        $this->assertSame('The message', (new Notification('1', 'The message'))->getMessage());
    }
}
