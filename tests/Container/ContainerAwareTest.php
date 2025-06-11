<?php

namespace GFExcel\Tests\Container;

use GFExcel\Container\ContainerAware;
use GFExcel\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see ContainerAware}.
 * @since $ver$
 */
class ContainerAwareTest extends TestCase
{
    /**
     * Trait under test.
     * @since $ver$
     * @var ContainerAware
     */
    private $trait;

    /**
     * @inheritdoc
     * @since $ver$
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->trait = new class {
            use ContainerAware;
        };
    }

    /**
     * Test case for {@see ContainerAware::setContainer} and {@see ContainerAware::getContaienr}.
     * @since $ver$
     */
    public function testContainer(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $this->assertNull($this->trait->setContainer($container));
        $this->assertEquals($container, $this->trait->getContainer());
    }
}
