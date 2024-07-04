<?php

namespace GFExcel\GravityForms\Field;

use Gravity_Forms\Gravity_Forms\Settings\Fields\HTML;
use GFExcel\Shortcode\DownloadUrl;

/**
 * A field that contains the embed shortcode for this feed, with a copy to clipboard button.
 * @since 2.2.0
 */
final class CopyShortcode extends HTML {
	/**
	 * @inheritdoc
	 * @since 2.2.0
	 */
	public $type = 'copy_shortcode';

	/**
	 * @since 2.2.0
	 * @var null|string
	 */
	protected $embed_type;

	/**
	 * @inheritdoc
	 * @since 2.2.0
	 */
	public function scripts(): array {
		$script = <<<JS
(function($) {
	$( document ).ready(function() {
        addClipboard('%s','%s');
	});
})(jQuery);
JS;

		return [
			[
				'handle'   => 'gk-gravityexport-clipboard-js',
				'callback' => function () use ( $script ) {
					wp_add_inline_script(
						'gk-gravityexport-clipboard-js',
						sprintf(
							$script,
							esc_attr( '#copy-embed-code' ),
							esc_attr__( 'The shortcode has been copied to your clipboard.', 'gk-gravityexport-lite' )
						)
					);
				},
				'deps'     => [ 'jquery', 'wp-a11y', 'wp-i18n', 'clipboard' ],
			],
		];
	}

	/**
	 * @inheritDoc
	 * @since 2.2.0
	 */
	public function markup(): string {
		$form_id        = (int) rgget( 'id' );
		$shortcode      = esc_attr( DownloadUrl::generate_embed_short_code( $form_id, $this->embed_type ) );
		$secret         = DownloadUrl::get_secret( $form_id );
		$copy_shortcode = esc_html__( 'Copy Shortcode', 'gk-gravityexport-lite' );

		return <<<HTML
<div class="copy-short-code copy-to-clipboard-container">
    <div class="input"><input type="text" readonly value="{$shortcode}" id="embed_code" data-secret="{$secret}"></div>
    <div class="success hidden" aria-hidden="true">Copied!</div>
    <button id="copy-embed-code" type="button" class="button" data-clipboard-target="[id=embed_code]">
        <span class="dashicons dashicons-clipboard"></span> {$copy_shortcode}
    </button>
</div>
HTML;
	}
}
