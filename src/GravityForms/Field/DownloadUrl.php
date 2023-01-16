<?php

namespace GFExcel\GravityForms\Field;

use GFExcel\Action\DownloadUrlDisableAction;
use GFExcel\Action\DownloadUrlEnableAction;
use GFExcel\Action\DownloadUrlResetAction;
use GFExcel\GFExcel;
use Gravity_Forms\Gravity_Forms\Settings\Fields\Text;

/**
 * A field that represents the download url from Gravity Export.
 * @since $ver$
 */
class DownloadUrl extends Text {
	/**
	 * @inheritdoc
	 * @since $ver$
	 */
	public $type = 'download_url';

	/**
	 * The asset's directory.
	 * @since $ver$
	 * @var string
	 */
	public $assets_dir = '';

	/**
	 * This is a readonly field.
	 * @since $ver$
	 * @var bool
	 */
	public $readonly = true;

	/**
	 * @inheritdoc
	 * @since $ver$
	 */
	public function markup(): string {
		$html = [];

		if ( ! $this->get_value() ) {
			$html[] = sprintf(
				'<button type="submit" name="gform-settings-save" value="%s" form="gform-settings" class="button button-secondary">%s</button>',
				DownloadUrlEnableAction::$name,
				'Enable download URL'
			);

			return implode( "\n", $html );
		}

		$html[] = parent::markup();

		$html[] = '<div class="copy-to-clipboard-container" style="justify-content: space-between">';
		$html[] = '<div>';

		$html[] = sprintf(
			'<button type="submit" onclick="%s" name="gform-settings-save" value="%s" form="gform-settings" class="button button-secondary">%s</button>',
			sprintf( 'return confirm(&quot;%s&quot;);', esc_attr__( 'You are about to reset the URL for this form. This can\'t be undone.', 'gk-gravityexport-lite' ) ),
			DownloadUrlResetAction::$name,
			esc_attr__('Regenerate URL', 'gk-gravityexport-lite')
		);
		$html[] = sprintf(
			'<button type="submit" onclick="%s" name="gform-settings-save" value="%s" form="gform-settings" class="button button-danger">%s</button>',
			sprintf( 'return confirm(&quot;%s.&quot;);', esc_attr__( 'You are about to disable the URL for this form. This will invalidate the URL, and can\'t be undone.', 'gk-gravityexport-lite' ) ),
			DownloadUrlDisableAction::$name,
			esc_attr__( 'Disable download URL', 'gk-gravityexport-lite' )
		);
		$html[] = '</div>';
		$html[] = '<div>';
		$html[] = '<span class="success hidden" aria-hidden="true">' . esc_html__(
				'Copied!',
				'gk-gravityexport-lite'
			) . '</span>';
		$html[] = '<button type="button" class="button copy-attachment-url" data-clipboard-target="[name=_gform_setting_hash]"><span class="dashicons dashicons-clipboard"></span>';
		$html[] = esc_html__( 'Copy URL to Clipboard', 'gk-gravityexport-lite' ) . '</button>';
		$html[] = '</div>';
		$html[] = '</div>';

		return implode( "\n", $html );
	}

	/**
	 * @inheritdoc
	 * @since $ver$
	 */
	public function save( $value ): string {
		$previous_values = $this->settings->get_previous_values();

		return $previous_values[ $this->name ] ?? '';
	}

	/**
	 * @inheritDoc
	 * Added the full url to the value.
	 * @since $ver$
	 */
	public function get_value() {
		if ( ! $hash = parent::get_value() ) {
			return '';
		}

		$blog_url = get_bloginfo( 'url' );
		if ( strpos( $hash, $blog_url ) !== false ) {
			return $hash;
		}

		$permalink = '/index.php?' . GFExcel::KEY_ACTION . '=%s&' . GFExcel::KEY_HASH . '=%s';
		$action    = GFExcel::$slug;

		if ( get_option( 'permalink_structure' ) ) {
			$permalink = '/%s/%s';
		} else {
			$hash = urlencode( $hash );
		}

		return $blog_url . sprintf( $permalink, $action, $hash );
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function do_validation( $value ): void {
		// Pass empty value as string to circumvent error.
		parent::do_validation( (string) $value );
	}

	/**
	 * @inheritdoc
	 * @since $ver$
	 */
	public function scripts(): array {
		$script = "(function($) { $(document).ready(function() { addClipboard('%s','%s'); });})(jQuery);";

		return [
			[
				'handle'   => 'gk-gravityexport-clipboard-js',
				'src'      => $this->assets_dir . 'js/clipboard.js',
				'callback' => function () use ( $script ) {
					wp_add_inline_script(
						'gk-gravityexport-clipboard-js',
						sprintf(
							$script,
							esc_attr( '.copy-attachment-url' ),
							esc_attr__( 'The file URL has been copied to your clipboard.', 'gk-gravityexport-lite' )
						)
					);
				},
				'deps'     => [ 'jquery', 'wp-a11y', 'wp-i18n', 'clipboard' ],
			],
		];
	}
}