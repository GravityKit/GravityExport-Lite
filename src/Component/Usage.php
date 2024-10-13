<?php

namespace GFExcel\Component;

use GFExcel\GFExcel;

/**
 * Usage component that handles all the plugin usage statistics and notifications.
 * @since 2.0.0
 */
final class Usage {
	/**
	 * Get the current usage count from the plugin repo.
	 * Info is cached for a week.
	 *
	 * @param bool $number_format Whether to return a formatted number.
	 *
	 * @return string The target.
	 */
	public function getCount( bool $number_format = true ): string {
		if ( ! $active_installs = get_transient( GFExcel::$slug . '-active_installs' ) ) {
			if ( ! function_exists( 'plugins_api' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
			}

			/** @var object|array $data */
			$data = plugins_api( 'plugin_information', [
				'slug'   => 'gf-entries-in-excel',
				'fields' => [ 'active_installs' => true ],
			] );

			if ( $data instanceof \WP_Error || ! is_object( $data ) || ! isset( $data->active_installs ) ) {
				return __( 'countless', 'gk-gravityexport-lite' );
			}
			$active_installs = $data->active_installs;
			set_transient( GFExcel::$slug . '-active_installs', $active_installs, WEEK_IN_SECONDS );
		}

		return (string) $number_format ? number_format_i18n( $active_installs ) : $active_installs;
	}

	/**
	 * Get the current usage count from the plugin repo.
	 * Info is cached for a week.
	 *
	 * @param bool $number_format Whether to return a formatted number.
	 *
	 * @return string The target.
	 */
	public function getTarget( bool $number_format = true ): string {
		$current_count = $this->getCount( false );
		if ( $current_count === __( 'countless', 'gk-gravityexport-lite' ) ) {
			return __( 'even more', 'gk-gravityexport-lite' );
		}

		// What step should we reach for?
		$next_level = 1000;

		$usage_target = ( ( (int) $current_count / $next_level ) + 1 ) * $next_level;

		return (string) $number_format ? number_format_i18n( $usage_target ) : $usage_target;
	}
}
