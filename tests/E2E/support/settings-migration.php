<?php
/**
 * Asserts what the 2.0 settings migration does with the pre-2.0 add-on settings.
 *
 * The migration runs on every new install, and again on any site where a run stopped before it
 * recorded its version. A site without the pre-2.0 settings must come through it with no PHP notice
 * and with the settings saved since intact; a site with them must get them moved, through the
 * Gravity Forms save the unit tests cannot load.
 *
 * The two options the cases use are put back as they were at the end, and the test form is deleted.
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

	// Gravity Forms caches the add-on settings for the request, and each case changes them underneath it.
	if ( property_exists( GFAddOn::class, '_plugin_settings' ) ) {
		$cache = new ReflectionProperty( GFAddOn::class, '_plugin_settings' );
		$cache->setAccessible( true );
		$cache->setValue( null, [] );
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

/** The add-on's stored settings, or `false` when nothing is stored. The test site does not encrypt them. */
$stored = static function () use ( $new_option ) {
	return get_option( $new_option );
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
		'field_address_split_enabled' => '1',
		'notes_enabled'               => '1',
	],
	$settings
);
$cases[] = [
	'settings saved since are kept when the pre-2.0 settings are still there',
	[] === $raised && $settings == $stored() ? true : 'raised: ' . implode( '; ', $raised ) . '; stored: ' . wp_json_encode( $stored() ),
];

// Runs after a case that cached saved settings, so it also fails if that cache is not cleared.
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

// The same for each form: its pre-2.0 keys stay on the form after they are moved to a feed.
$form_id = GFAPI::add_form(
	[
		'title'                   => 'Settings migration check',
		'fields'                  => [],
		'gfexcel_custom_filename' => 'pre-2-0-name',
	]
);

$update_form = new ReflectionMethod( SingleFeedMigration::class, 'updateForm' );
$update_form->setAccessible( true );

/** The form's feed, or `null` when it has none. */
$feed = static function () use ( $addon, $form_id ): ?array {
	$feeds = GFAPI::get_feeds( null, $form_id, $addon->get_slug() );

	return is_array( $feeds ) ? $feeds[0] : null;
};

try {
	$update_form->invoke( new SingleFeedMigration(), GFAPI::get_form( $form_id ) );
	$filename = $feed()['meta']['custom_filename'] ?? null;
	$cases[]  = [
		'the pre-2.0 form settings are saved on a new feed',
		'pre-2-0-name' === $filename ? true : 'filename: ' . wp_json_encode( $filename ),
	];

	if ( $feed() ) {
		GFAPI::update_feed( $feed()['id'], array_merge( $feed()['meta'], [ 'custom_filename' => 'saved-since' ] ) );
	}

	$update_form->invoke( new SingleFeedMigration(), GFAPI::get_form( $form_id ) );
	$filename = $feed()['meta']['custom_filename'] ?? null;
	$cases[]  = [
		'feed settings saved since are kept when the migration runs again',
		'saved-since' === $filename ? true : 'filename: ' . wp_json_encode( $filename ),
	];
} catch ( Throwable $e ) {
	$cases[] = [ 'the form settings cases ran without an error', get_class( $e ) . ': ' . $e->getMessage() ];
} finally {
	// Deleting the form deletes its feeds too.
	GFAPI::delete_form( $form_id );
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
