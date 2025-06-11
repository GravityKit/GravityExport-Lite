<?php

namespace GFExcel\Tests\Generator;

use GFExcel\Generator\HashGenerator;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see HashGenerator}.
 * @since $ver$
 */
class HashGeneratorTest extends TestCase
{
    /**
     * Test case for {@see HashGenerator::generate()}.
     * @since $ver$
     * @throws \Exception
     */
    public function testGenerate(): void
    {
        $hash = (new HashGenerator())->generate();
        $this->assertIsString($hash);
        $this->assertEquals(32, strlen($hash));
        $this->assertNotEquals($hash, (new HashGenerator())->generate());
    }
}
