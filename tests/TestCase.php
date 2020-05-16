<?php

namespace GFExcel\Tests;

use WP_Mock\Tools\TestCase as BaseTestCase;

/**
 * Base test case for the plugin.
 * @since $ver$
 */
class TestCase extends BaseTestCase
{
    /**
     * @inheritdoc
     * @since $ver$
     */
    public function setUp(): void
    {
        parent::setUp();

        \WP_Mock::setUp();
    }

    /**
     * @inheritdoc
     * @since $ver$
     */
    public function tearDown(): void
    {
        parent::tearDown();
        \WP_Mock::tearDown();
    }
}