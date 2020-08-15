<?php

namespace GFExcel\Tests\Values;

use GFExcel\Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Abstract test case for all Value types.
 * @since 1.8.0
 */
class AbstractValueTestCase extends TestCase
{
    /**
     * A mocked field instance.
     * @since 1.8.0
     * @var \GF_Field|MockObject
     */
    protected $gf_field;

    /**
     * @inheritdoc
     * @since 1.8.0
     */
    public function setup(): void
    {
        $this->gf_field =
            $this->getMockBuilder(\stdClass::class)
                ->setMockClassName('GF_Field')
                ->setMethods(['get_input_type'])
                ->getMock();
    }
}
