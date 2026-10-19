<?php
/**
 * Asserts what the 2.0 settings migration does with the pre-2.0 add-on settings.
 *
 * The migration runs on every new install, and again on any site where a run stopped before it
 * recorded its version. A site without the pre-2.0 settings must come through it with no PHP notice
 * and with the settings saved since intact; a site with them must get them moved, through the
 * Gravity Forms save the unit tests cannot load.
 *
 * The two options the cases use are put back as they were at the end.
 *
 * @package GravityExport_Lite
 */

use GFExcel\Addon\GravityExportAddon;
use GFExcel\Migration\Migration\SingleFeedMigration;

foreach ( [ GravityExportAddon::class, SingleFeedMigration::class ] as $class ) {
	if ( ! class_exists( $class ) ) {
		fwrite( STDERR, "$class is not loaded.\n" );

		exit( 1 );
	}
}

$addon      = GravityExportAddon::get_instance();
$old_option = 'gravityformsaddon_gf-entries-in-excel_settings';
$new_option = 'gravityformsaddon_' . $addon->get_slug() . '_settings';
$absent     = new stdClass();
$saved      = [
	$old_option => get_option( $old_option, $absent ),
	$new_option => get_option( $new_option, $absent ),
];

$migrate = new ReflectionMethod( SingleFeedMigration::class, 'migrateAddonSettings' );
$migrate->setAccessible( true );

/**
 * Stores the two options for one case, runs the migration step, and reports what it raised.
 *
 * @param mixed $old What the pre-2.0 option holds, or `$absent` for no row.
 * @param mixed $new What the add-on's own option holds, or `$absent` for no row.
 *
 * @return string[] Every notice, warning, deprecation or error the step raised.
 */
$run = static function ( $old, $new ) use ( $migrate, $old_option, $new_option, $absent ): array {
	foreach ( [ $old_option => $old, $new_option => $new ] as $option => $value ) {
		$absent === $value ? delete_option( $option ) : update_option( $option, $value );
	}

	$raised = [];
	set_error_handler(
		static function ( int $level, string $message ) use ( &$raised ): bool {
			$raised[] = $message;

			return true;
		}
	);

	try {
		$migrate->invoke( new SingleFeedMigration() );
	} catch ( Throwable $e ) {
		$raised[] = get_class( $e ) . ': ' . $e->getMessage();
	} finally {
		restore_error_handler();
	}

	return $raised;
};

/** The add-on's settings as the plugin reads them, or `false` when nothing is stored. */
$stored = static function () use ( $addon, $new_option ) {
	return $addon->get_encryptor()->decrypt( get_option( $new_option ) );
};

// Specs run in parallel and exports read these settings, so every stored value is the default.
$cases = [];

$raised  = $run( $absent, $absent );
$cases[] = [
	'a new install raises nothing and stores nothing',
	[] === $raised && false === $stored() ? true : 'raised: ' . implode( '; ', $raised ) . '; stored: ' . wp_json_encode( $stored() ),
];

$settings = [
	'use_admin_label'          => '0',
	'field_separation_enabled' => '0',
	'hyperlinks_enabled'       => '1',
];
$raised   = $run( $absent, $settings );
$cases[]  = [
	'settings saved since are kept when the migration runs again',
	[] === $raised && $settings == $stored() ? true : 'raised: ' . implode( '; ', $raised ) . '; stored: ' . wp_json_encode( $stored() ),
];

$raised  = $run(
	[
		'field_address_split_enabled' => '0',
		'notes_enabled'               => '0',
	],
	$absent
);
$cases[] = [
	'the pre-2.0 settings are saved under the new name',
	[] === $raised && [ 'notes_enabled' => '0', 'field_separation_enabled' => '0' ] == $stored()
		? true
		: 'raised: ' . implode( '; ', $raised ) . '; stored: ' . wp_json_encode( $stored() ),
];

$raised  = $run( 'text', $absent );
$cases[] = [
	'text in the pre-2.0 option is left alone, without an error',
	[] === $raised && false === $stored() ? true : 'raised: ' . implode( '; ', $raised ) . '; stored: ' . wp_json_encode( $stored() ),
];

foreach ( $saved as $option => $value ) {
	$absent === $value ? delete_option( $option ) : update_option( $option, $value );
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
