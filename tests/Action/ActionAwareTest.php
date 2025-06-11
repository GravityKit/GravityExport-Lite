<?php

namespace GFExcel\Tests\Action;

use GFExcel\Action\ActionAware;
use GFExcel\Action\ActionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see ActionAware}.
 * @since $ver$
 */
class ActionAwareTest extends TestCase
{
    /**
     * The Trait under test.
     * @since $ver$
     * @var ActionAware
     */
    private $trait;

    private $actions = [];

    /**
     * {@inheritdoc}
     * @since $ver$
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->trait = new class {
            use ActionAware;
        };

        $this->actions = [
            $this->mockAction('mock1'),
            $this->mockAction('mock2'),
            $this->mockAction('mock3'),
        ];
    }

    /**
     * Test case for {@see ActionAware::setActions} and {@see ActionAware::getActions}.
     * @since $ver$
     */
    public function testActions(): void
    {
        $this->assertEmpty($this->trait->getActions());

        $this->trait->setActions($this->actions);

        $this->assertSame(array_reduce($this->actions, static function (array $actions, ActionInterface $action) {
            return array_merge($actions, [$action->getName() => $action]);
        }, []), $this->trait->getActions());
    }

    /**
     * Test case for {@see ActionAware::hasAction}.
     * @since $ver$
     */
    public function testHasAction(): void
    {
        $this->trait->setActions($this->actions);

        $this->assertTrue($this->trait->hasAction('mock1'));
        $this->assertTrue($this->trait->hasAction('mock2'));
        $this->assertTrue($this->trait->hasAction('mock3'));
        $this->assertFalse($this->trait->hasAction('mock4'));
    }

    /**
     * Test case for {@see ActionAware::getAction}.
     * @since $ver$
     */
    public function testGetAction(): void
    {
        $this->trait->setActions($this->actions);

        $this->assertSame($this->actions[0], $this->trait->getAction('mock1'));
        $this->assertSame($this->actions[1], $this->trait->getAction('mock2'));
        $this->assertSame($this->actions[2], $this->trait->getAction('mock3'));
    }

    /**
     * Test case for {@see ActionAware::getAction}.
     * @since $ver$
     */
    public function testGetActionWithException(): void
    {
        $this->trait->setActions($this->actions);

        $this->expectExceptionMessage('Action "mock4" is not implemented.');

        $this->trait->getAction('mock4');
    }

    /**
     *
     * @since $ver$
     * @param string $name
     * @return MockObject|ActionInterface
     */
    private function mockAction(string $name): MockObject
    {
        $mock = $this->createMock(ActionInterface::class);
        $mock->expects($this->atLeastOnce())->method('getName')->willReturn($name);

        return $mock;
    }
}
