<?php

namespace GFExcel\Repository;

use GFAPI;
use GFExcel\Addon\GravityExportAddon;

/**
 * Forms repository for Gravity Forms' forms.
 */
class FormsRepository {
	/** @var array|false */
	private $form;

	/**
	 * The Gravity Export addon.
	 * @since 2.0.0
	 * @var GravityExportAddon
	 */
	private $addon;

	/**
	 * Creates the Forms Repository.
	 *
	 * @param string|int $form_id The form id.
	 */
	public function __construct( $form_id ) {
		$this->form  = $form_id ? GFAPI::get_form( $form_id ) : [];
		$this->addon = GravityExportAddon::get_instance();
	}

	/**
	 * Whether to show notes based on setting or filter.
	 * @return bool
	 */
	public function showNotes(): bool {
		// Plugin has global setting.
		$plugin_setting = (bool) $this->addon->get_plugin_setting( 'notes_enabled' );

		// Form can overwrite that setting.
		$form_id = \rgar( $this->getForm(), 'id', 0 );
		$setting = $this->addon->get_feed_meta_field( 'enable_notes', $form_id, $plugin_setting );

		// Hook can overwrite the setting too.
		return (bool) gf_apply_filters( [ 'gfexcel_field_notes_enabled', $form_id ], $setting, $form_id );
	}

	/**
	 * Get field to sort the data by
	 * @return mixed
	 */
	public function getSortField() {
		$form_id = \rgar( $this->getForm(), 'id', 0 );

		$value = $this->addon->get_feed_meta_field( 'sort_field', $form_id, 'date_created' );

		return gf_apply_filters( [ 'gfexcel_output_sort_field', $form_id ], $value );
	}

	/**
	 * In what order should the data be sorted.
	 * @return string The sort order.
	 */
	public function getSortOrder(): string {
		$form_id = \rgar( $this->getForm(), 'id', 0 );

		$value = $this->addon->get_feed_meta_field( 'sort_order', $form_id, 'ASC' );

		$value = gf_apply_filters( [ 'gfexcel_output_sort_order', $form_id ], $value );

		//force either ASC or DESC
		return $value === 'ASC' ? 'ASC' : 'DESC';
	}

	/**
	 * Return the notifications for this form
	 * @return array
	 */
	public function getNotifications(): array {
		return \rgar( $this->getForm(), 'notifications', [] );
	}

	/**
	 * Returns the selected notification.
	 * @return string
	 */
	public function getSelectedNotification(): string {
		return (string) $this->addon->get_feed_meta_field(
			'attachment_notification',
			\rgar( $this->form, 'id', 0 ),
			''
		);
	}

	/**
	 *
	 * @since 2.0.0
	 * @return bool Whether the form should be transposed.
	 */
	public function isTransposed(): bool {
		$form_id = \rgar( $this->getForm(), 'id', 0 );
		$value   = $this->addon->get_feed_meta_field( 'is_transposed', $form_id, false );

		return gf_apply_filters( [ 'gfexcel_renderer_transpose', $form_id ], $value );
	}

	/**
	 * Get the form instance
	 * @return array|false
	 */
	public function getForm() {
		return $this->form;
	}
}
