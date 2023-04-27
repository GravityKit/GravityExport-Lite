<?php

namespace GFExcel\Tests\Migration;

use GFExcel\Migration\Manager\MigrationManager;
use GFExcel\Migration\Migration\Migration;
use GFExcel\Tests\TestCase;

/**
 * Unit tests for {@see Migration}.
 * @since 1.9.0
 */
class MigrationTest extends TestCase
{
    /**
     * Test case for {@see Migration::setManager()}.
     * @since 1.9.0
     */
    public function testSetManager(): void
    {
        $manager = $this->createMock(MigrationManager::class);
        $manager->expects($this->once())->method('migrate');

        $migration = new ConcreteMigration();
        $migration->setManager($manager);
        $migration->run();
    }

    /**
     * Test case for {@see Migration::getVersion()}.
     * @since 1.9.0
     */
    public function testGetVersion(): void
    {
        $this->assertSame('0.0.0', Migration::getVersion());
        $this->assertSame('1.2.3', ConcreteMigration::getVersion());
    }
}

class ConcreteMigration extends Migration
{
    protected static $version = '1.2.3';

    public function run(): void
    {
        $this->manager->migrate();
    }
}
