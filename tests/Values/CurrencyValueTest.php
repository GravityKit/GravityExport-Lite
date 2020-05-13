<?php

namespace GFExcel\Tests\Values;

use GFExcel\Tests\TestCase;
use GFExcel\Values\CurrencyValue;
use PHPUnit\Framework\MockObject\MockObject;

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
        $this->gf_field = $this->createMock(\GF_Field::class);
        $this->value_object = new CurrencyValue(1000, $this->gf_field);
    }

    public function testFormat(): void
    {
        $this->assertSame('$', $this->value_object->getFormat());
    }
}