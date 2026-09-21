<?php
/**
 * Asserts what decides whether a failed export shows its technical details.
 *
 * GEXPLIT-26 was fixed three times. The first fix gated the details on `WP_DEBUG`, which is often
 * left on for live sites, so an anonymous download still returned the exception message, a stack
 * trace, server paths and version details. The second added `WP_DEBUG_DISPLAY` to the same gate,
 * which is no better: those constants describe how a site reports errors, not who may read them.
 * The gate is now the `gravityforms_export_entries` capability alone.
 *
 * Both debug constants are defined as true before WordPress loads (see the spec that runs this), so
 * a gate that consulted either of them would open here. That is the point: the browser suite cannot
 * turn those constants on for a single request.
 *
 * @package GravityExport_Lite
 */

use GFExcel\Renderer\AbstractPHPExcelRenderer;

if ( ! class_exists( AbstractPHPExcelRenderer::class ) ) {
	fwrite( STDERR, "AbstractPHPExcelRenderer is not loaded.\n" );

	exit( 1 );
}

foreach ( [ 'WP_DEBUG', 'WP_DEBUG_DISPLAY' ] as $constant ) {
	if ( ! defined( $constant ) || ! constant( $constant ) ) {
		fwrite( STDERR, "$constant is not on, so this run could not tell the fixed gate from the one it replaced.\n" );

		exit( 1 );
	}
}

$gate = new ReflectionMethod( AbstractPHPExcelRenderer::class, 'can_see_diagnostics' );
$gate->setAccessible( true );

$cases = [];

wp_set_current_user( 0 );

$cases[] = [
	'a signed-out visitor is refused the details, debug constants notwithstanding',
	false === $gate->invoke( null ) ? true : 'the gate opened for an anonymous request',
];

$administrator = get_users(
	[
		'role'   => 'administrator',
		'number' => 1,
		'fields' => 'ID',
	]
);

if ( empty( $administrator ) ) {
	fwrite( STDERR, "No administrator to test the allowed case with.\n" );

	exit( 1 );
}

wp_set_current_user( (int) $administrator[0] );

$cases[] = [
	'someone who can export entries is shown the details',
	true === $gate->invoke( null ) ? true : 'the gate stayed shut for a user who can export entries',
];

// A subscriber is a signed-in user who still has no business reading server paths.
$subscriber = wp_insert_user(
	[
		'user_login' => 'gk_e2e_gate_subscriber_' . wp_generate_password( 6, false ),
		'user_pass'  => wp_generate_password(),
		'role'       => 'subscriber',
	]
);

if ( ! is_wp_error( $subscriber ) ) {
	wp_set_current_user( (int) $subscriber );

	$cases[] = [
		'a signed-in subscriber is refused the details',
		false === $gate->invoke( null ) ? true : 'the gate opened for a subscriber',
	];

	wp_set_current_user( 0 );

	require_once ABSPATH . 'wp-admin/includes/user.php';
	wp_delete_user( (int) $subscriber );
}

$failures = 0;

foreach ( $cases as list( $label, $outcome ) ) {
	if ( true === $outcome ) {
		printf( "ok   %s\n", $label );

		continue;
	}

	++$failures;

	printf( "FAIL %s\n       %s\n", $label, $outcome );
}

printf( "\n%d of %d passed\n", count( $cases ) - $failures, count( $cases ) );

exit( $failures > 0 ? 1 : 0 );
