<?php

namespace GFExcel\Tests\Values;

use GFExcel\Values\NumericValue;

/**
 * Test case for {@see NumericValue}.
 * @since $ver$
 */
class NumericValueTest extends AbstractValueTestCase
{
    /**
     * @inheritdoc
     * @since $ver$
     */
    public function setup(): void
    {
        parent::setup();
    }

    /**
     * Test case for {@see NumericValue::isNumeric()}.
     * @since $ver$
     */
    public function testIsNumeric(): void
    {
        $this->assertTrue((new NumericValue(1000, $this->gf_field))->isNumeric());
    }

    /**
     * Data provider for {@see NumericValueTest::testGetValue()}.
     * @since $ver$
     * @return mixed[] The data.
     */
    public function dataProviderForGetValue(): array
    {
        return [
            [1],
            ['one'],
            [1.000],
            [1E3],
        ];
    }

    /**
     * Test case for {@see NumericValue::getValue()}.
     * @since $ver$
     * @param mixed $value The provided value.
     * @dataProvider dataProviderForGetValue the data provider.
     */
    public function testGetValue($value): void
    {
        $this->assertSame($value, (new NumericValue($value, $this->gf_field))->getValue());
    }
}
