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

add_action(
	'plugins_loaded',
	function () {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only switch in a test-only plugin.
		if ( empty( $_GET[ GK_E2E_EXPORT_FAILURE_ARG ] ) ) {
			return;
		}

		add_filter(
			'gform_include_bom_export_entries',
			function ( $use_bom ) {
				throw new \RuntimeException( GK_E2E_EXPORT_FAILURE_MESSAGE );
			},
			10,
			1
		);
	}
);
