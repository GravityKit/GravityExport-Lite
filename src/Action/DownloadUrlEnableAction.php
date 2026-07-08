<?php

namespace GFExcel\Action;

use GFExcel\Addon\GravityExportAddon;

/**
 * Action to reset the download URL for a form.
 * @since 2.0.0
 */
class DownloadUrlEnableAction extends DownloadUrlResetAction {
	/**
	 * @inheritdoc
	 * @since 2.0.0
	 */
	public static $name = 'download_url_enable';

	/**
	 * @inheritDoc
	 * @since TBD
	 */
	protected function get_success_message(): string {
		return esc_html__( 'The download URL has been enabled.', 'gk-gravityexport-lite' );
	}

	/**
	 * @inheritdoc
	 * @since 2.0.0
	 */
	public function fire( \GFAddOn $addon, array $form ): void {
		if ( ! $addon instanceof GravityExportAddon ) {
			return;
		}

		$settings = $form[2] ?? [];

		if ( ! empty( $settings['hash'] ?? null ) ) {
			// Feed is already enabled.
			return;
		}

		// Enable embed secret by default.
		$form[2]['has_embed_secret'] = 1;

		parent::fire( $addon, $form );
	}
}
