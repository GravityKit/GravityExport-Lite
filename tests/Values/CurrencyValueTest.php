<?php

namespace GFExcel\Tests\Values;

use GFExcel\Tests\TestCase;
use GFExcel\Values\CurrencyValue;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for {@see CurrencyValue}.
 * @since $ver$
 */
class CurrencyValueTest extends TestCase
{
    /**
     * The class under test.
     * @since $ver$
     * @var CurrencyValue
     */
    private $value_object;

    /**
     * A mocked field instance.
     * @since $ver$
     * @var \GF_Field|MockObject
     */
    private $gf_field;

    /**
     * @inheritdoc
     * @since $ver$
     */
    public function setup(): void
    {
        $this->gf_field = $this->getMockBuilder('GF_Field')->getMock();
        $this->value_object = new CurrencyValue(1000, $this->gf_field);
    }

    /**
     * Test case for {@see CurrencyValue::getFormat()} and {@see CurrencyValue::setFormat()}
     * @since $ver$
     */
    public function testFormat(): void
    {
        $this->assertSame(CurrencyValue::FORMAT_CURRENCY_NONE, $this->value_object->getFormat());
        $this->value_object->setFormat('test');
        $this->assertSame('test', $this->value_object->getFormat());
    }

    /**
     * Test case for {@see CurrencyValue::getSymbol()} and {@see CurrencyValue::setSymbol()}
     * @since $ver$
     */
    public function testSymbol(): void
    {
        $this->assertSame('$', $this->value_object->getSymbol());
        $this->value_object->setSymbol('test');
        $this->assertSame('test', $this->value_object->getSymbol());
    }

    /**
     * Test case for {@see CurrencyValue::isNumeric()}.
     * @since $ver$
     */
    public function testIsNumeric(): void
    {
        $this->assertTrue($this->value_object->isNumeric());
    }
}