<?php

namespace GFExcel\Action;

use GFExcel\Addon\GravityExportAddon;

/**
 * Action that resets the count for this feed.
 * @since $ver$
 */
final class DownloadCountResetAction extends AbstractAction {
	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public static $name = 'download_count_reset';

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function fire( \GFAddOn $addon, array $form ): void {
		if ( ! $addon instanceof GravityExportAddon ) {
			return;
		}

		[ $feed_id, $form_id, $settings ] = $form;

		// Reset the count.
		$settings[ CountDownloads::KEY_COUNT ] = 0;

		// store new data.
		$addon->save_feed_settings( $feed_id, $form_id, $settings );
	}
}
