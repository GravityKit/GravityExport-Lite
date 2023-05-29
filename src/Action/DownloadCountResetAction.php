<?php

namespace GFExcel\Action;

use GFExcel\Addon\GravityExportAddon;

/**
 * Action that resets the count for this feed.
 * @since 2.0.0
 */
final class DownloadCountResetAction extends AbstractAction {
	/**
	 * @inheritDoc
	 * @since 2.0.0
	 */
	public static $name = 'download_count_reset';

	/**
	 * @inheritDoc
	 * @since 2.0.0
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
