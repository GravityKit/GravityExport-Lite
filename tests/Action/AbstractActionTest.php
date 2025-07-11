<?php

namespace GFExcel\Tests\Action;

use GFExcel\Action\AbstractAction;
use GFExcel\Addon\AddonInterface;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see AbstractAction}.
 * @since 2.4.0
 */
class AbstractActionTest extends TestCase
{
    /**
     * Test case for {@see AbstractAction::getName}.
     * @since 2.4.0
     */
    public function testGetName(): void
    {
        $this->assertEquals('concrete', (new ConcreteAction('concrete'))->getName());
    }

    /**
     * Test case for {@see AbstractAction::getName} with a missing name.
     * @since 2.4.0
     */
    public function testGetNameWithException(): void
    {
        $this->expectExceptionMessage(sprintf('Action "%s" should implement a $name variable.', ConcreteAction::class));
        $this->assertEquals('concrete', (new ConcreteAction(''))->getName());
    }
}

/**
 * Helper class to test {@see AbstractAction}.
 * @since 2.4.0
 */
class ConcreteAction extends AbstractAction
{
    /**
     * Helper constructor to set the name of the action.
     * @since 2.4.0
     * @param string $name
     */
    public function __construct(string $name)
    {
        self::$name = $name;
    }

    /**
     * @inheritdoc
     * @since 2.4.0
     */
    public function fire(\GFAddOn $addon, array $form): void
    {
        // empty by design.
    }
}
