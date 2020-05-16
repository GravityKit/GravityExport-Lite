<?php

namespace GFExcel\Tests\Values;

use GFExcel\Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Abstract test case for all Value types.
 * @since $ver$
 */
class AbstractValueTestCase extends TestCase
{
    /**
     * A mocked field instance.
     * @since $ver$
     * @var \GF_Field|MockObject
     */
    protected $gf_field;

    public function setup(): void
    {
        $this->gf_field =
            $this->getMockBuilder(\stdClass::class)
                ->setMockClassName('GF_Field')
                ->addMethods(['get_input_type'])
                ->getMock();
    }
}
