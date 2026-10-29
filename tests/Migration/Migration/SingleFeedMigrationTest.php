<?php

namespace GFExcel\Tests\Migration\Migration;

use GFExcel\Migration\Migration\SingleFeedMigration;
use GFExcel\Tests\TestCase;

/**
 * Unit tests for {@see SingleFeedMigration}'s move of the pre-2.0 add-on settings.
 * @since 2.7.5
 */
class SingleFeedMigrationTest extends TestCase
{
    /**
     * Data provider for {@see self::testNothingToMoveWhenTheOldOptionIsNotAnArray()}.
     * @since 2.7.5
     * @return array[]
     */
    public function dataProviderForNoOldSettings(): array
    {
        return [
            'absent, as on a fresh install' => [false],
            'empty string' => [''],
            'string' => ['text'],
            'number' => [1],
        ];
    }

    /**
     * A site with no pre-2.0 settings array has nothing to move: the migration raises no notice or
     * error, and returns before it reaches the add-on to save settings.
     * @since 2.7.5
     * @covers \GFExcel\Migration\Migration\SingleFeedMigration::migrateAddonSettings
     * @see https://linear.app/gravitykit/issue/GEXPLIT-32
     * @dataProvider dataProviderForNoOldSettings
     * @param mixed $stored What `get_option()` returns for the old option.
     */
    public function testNothingToMoveWhenTheOldOptionIsNotAnArray($stored): void
    {
        \WP_Mock::userFunction('get_option', [
            'args' => ['gravityformsaddon_gf-entries-in-excel_settings'],
            'return' => $stored,
        ]);

        $raised = [];
        set_error_handler(static function (int $level, string $message) use (&$raised): bool {
            $raised[] = $message;

            return true;
        }, E_DEPRECATED);

        $thrown = null;
        try {
            $method = new \ReflectionMethod(SingleFeedMigration::class, 'migrateAddonSettings');
            $method->setAccessible(true);
            $method->invoke(new SingleFeedMigration());
        } catch (\Throwable $e) {
            // The add-on is not loaded in unit tests, so reaching it throws as well.
            $thrown = get_class($e) . ': ' . $e->getMessage();
        } finally {
            restore_error_handler();
        }

        self::assertSame([], $raised);
        self::assertNull($thrown);
    }
}
