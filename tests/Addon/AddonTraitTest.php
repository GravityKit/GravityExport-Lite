<?php

namespace GFExcel\Tests\Addon;

use GFExcel\Addon\AddonInterface;
use GFExcel\Addon\AddonTrait;
use GFExcel\Tests\TestCase;

/**
 * Unit tests for {@see AddonTrait}.
 * @since 1.9.0
 */
class AddonTraitTest extends TestCase
{
    /**
     * Test case for {@see AddonTrait::get_instance()} without a provided instance.
     *
     * Must be the first test to avoid setting the instance.
     *
     * @since 1.9.0
     */
    public function testGetInstanceINoInstance(): void
    {
        $this->expectExceptionObject(new \RuntimeException(
            'No instance of "GFExcel\Tests\Addon\ConcreteAddon" provided.'
        ));
        ConcreteAddon::get_instance();
    }

    /**
     * Test case for {@see AddonTrait::set_instance()} and {@see AddonTrait::get_instance()}.
     * @since 1.9.0
     */
    public function testSetInstance(): void
    {
        $instance = new ConcreteAddon();
        ConcreteAddon::set_instance($instance);
        self::assertSame($instance, ConcreteAddon::get_instance());
    }

    /**
     * Test case for {@see AddonTrait::set_instance()} with an invalid type.
     * @since 1.9.0
     */
    public function testSetInstanceInvalidType(): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException(
            'Add-on instance must be of type "GFExcel\Tests\Addon\ConcreteAddon".'
        ));
        ConcreteAddon::set_instance($this->createMock(AddonInterface::class));
    }
}

/**
 * Concrete class that implements the trait.
 * @since 1.9.0
 */
class ConcreteAddon implements AddonInterface
{
    use AddonTrait;
}
