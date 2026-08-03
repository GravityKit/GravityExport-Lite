<?php
/**
 * Plugin Name: GK Analytics Probe (test only)
 * Description: Intercepts analytics transmissions so end-to-end tests can assert on what WOULD be sent, without anything leaving the machine. Never ship this.
 */

defined( 'ABSPATH' ) || exit;

const GK_PROBE_OPTION = 'gk_probe_log';
const GK_PROBE_SECRET = 'gk-e2e-probe';

/**
 * Records analytics requests and stops them at the boundary.
 *
 * Returning a response short-circuits WordPress's HTTP layer, so the test site
 * never contacts understand.gravitykit.com. The recorded payload is what the
 * plugin actually tried to send, which is the only way a browser test can
 * assert "nothing was transmitted before consent".
 */
add_filter( 'pre_http_request', static function ( $preempt, $args, $url ) {
	if ( false === strpos( (string) $url, 'gravitykit' ) && false === strpos( (string) $url, 'understand' ) ) {
		return $preempt;
	}

	$log   = get_option( GK_PROBE_OPTION );
	$log   = is_array( $log ) ? $log : [];
	$log[] = [
		'url'  => $url,
		'body' => json_decode( (string) ( $args['body'] ?? '' ), true ),
		'at'   => microtime( true ),
	];

	update_option( GK_PROBE_OPTION, $log, false );

	return [
		'headers'  => [],
		'body'     => '',
		'response' => [ 'code' => 200, 'message' => 'OK' ],
		'cookies'  => [],
		'filename' => null,
	];
}, 10, 3 );

add_action( 'rest_api_init', static function (): void {
	$guard = static function ( \WP_REST_Request $request ) {
		return GK_PROBE_SECRET === $request->get_header( 'x-gk-probe' );
	};

	register_rest_route( 'gk-probe/v1', '/log', [
		'methods'             => 'GET',
		'permission_callback' => $guard,
		'callback'            => static function () {
			$log = get_option( GK_PROBE_OPTION );

			return rest_ensure_response( [
				'count'    => is_array( $log ) ? count( $log ) : 0,
				'requests' => is_array( $log ) ? $log : [],
				'consent'  => get_option( 'gk_analytics_consent' ),
				'salt_set' => (bool) get_option( 'gk_analytics_site_salt' ),
			] );
		},
	] );

	register_rest_route( 'gk-probe/v1', '/reset', [
		'methods'             => 'POST',
		'permission_callback' => $guard,
		'callback'            => static function () {
			global $wpdb;

			delete_option( GK_PROBE_OPTION );
			delete_option( 'gk_analytics_consent' );
			delete_option( 'gk_analytics_dropped' );

			// Release the once-per-form-per-day activation claims, or a later test
			// finds the slot already taken and sees no event.
			$claims = $wpdb->get_col(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'gk_ax_export_%'"
			);

			foreach ( $claims as $claim ) {
				delete_option( $claim );
			}

			return rest_ensure_response( [ 'reset' => true ] );
		},
	] );
} );
