<?php

namespace GFExcel\Migration;

use GFExcel\Addon\GFExcelAddon;
use GFExcel\Migration\Exception\MigrationException;

/**
 * Migration to upgrade the old form settings to the new {@see GFExcelAddon} single feed settings.
 * @since $ver$
 */
class SingleFeedMigration extends Migration {
	/**
	 * @inheritdoc
	 * @since $ver$
	 */
	protected static $version = '2.0.0';

	/**
	 * Mapping from the old addon settings names to the new names.
	 * @since $ver$
	 * @var string[]
	 */
	private static $addon_mapping = [
		'field_address_split_enabled' => 'field_separation_enabled',
	];

	/**
	 * Mapping from the old form settings names to the new names.
	 * @since $ver$
	 * @var string[]
	 */
	private static $feed_mapping = [
		'gfexcel_hash'                    => 'hash',
		'gfexcel_enabled_notes'           => 'enable_notes',
		'gfexcel_output_sort_field'       => 'sort_field',
		'gfexcel_output_sort_order'       => 'sort_order',
		'gfexcel_renderer_transpose'      => 'is_transposed',
		'gfexcel_custom_filename'         => 'custom_filename',
		'gfexcel_file_extension'          => 'file_extension',
		'gfexcel_attachment_notification' => 'attachment_notification',
		'gfexcel_disabled_fields'         => 'disabled_fields',
		'gfexcel_enabled_fields'          => 'export-fields/enabled',
		'gfexcel_download_count'          => 'export-fields/disabled',
		'gfexcel_download_secured'        => 'is_secured',
	];

	/**
	 * @inheritdoc
	 * @since $ver$
	 */
	public function run(): void {
		$this->migrateAddonSettings();

		$forms = \GFFormsModel::get_form_ids();
		foreach ( array_chunk( $forms, 50 ) as $form_ids ) {
			$this->updateForms( $form_ids );
		}
	}

	/**
	 * Helper method to update multiple forms by ID.
	 *
	 * @since $ver$
	 *
	 * @param array $form_ids The form ID's to update.
	 *
	 * @throws MigrationException When one of the feeds could not update.
	 */
	private function updateForms( array $form_ids ): void {
		foreach ( $form_ids as $form_id ) {
			$form = \GFAPI::get_form( $form_id );
			if ( ! $form ) {
				continue;
			}

			$this->updateForm( $form );
		}
	}

	/**
	 * Updates a single form from the old to new settings style.
	 *
	 * @since $ver$
	 *
	 * @param array $form The form object.
	 *
	 * @throws MigrationException
	 */
	private function updateForm( array $form ): void {
		$old_settings = array_filter( $form, function ( $key ) {
			return preg_match( '/^(gfexcel|gravityexport)/is', $key );
		}, ARRAY_FILTER_USE_KEY );
		$new_settings = [];

		// Predefined settings.
		foreach ( $old_settings as $key => $value ) {
			self::setValue( $new_settings, self::$feed_mapping[ $key ] ?? $key, $value );
		}

		$addon = GFExcelAddon::get_instance();
		$feed  = $addon->get_feed_by_form_id( $form_id = rgar( $form, 'id', 0 ) );
		if ( $feed ) {
			$result = \GFAPI::update_feed( rgar( $feed, 'id', 0 ), array_merge( $feed['meta'], $new_settings ),
				$form_id );
		} else {
			$result = \GFAPI::add_feed( $form_id, $new_settings, $addon->get_slug() );
		}

		if ( $result instanceof \WP_Error ) {
			throw new MigrationException( $result->get_error_message() );
		}
	}

	/**
	 * Migrates the old add-on settings to the new add-on.
	 * @since $ver$
	 */
	private function migrateAddonSettings(): void {
		// Get all the old settings.
		$settings = get_option( 'gravityformsaddon_gf-entries-in-excel_settings' );

		foreach ( self::$addon_mapping as $old => $new ) {
			// Update old to new setting name, if the new setting name isn't already present.
			if ( isset( $settings[ $old ] ) && ! isset( $settings[ $new ] ) ) {
				$settings[ $new ] = $settings[ $old ];
			}

			// Remove old setting name.
			unset( $settings[ $old ] );
		}

		GFExcelAddon::get_instance()->update_plugin_settings( $settings );
	}

	/**
	 * Helper method to set a value with nested keys.
	 *
	 * Gratefully borrowed from Laravel Arr::set().
	 *
	 * @param array $array The array to update.
	 * @param string $key The (possibly nested) key.
	 * @param mixed $value The value to set.
	 */
	private static function setValue( array &$array, string $key, $value ): void {
		$keys = explode( '/', $key );

		foreach ( $keys as $i => $key ) {
			if ( count( $keys ) === 1 ) {
				break;
			}

			unset( $keys[ $i ] );

			// If the key doesn't exist at this depth, we will just create an empty array
			// to hold the next value, allowing us to create the arrays to hold final
			// values at the correct depth. Then we'll keep digging into the array.
			if ( ! isset( $array[ $key ] ) || ! is_array( $array[ $key ] ) ) {
				$array[ $key ] = [];
			}

			$array = &$array[ $key ];
		}
		$array[ array_shift( $keys ) ] = $value;
	}
}
