<?php

namespace GFExcel\GravityForms\Field;

use GFExcel\Action\DownloadUrlDisableAction;
use GFExcel\Action\DownloadUrlEnableAction;
use GFExcel\Action\DownloadUrlResetAction;
use Gravity_Forms\Gravity_Forms\Settings\Fields\Base;
use Gravity_Forms\Gravity_Forms\Settings\Fields\Text;

/**
 * A field that represents the download url from Gravity Export.
 * @since $ver$
 */
class DownloadUrl extends Base {
	/**
	 * @inheritdoc
	 * @since $ver$
	 */
	public $type = 'download_url';

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

		$html[] = $this->get_read_only_url_input();

		$html[] = '<div class="copy-to-clipboard-container" style="justify-content: space-between">';
		$html[] = '<div>';

		$html[] = sprintf(
			'<button type="submit" onclick="%s" name="gform-settings-save" value="%s" form="gform-settings" class="button button-secondary">%s</button>',
			'return confirm(&quot;You are about to reset the URL for this form. This can\'t be undone.&quot;);',
			DownloadUrlResetAction::$name,
			'Regenerate URL'
		);
		$html[] = sprintf(
			'<button type="submit" onclick="%s" name="gform-settings-save" value="%s" form="gform-settings" class="button button-danger">%s</button>',
			'return confirm(&quot;You are about to disable the URL for this form. This can\'t be undone.&quot;);',
			DownloadUrlDisableAction::$name,
			'Disable download URL'
		);
		$html[] = '</div>';
		$html[] = '<div>';
		$html[] = '<span class="success hidden" aria-hidden="true">' . esc_html__(
				'Copied!',
				'gk-gravityexport'
			) . '</span>';
		$html[] = '<button type="button" class="button copy-attachment-url" data-clipboard-target="[name=_gform_setting_hash]"><span class="dashicons dashicons-clipboard"></span>';
		$html[] = esc_html__( 'Copy URL to Clipboard', 'gk-gravityexport' ) . '</button>';
		$html[] = '</div>';
		$html[] = '</div>';

		return implode( "\n", $html );
	}

	/**
	 * Helper method that returns the readonly input field that contains the download url.
	 * @return string
	 */
	private function get_read_only_url_input(): string {
		ob_start();

		$this->settings->render_field(
			new Text(
				[
					'name'     => $this->name,
					'readonly' => true,
					'value'    => $this->get_value(),
				],
				$this->settings
			)
		);

		return ob_get_clean();
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
	 * @inheritdoc
	 * @since $ver$
	 */
	public function scripts(): array {
		return [
			[
				'handle'   => 'gk-gravityexport-clipboard-js',
				'src'      => $this->assets_dir . 'js/clipboard.js',
				'callback' => function () {
					$script = <<<JS
(function($) {
    $(document).ready(function() {
        addClipboard('%s','%s');
    });
})(jQuery);
JS;
					$script = sprintf(
						$script,
						esc_attr( '.copy-attachment-url' ),
						esc_attr__( 'The file URL has been copied to your clipboard', 'gk-gravityexport' )
					);

					wp_add_inline_script( 'gk-gravityexport-clipboard-js', $script );
				},
				'deps'     => [ 'jquery', 'wp-a11y', 'wp-i18n', 'clipboard' ],
			],
		];
	}
}
