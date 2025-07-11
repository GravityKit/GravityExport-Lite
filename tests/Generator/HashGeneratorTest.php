<?php

namespace GFExcel\Tests\Generator;

use GFExcel\Generator\HashGenerator;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see HashGenerator}.
 * @since 2.4.0
 */
class HashGeneratorTest extends TestCase
{
    /**
     * Test case for {@see HashGenerator::generate()}.
     * @since 2.4.0
     * @throws \Exception
     */
    public function testGenerate(): void
    {
        $hash = (new HashGenerator())->generate();
        $this->assertEquals(32, strlen($hash));
        $this->assertNotEquals($hash, (new HashGenerator())->generate());
    }
}
