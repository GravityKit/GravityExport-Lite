<?php

namespace {

    use GFExcel\Shorttag\DownloadUrl;
    use WP_Mock\Functions;

    /**
     * Mocked instance of GfAddOn
     * @since $ver$
     */
    abstract class GFAddOn
    {
        public function __construct()
        {
        }

        public function get_plugin_setting(string $setting)
        {
            return true;
        }
    }

    \WP_Mock::userFunction('add_shortcode', [
        'args' => [
            DownloadUrl::SHORTTAG,
            Functions::type('array'),
        ]
    ]);
}

namespace GFExcel\Tests\Values {

    use GFExcel\Values\StringValue;

    /**
     * Unit tests for {@see StringValue}.
     * @since $ver$
     */
    class StringValueTest extends AbstractValueTestCase
    {
        public function testIsUrl(): void
        {
            $non_url = new StringValue('non-url', $this->gf_field);
            $url = new StringValue('https://gfexcel.com', $this->gf_field);
            $this->assertFalse($non_url->getUrl());
            $this->assertSame('https://gfexcel.com', $url->getUrl());
        }
    }
}