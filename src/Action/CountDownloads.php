<?php

namespace GFExcel\Action;

use GFExcel\Addon\GravityExportAddon;
use GFExcel\GFExcelConfigConstants;

/**
 * @since 1.6.1
 */
class CountDownloads {
	/**
	 * Key used to store the download count in.
	 * @var string
	 */
	public const KEY_COUNT = 'download_count';

	/**
	 * The add on.
	 * @since 2.0.0
	 * @var GravityExportAddon
	 */
	private $addon;

	/**
	 * Register action to event.
	 */
	public function __construct() {
		add_action( GFExcelConfigConstants::GFEXCEL_EVENT_DOWNLOAD, [ $this, 'incrementCounter' ] );

		$this->addon = GravityExportAddon::get_instance();
	}

	/**
	 * Updates the download counter for a form.
	 * @since 1.6.1
	 *
	 * @param int $form_id The form id.
	 * @return void
	 */
	public function incrementCounter( $form_id ) {
		// Get the form data.
		$count = $this->addon->get_feed_meta_field( self::KEY_COUNT, $form_id, 0 );
		$this->setCounter( $form_id, ++ $count );
	}

	/**
	 * Helper function to actually set the value.
	 * @since 1.6.1
	 *
	 * @param int $form_id The form id.
	 * @param int $count The value to set the counter to.
	 */
	private function setCounter( $form_id, $count ) {
		$feed_id  = $this->addon->get_default_feed_id( $form_id );
		$settings = $this->addon->get_feed_by_form_id( $form_id );

		$meta                    = rgar( $settings, 'meta', [] );
		$meta[ self::KEY_COUNT ] = (int) $count;

		// store new data.
		$this->addon->save_feed_settings( $feed_id, $form_id, $meta );
	}
}
