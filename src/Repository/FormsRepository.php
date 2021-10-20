<?php

namespace GFExcel\Repository;

use GFAPI;
use GFExcel\Addon\GFExcelAddon;
use GFExcel\GFExcelAdmin;

class FormsRepository {
	/** @var array|false */
	private $form;
	/**
	 * @var GFExcelAdmin
	 */
	private $admin;

	public function __construct( $form_id ) {
		$this->form  = $form_id ? GFAPI::get_form( $form_id ) : [];
		$this->admin = GFExcelAdmin::get_instance();
	}

	/**
	 * Whether to show notes based on setting or filter
	 * @return bool
	 */
	public function showNotes() {
		$value = false; //default
		if ( $setting = $this->admin->get_plugin_setting( 'notes_enabled' ) ) {
			$value = (bool) $setting;
		}

		$form_id = rgar( $this->getForm(), 'id', 0 );

		$value = GFExcelAddon::get_instance()->get_feed_meta_field( 'enable_notes', $form_id, $value );

		return (bool) gf_apply_filters( [ 'gfexcel_field_notes_enabled', $form_id ], $value );
	}

	/**
	 * Get field to sort the data by
	 * @return mixed
	 */
	public function getSortField() {
		$form_id = rgar( $this->getForm(), 'id', 0 );

		$value = GFExcelAddon::get_instance()->get_feed_meta_field( 'sort_field', $form_id, 'date_created' );

		return gf_apply_filters( [ 'gfexcel_output_sort_field', $form_id ], $value );
	}

	/**
	 * In what order should the data be sorted
	 * @return string
	 */
	public function getSortOrder() {
		$form_id = rgar( $this->getForm(), 'id', 0 );

		$value = GFExcelAddon::get_instance()->get_feed_meta_field( 'sort_order', $form_id, 'ASC' );

		$value = gf_apply_filters( [ 'gfexcel_output_sort_order', $form_id ], $value );

		//force either ASC or DESC
		return $value === 'ASC' ? 'ASC' : 'DESC';
	}

	/**
	 * Return the notifications for this form
	 * @return array
	 */
	public function getNotifications() {
		return \rgar( $this->form, 'notifications', [] );
	}

	/**
	 * Returns the selected notification.
	 * @return string
	 */
	public function getSelectedNotification() {
		return GFExcelAddon::get_instance()->get_feed_meta_field(
			'attachment_notification',
			rgar( $this->form, 'id', 0 ),
			''
		);
	}

	/**
	 * Get the form instance
	 * @return array|false
	 */
	public function getForm() {
		return $this->form;
	}
}
