<?php

namespace GFExcel\Action;

use GFExcel\Addon\GravityExportAddon;

/**
 * Action to reset the download URL for a form.
 * @since $ver$
 */
class DownloadUrlEnableAction extends DownloadUrlResetAction {
	/**
	 * @inheritdoc
	 * @since $ver$
	 */
	public static $name = 'download_url_enable';

	/**
	 * @inheritdoc
	 * @since $ver$
	 */
	protected static $success_message = 'The download URL has been enabled.';

	/**
	 * @inheritdoc
	 * @since $ver$
	 */
	public function fire( \GFAddOn $addon, array $form ): void {
		if ( ! $addon instanceof GravityExportAddon ) {
			return;
		}

		[ , , $settings ] = $form;

		if ( ! empty( $settings['hash'] ?? null ) ) {
			// Feed is already enabled.
			return;
		}

		parent::fire( $addon, $form );
	}
}
