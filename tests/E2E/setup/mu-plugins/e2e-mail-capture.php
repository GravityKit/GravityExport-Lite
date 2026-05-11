<?php
/**
 * Plugin Name: E2E Mail Capture
 * Description: Short-circuits wp_mail and exposes captured messages via REST. Used only by Playwright tests.
 *
 * Auth: same X-E2E-TEST-TOKEN header as @gravitykit/e2e-fixtures (constant 'gravitykit-e2e-test').
 *
 * @package GravityExport_Lite_E2E
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'GK_E2E_MAIL_TOKEN' ) ) {
	define( 'GK_E2E_MAIL_TOKEN', 'gravitykit-e2e-test' );
}

if ( ! function_exists( 'gk_e2e_mail_dir' ) ) {
	/**
	 * Resolve the capture directory inside uploads.
	 *
	 * @return string Absolute path with trailing slash.
	 */
	function gk_e2e_mail_dir() {
		$uploads = wp_upload_dir( null, false );
		$dir     = trailingslashit( $uploads['basedir'] ) . 'e2e-mail-capture/';

		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		return $dir;
	}
}

// Short-circuit wp_mail: write payload + attachments to disk, return true.
add_filter(
	'pre_wp_mail',
	static function ( $null, $atts ) {
		$dir = gk_e2e_mail_dir();

		$to          = (array) ( $atts['to'] ?? [] );
		$subject     = (string) ( $atts['subject'] ?? '' );
		$message     = (string) ( $atts['message'] ?? '' );
		$headers     = (array) ( $atts['headers'] ?? [] );
		$attachments = (array) ( $atts['attachments'] ?? [] );

		$attachment_records = [];

		$copy_dir = $dir . 'attachments/';
		if ( ! is_dir( $copy_dir ) ) {
			wp_mkdir_p( $copy_dir );
		}

		foreach ( $attachments as $path ) {
			if ( ! is_string( $path ) || ! is_readable( $path ) ) {
				continue;
			}

			$size  = filesize( $path );
			$mime  = function_exists( 'mime_content_type' ) ? mime_content_type( $path ) : null;
			$sha1  = sha1_file( $path );
			$base  = basename( $path );
			// Copy the attachment to a stable location: notification senders
			// often delete the source file after wp_mail returns
			// (e.g. GravityExport's gform_after_email cleanup), so tests
			// need an independent copy to read full bytes from.
			$copy  = $copy_dir . $sha1 . '-' . $base;
			if ( ! file_exists( $copy ) ) {
				@copy( $path, $copy );
			}

			$attachment_records[] = [
				'filename' => $base,
				'size'     => $size === false ? 0 : (int) $size,
				'mime'     => $mime ?: '',
				'sha1'     => $sha1,
				'path'     => $copy,
			];
		}

		$record = [
			'id'          => uniqid( 'mail_', true ),
			'timestamp'   => microtime( true ),
			'to'          => $to,
			'subject'     => $subject,
			'message'     => $message,
			'headers'     => $headers,
			'attachments' => $attachment_records,
		];

		$filename = $dir . sprintf( '%010d-%s.json', floor( $record['timestamp'] ), $record['id'] );
		file_put_contents( $filename, wp_json_encode( $record, JSON_UNESCAPED_SLASHES ) );

		// Return non-null to short-circuit the real wp_mail.
		return true;
	},
	10,
	2
);

// REST endpoints — list and clear captured emails.
add_action(
	'rest_api_init',
	static function () {
		register_rest_route(
			'gk-e2e/v1',
			'/mail',
			[
				'methods'             => 'GET',
				'permission_callback' => 'gk_e2e_mail_check_permission',
				'callback'            => static function () {
					$dir   = gk_e2e_mail_dir();
					$files = glob( $dir . '*.json' ) ?: [];
					sort( $files );

					$messages = [];

					foreach ( $files as $file ) {
						$decoded = json_decode( (string) file_get_contents( $file ), true );

						if ( is_array( $decoded ) ) {
							$messages[] = $decoded;
						}
					}

					return new WP_REST_Response( [ 'messages' => $messages ], 200 );
				},
			]
		);

		register_rest_route(
			'gk-e2e/v1',
			'/mail',
			[
				'methods'             => 'DELETE',
				'permission_callback' => 'gk_e2e_mail_check_permission',
				'callback'            => static function () {
					$dir   = gk_e2e_mail_dir();
					$files = glob( $dir . '*.json' ) ?: [];

					foreach ( $files as $file ) {
						@unlink( $file );
					}

					return new WP_REST_Response( [ 'cleared' => count( $files ) ], 200 );
				},
			]
		);
	}
);

if ( ! function_exists( 'gk_e2e_mail_check_permission' ) ) {
	function gk_e2e_mail_check_permission( WP_REST_Request $request ) {
		return GK_E2E_MAIL_TOKEN === $request->get_header( 'X-E2E-TEST-TOKEN' );
	}
}
