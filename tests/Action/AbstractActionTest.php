<?php

namespace GFExcel\Tests\Action;

use GFExcel\Action\AbstractAction;
use GFExcel\Addon\AddonInterface;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see AbstractAction}.
 * @since $ver$
 */
class AbstractActionTest extends TestCase
{
    /**
     * Test case for {@see AbstractAction::getName}.
     * @since $ver$
     */
    public function testGetName(): void
    {
        $this->assertEquals('concrete', (new ConcreteAction('concrete'))->getName());
    }

    /**
     * Test case for {@see AbstractAction::getName} with a missing name.
     * @since $ver$
     */
    public function testGetNameWithException(): void
    {
        $this->expectExceptionMessage(sprintf('Action "%s" should implement a $name variable.', ConcreteAction::class));
        $this->assertEquals('concrete', (new ConcreteAction(''))->getName());
    }
}

/**
 * Helper class to test {@see AbstractAction}.
 * @since $ver$
 */
class ConcreteAction extends AbstractAction
{
    /**
     * Helper constructor to set the name of the action.
     * @since $ver$
     * @param string $name
     */
    public function __construct(string $name)
    {
        self::$name = $name;
    }

    /**
     * @inheritdoc
     * @since $ver$
     */
    public function fire(\GFAddOn $addon, array $form): void
    {
        // empty by design.
    }
}
