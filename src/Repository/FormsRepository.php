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
	 * Returns the first selected notification.
	 * @return string
	 */
	public function getSelectedNotification(): string {
		$ids = $this->getSelectedNotifications();

		return $ids[0] ?? '';
	}

	/**
	 * Returns the selected notification IDs the single-entry export is attached to.
	 * @since TBD
	 * @return string[] The notification IDs.
	 */
	public function getSelectedNotifications(): array {
		$form_id = (int) \rgar( $this->form, 'id', 0 );

		$ids = $this->normalizeNotificationIds(
			$this->addon->get_feed_meta_field( 'attachment_notification', $form_id, '' )
		);

		/**
		 * Replaces the stored notification selection with the one owned by another product.
		 *
		 * The last callback wins; this stage establishes the selection rather than modifying
		 * it. Runs before `gk/gravityexport/notification/attachment-ids`, so callbacks on that
		 * filter receive the complete selection. Use that filter to add or remove IDs.
		 *
		 * @since TBD
		 *
		 * @param string[] $ids     The notification IDs stored by GravityExport Lite.
		 * @param int      $form_id The form ID.
		 */
		$ids = $this->normalizeNotificationIds(
			gf_apply_filters( [ 'gk/gravityexport/notification/attachment-source-ids', $form_id ], $ids, $form_id )
		);

		/**
		 * Modifies the notification IDs the single-entry export is attached to.
		 *
		 * @since TBD
		 *
		 * @param string[] $ids     The selected notification IDs.
		 * @param int      $form_id The form ID.
		 */
		$ids = gf_apply_filters( [ 'gk/gravityexport/notification/attachment-ids', $form_id ], $ids, $form_id );

		return $this->normalizeNotificationIds( $ids );
	}

	/**
	 * Normalizes a stored or filtered notification selection to a list of ID strings.
	 * @since TBD
	 * @param mixed $value The raw value; a single ID or a list of IDs.
	 * @return string[] The unique, non-empty notification IDs.
	 */
	private function normalizeNotificationIds( $value ): array {
		if ( ! is_array( $value ) ) {
			$value = [ $value ];
		}

		$ids = [];

		foreach ( $value as $id ) {
			if ( ! is_scalar( $id ) ) {
				$this->addon->log_debug( __METHOD__ . '(): Skipping a non-scalar notification ID; check the stored attachment notification selection.' );

				continue;
			}

			$id = (string) $id;

			if ( $id === '' || in_array( $id, $ids, true ) ) {
				continue;
			}

			$ids[] = $id;
		}

		return $ids;
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
