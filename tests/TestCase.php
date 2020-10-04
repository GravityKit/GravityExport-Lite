<?php

namespace GFExcel\Tests;

use WP_Mock\Tools\TestCase as BaseTestCase;

/**
 * Base test case for the plugin.
 * @since 1.8.0
 */
class TestCase extends BaseTestCase
{
    /**
     * @inheritdoc
     * @since 1.8.0
     */
    public function setUp(): void
    {
        parent::setUp();

        \WP_Mock::setUp();
    }

    /**
     * @inheritdoc
     * @since 1.8.0
     */
    public function tearDown(): void
    {
        parent::tearDown();
        \WP_Mock::tearDown();
    }
}