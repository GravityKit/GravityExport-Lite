<?php

namespace GFExcel\Tests\Container;

use GFExcel\Container\ContainerAware;
use GFExcel\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see ContainerAware}.
 * @since 2.4.0
 */
class ContainerAwareTest extends TestCase
{
    /**
     * Trait under test.
     * @since 2.4.0
     */
    private $trait;

    /**
     * @inheritdoc
     * @since 2.4.0
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
     * @since 2.4.0
     */
    public function testContainer(): void {
	    $container = $this->createMock( ContainerInterface::class );
	    $this->trait->setContainer( $container );
	    $this->assertEquals( $container, $this->trait->getContainer() );
    }
}
