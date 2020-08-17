<?php

namespace GFExcel\Tests\Values;

use GFExcel\Values\NumericValue;

/**
 * Test case for {@see NumericValue}.
 * @since 1.8.0
 */
class NumericValueTest extends AbstractValueTestCase
{
    /**
     * @inheritdoc
     * @since 1.8.0
     */
    public function setup(): void
    {
        parent::setup();
    }

    /**
     * Test case for {@see NumericValue::isNumeric()}.
     * @since 1.8.0
     */
    public function testIsNumeric(): void
    {
        $this->assertTrue((new NumericValue(1000, $this->gf_field))->isNumeric());
    }

    /**
     * Data provider for {@see NumericValueTest::testGetValue()}.
     * @since 1.8.0
     * @return mixed[] The data.
     */
    public function dataProviderForGetValue(): array
    {
        return [
            [1],
            [1.000],
            [1E3],
        ];
    }

    /**
     * Test case for {@see NumericValue::getValue()}.
     * @since 1.8.0
     * @param mixed $value The provided value.
     * @dataProvider dataProviderForGetValue the data provider.
     */
    public function testGetValue($value): void
    {
        $this->assertSame($value, (new NumericValue($value, $this->gf_field))->getValue());
    }

    /**
     * Test case for {@see NumericValue::getValue()} with a non-numeric value.
     * @since 1.8.2
     */
    public function testGetValueWithNonNumericValue(): void
    {
        $this->assertNull((new NumericValue('one', $this->gf_field))->getValue());
    }

    /**
     * Test case for {@see NumericValue::getFormat()} and {@see NumericValue::setFormat()}.
     * @since 1.8.0
     */
    public function testFormat(): void
    {
        $value_object = new NumericValue(100.30, $this->gf_field);
        $this->assertSame(NumericValue::FORMAT_DEFAULT, $value_object->getFormat());
        $value_object->setFormat('test');
        $this->assertSame('test', $value_object->getFormat());
    }
}
