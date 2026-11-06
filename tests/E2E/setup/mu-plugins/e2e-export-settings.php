<?php
/**
 * Sets "Multiple Columns" and numeric prices for one download request, so a spec can export with
 * them on or off without changing GravityExport Lite's global settings.
 *
 * The global settings are shared by every spec, and the suite runs specs in parallel, so a spec that
 * changed them would change the exports of whatever else is running. These query arguments affect
 * only the request they are sent with:
 *
 * - `gk_e2e_field_separation=1|0` answers the `gfexcel_field_separated` filter ("Multiple Columns").
 * - `gk_e2e_numeric_price=1|0` answers the `gfexcel_numeric_price` filter.
 *
 * @package GravityExport_Lite
 */

foreach (
	[
		'gk_e2e_field_separation' => 'gfexcel_field_separated',
		'gk_e2e_numeric_price'    => 'gfexcel_numeric_price',
	] as $gk_e2e_arg => $gk_e2e_filter
) {
	add_filter(
		$gk_e2e_filter,
		static function ( $value ) use ( $gk_e2e_arg ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Test-only switch, no state change.
			if ( ! isset( $_GET[ $gk_e2e_arg ] ) ) {
				return $value;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return '1' === (string) wp_unslash( $_GET[ $gk_e2e_arg ] );
		},
		PHP_INT_MAX
	);
}
