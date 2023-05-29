<?php

namespace GFExcel\Action;

use GFExcel\Addon\GravityExportAddon;
use GFExcel\Generator\HashGeneratorInterface;

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
	 * @since 2.0.0
	 */
	public function __construct( HashGeneratorInterface $generator ) {
		parent::__construct( $generator );

		static::$success_message = esc_html__( 'The download URL has been enabled.', 'gk-gravityexport-lite' );
	}

	/**
	 * @inheritdoc
	 * @since 2.0.0
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
