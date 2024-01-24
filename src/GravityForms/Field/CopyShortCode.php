<?php

namespace GFExcel\GravityForms\Field;

use Gravity_Forms\Gravity_Forms\Settings\Fields\HTML;

/**
 * A field that contains the embed shortcode for this feed, with a copy to clipboard button.
 * @since $ver$
 */
final class CopyShortCode extends HTML {
	/**
	 * @inheritdoc
	 * @since $ver$
	 */
	public $type = 'copy_shortcode';

	/**
	 * @since $ver$
	 * @var null|string
	 */
	protected $embed_type;

	/**
	 * @inheritdoc
	 * @since $ver$
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
	 * @since $ver$
	 */
	public function markup(): string {
		$html = <<<HTML
<div class="copy-short-code copy-to-clipboard-container">
    <div class="input"><input type="text" readonly value="%s" id="embed_code"></div>
    <div class="success hidden" aria-hidden="true">Copied!</div>
    <button id="copy-embed-code" type="button" class="button" data-clipboard-target="[id=embed_code]">
        <span class="dashicons dashicons-clipboard"></span> %s
    </button>
</div>
HTML;

		return sprintf(
			$html,
			esc_attr( \GFExcel\Shorttag\DownloadUrl::generate_embed_short_code( rgget( 'id' ), $this->embed_type ) ),
			__( 'Copy shortcode', 'gk-gravityexport-lite' )
		);
	}
}
