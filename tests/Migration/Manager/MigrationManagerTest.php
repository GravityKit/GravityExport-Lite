<?php

namespace GFExcel\Tests\Migration\Manager;

use GFExcel\Migration\Manager\MigrationManager;
use GFExcel\Migration\Migration;
use GFExcel\Tests\TestCase;

/**
 * Unit tests for {@see MigrationManager}.
 * @since $ver$
 */
class MigrationManagerTest extends TestCase
{
    /**
     * The manager under test.
     * @since $ver$
     * @var MigrationManager
     */
    private $manager;

    /**
     * @inheritdoc
     * @since $ver$
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->manager = new MigrationManager();
    }

    /**
     * Test case for {@see MigrationManager::setMigrations()}.
     * @since $ver$
     */
    public function testSetMigrations(): void
    {
        \WP_Mock::userFunction('get_option', [
            'args' => [MigrationManager::OPTION_MIGRATION_VERSION, '0.0.0'],
            'return' => '1.0.0',
        ]);

        $this->manager->setMigrations([
            new \stdClass(), // skipped because of type
            new Test_Migration_1_0_0(), // skipped because of the current version
            new Test_Migration_10_0_0(),
            new Test_Migration_2_0_0(),
            new Test_Migration_1_1_0(),
            new Test_Migration_2_0_0(),
        ]);

        // check to see if the order is correct.
        $this->assertSame(
            ['1.1.0', '2.0.0', '2.0.0', '10.0.0'],
            array_map(static function (Migration $migration): string {
                return $migration::getVersion();
            }, $this->manager->getMigrations())
        );
    }

    /**
     * Test case for {@see MigrationManager::getMigrations()}.
     * @since $ver$
     */
    public function testGetMigrations(): void
    {
        $this->assertSame([], $this->manager->getMigrations());
    }

    public function testMigrate(): void
    {
        \WP_Mock::userFunction('get_option', [
            'args' => [MigrationManager::OPTION_MIGRATION_VERSION, '0.0.0'],
            'return' => '1.0.0',
        ]);

        \WP_Mock::userFunction('get_transient', [
            'args' => [MigrationManager::TRANSIENT_MIGRATION_RUNNING],
            'return' => false,
        ]);

        \WP_Mock::userFunction('set_transient', [
            'args' => [MigrationManager::TRANSIENT_MIGRATION_RUNNING, true, 300],
        ]);

        \WP_Mock::userFunction('update_option', [
            'args' => [MigrationManager::OPTION_MIGRATION_VERSION, '1.1.0'],
        ]);

        \WP_Mock::userFunction('update_option', [
            'args' => [MigrationManager::OPTION_MIGRATION_VERSION, '2.0.0'],
        ]);

        \WP_Mock::userFunction('update_option', [
            'args' => [MigrationManager::OPTION_MIGRATION_VERSION, '10.0.0'],
        ]);

        \WP_Mock::userFunction('delete_transient', [
            'args' => [MigrationManager::TRANSIENT_MIGRATION_RUNNING],
        ]);

        $mock = $this->getMockBuilder(\stdClass::class)->setMethods(['triggered'])->getMock();
        $mock->expects($this->exactly(3))->method('triggered');

        $this->manager->setMigrations([
            new Test_Migration_10_0_0($mock),
            new Test_Migration_1_1_0($mock),
            new Test_Migration_2_0_0($mock),
        ]);

        $this->manager->migrate();
    }
}

class Test_Migration_1_0_0 extends Migration
{
    protected static $version = '1.0.0';

    private $object;

    public function __construct($object = null)
    {
        $this->object = $object;
    }

    public function run(): void
    {
        if ($this->object) {
            $this->object->triggered();
        }
    }
}

class Test_Migration_1_1_0 extends Test_Migration_1_0_0
{
    protected static $version = '1.1.0';
}

class Test_Migration_10_0_0 extends Test_Migration_1_0_0
{
    protected static $version = '10.0.0';
}

class Test_Migration_2_0_0 extends Test_Migration_1_0_0
{
    protected static $version = '2.0.0';
}
