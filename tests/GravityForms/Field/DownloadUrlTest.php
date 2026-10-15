<?php

namespace GFExcel\Tests\GravityForms\Field;

use GFExcel\GravityForms\Field\DownloadUrl;
use GFExcel\Tests\TestCase;
use Gravity_Forms\Gravity_Forms\Settings\Settings;

/**
 * Unit tests for {@see DownloadUrl}.
 * @since TBD
 */
class DownloadUrlTest extends TestCase {
	/**
	 * The clipboard script must resolve inside the plugin whether or not the field
	 * was constructed with an `assets_dir` — a relative src would be concatenated
	 * straight onto site_url() by WordPress, emitting a malformed
	 * `<host>js/clipboard.js` request (no path separator).
	 *
	 * @since TBD
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
	 * The "Enable download URL" button shown before a URL exists must go through the
	 * plugin's text domain, or translated sites render it in English.
	 *
	 * @since TBD
	 */
	public function testEnableButtonLabelIsTranslated(): void {
		\WP_Mock::userFunction( 'esc_html__', [
			'args'   => [ 'Enable download URL', 'gk-gravityexport-lite' ],
			'times'  => 1,
			'return' => 'Activer l’URL de téléchargement',
		] );

		$field = new class( [ 'name' => 'hash', 'type' => 'download_url' ], \Mockery::mock( Settings::class ) ) extends DownloadUrl {
			public function get_value() {
				return '';
			}
		};

		$markup = $field->markup();

		$this->assertStringContainsString( '>Activer l’URL de téléchargement</button>', $markup );
		$this->assertStringNotContainsString( 'Enable download URL', $markup );
	}

	/**
	 * An explicitly provided assets_dir keeps winning.
	 * @since TBD
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
