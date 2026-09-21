<?php
/**
 * Makes an export fail on demand, so the E2E suite can assert what a failure shows to whom.
 *
 * A failed export used to print the exception message, a stack trace, absolute paths and the
 * plugin, Gravity Forms, PHP and WordPress versions to whoever requested the download (GEXPLIT-26).
 * Nothing in the suite exercised the failure path, so the first fix shipped with a gate that still
 * opened on WP_DEBUG and had to be corrected again.
 *
 * The failure is raised from `gform_include_bom_export_entries`, which GravityExport applies while
 * preparing a CSV writer: inside the try block of `AbstractPHPExcelRenderer::renderOutput()` and
 * before any download header is sent, so the request ends on the plugin's error page rather than a
 * half-written file.
 *
 * It has to be that hook specifically. Gravity Forms 3.0.2 wrapped `gf_apply_filters()` in a
 * try/catch that logs throwables and carries on, so an exception raised from any of the neighbouring
 * `gfexcel_renderer_csv_*` filters is swallowed before GravityExport can see it. This one is a plain
 * `apply_filters()`.
 *
 * Triggered per request by a query argument rather than by an option, so it cannot leak into a test
 * running in parallel.
 *
 * @package GravityExport_Lite
 */

const GK_E2E_EXPORT_FAILURE_ARG     = 'gk_e2e_force_export_failure';
const GK_E2E_EXPORT_FAILURE_MESSAGE = 'E2E forced export failure marker 8f2c1d';

add_filter(
	'gform_include_bom_export_entries',
	/**
	 * Fails the export when the request asked for it, and is a no-op otherwise.
	 *
	 * The check belongs here rather than around the registration so the callback still returns a
	 * value on the ordinary path, which is what the rest of the suite runs through.
	 *
	 * @param bool $use_bom Whether to write a byte order mark.
	 *
	 * @throws \RuntimeException When the request asked for a failure.
	 *
	 * @return bool
	 */
	function ( $use_bom ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only switch in a test-only plugin.
		$token = isset( $_GET[ GK_E2E_EXPORT_FAILURE_ARG ] ) ? (string) $_GET[ GK_E2E_EXPORT_FAILURE_ARG ] : '';

		if ( '' === $token ) {
			return $use_bom;
		}

		// The value is carried into the exception message so a test can tell its own failure from
		// one an earlier test left in the log. Reduced to a safe token first: the message reaches
		// the error page for users who may see diagnostics.
		$token = substr( preg_replace( '/[^A-Za-z0-9]/', '', $token ), 0, 32 );

		throw new \RuntimeException( GK_E2E_EXPORT_FAILURE_MESSAGE . ' ' . $token );
	},
	10,
	1
);
