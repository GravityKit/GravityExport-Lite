<?php

namespace GFExcel\Tests\GravityForms\Field;

use GFExcel\GravityForms\Field\DownloadUrl;
use GFExcel\Tests\TestCase;
use Gravity_Forms\Gravity_Forms\Settings\Settings;

/**
 * Unit tests for {@see DownloadUrl}.
 * @since 2.6.1
 */
class DownloadUrlTest extends TestCase {
	/**
	 * The clipboard script must resolve inside the plugin whether or not the field
	 * was constructed with an `assets_dir` — a relative src would be concatenated
	 * straight onto site_url() by WordPress, emitting a malformed
	 * `<host>js/clipboard.js` request (no path separator).
	 *
	 * @since 2.6.1
	 */
	public function testScriptsSrcIsAbsoluteWithoutAssetsDir(): void {
		\WP_Mock::userFunction( 'plugin_dir_url', [
			'return' => 'https://example.test/wp-content/plugins/gk-gravityexport-lite/',
		] );

		$field = new DownloadUrl( [ 'name' => 'hash', 'type' => 'download_url' ], \Mockery::mock( Settings::class ) );

		$scripts = $field->scripts();

		$this->assertSame(
			'https://example.test/wp-content/plugins/gk-gravityexport-lite/public/js/clipboard.js',
			$scripts[0]['src']
		);
	}

	/**
	 * An explicitly provided assets_dir keeps winning.
	 * @since 2.6.1
	 */
	public function testScriptsSrcUsesProvidedAssetsDir(): void {
		$field = new DownloadUrl(
			[
				'name'       => 'hash',
				'type'       => 'download_url',
				'assets_dir' => 'https://example.test/wp-content/plugins/gk-gravityexport-lite/public/',
			],
			\Mockery::mock( Settings::class )
		);

		$scripts = $field->scripts();

		$this->assertSame(
			'https://example.test/wp-content/plugins/gk-gravityexport-lite/public/js/clipboard.js',
			$scripts[0]['src']
		);
	}
}
