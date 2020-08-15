<?php

namespace GFExcel\Tests\Values;

use GFExcel\Values\BoolValue;

/**
 * Unit tests for {@see BoolValue}.
 * @since 1.8.0
 */
class BoolValueTest extends AbstractValueTestCase
{
    /**
     * The class under test.
     * @since 1.8.0
     * @var BoolValue
     */
    private $value_object;

    /**
     * @inheritdoc
     * @since 1.8.0
     */
    public function setup(): void
    {
        parent::setup();

        $this->value_object = new BoolValue('0', $this->gf_field);
    }

    /**
     * Test case for {@see BoolValue::isBool()}.
     * @since 1.8.0
     */
    public function testIsBool(): void
    {
        $this->assertTrue($this->value_object->isBool());
    }

    /**
     * Test case for {@see BoolValue::getValue()}.
     * @since 1.8.0
     */
    public function testGetValue(): void
    {
        $this->assertFalse($this->value_object->getValue());
        $this->assertTrue((new BoolValue('value', $this->gf_field))->getValue());
    }
}
