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
	 * @since 2.7.0
	 */
	public function get_success_notice(): ActionNotice {
		return ActionNotice::success( esc_html__( 'The download URL has been enabled.', 'gk-gravityexport-lite' ) );
	}

	/**
	 * @inheritDoc
	 * @since 2.7.0
	 */
	public function fire_with_notice( \GFAddOn $addon, array $form ): ?ActionNotice {
		if ( ! $addon instanceof GravityExportAddon ) {
			return null;
		}

		$settings = $form[2] ?? [];

		if ( ! empty( $settings['hash'] ?? null ) ) {
			// Feed is already enabled.
			return null;
		}

		// Enable embed secret by default.
		$form[2]['has_embed_secret'] = 1;

		// A feed created here was never asked for by name, so it must not hand out
		// the entries to the internet. `GFExcel::isFormSecured()` reads this key as
		// a bool, so an absent value means public: write the 1 explicitly.
		$form[2]['is_secured'] = 1;

		return parent::fire_with_notice( $addon, $form );
	}
}
