<?php
/**
 * Seeds the fixture the end-to-end suite exercises: a form, two entries, and an
 * enabled export feed. Run via `wp eval-file`; prints the ids the specs need.
 *
 * Not loaded by WordPress. This file is a script, not a plugin.
 */

$existing = GFAPI::get_forms();
foreach ( $existing as $form ) {
	if ( 'E2E Export Form' === $form['title'] ) {
		GFAPI::delete_form( $form['id'] );
	}
}

$form_id = GFAPI::add_form( [
	'title'  => 'E2E Export Form',
	'fields' => [
		[ 'id' => 1, 'type' => 'text', 'label' => 'Name' ],
		[ 'id' => 2, 'type' => 'email', 'label' => 'Email' ],
	],
] );

if ( is_wp_error( $form_id ) ) {
	echo 'FORM_ERROR=' . $form_id->get_error_message() . "\n";

	return;
}

GFAPI::add_entry( [ 'form_id' => $form_id, '1' => 'Ada Lovelace', '2' => 'ada@example.com' ] );
GFAPI::add_entry( [ 'form_id' => $form_id, '1' => 'Alan Turing', '2' => 'alan@example.com' ] );

// The router resolves a download by matching this hash in FEED meta, so it has
// to live there. GFExcel::url() falls back to form meta, which is why a
// form-meta-only hash produces a working-looking URL that then 404s.
$hash = bin2hex( random_bytes( 32 ) );

GFExcel\Addon\GravityExportAddon::get_instance()->save_feed_settings( 0, $form_id, [
	'enable_download_url' => '1',
	'file_extension'      => 'csv',
	'is_secure'           => '0',
	'hash'                => $hash,
] );

echo 'FORM_ID=' . $form_id . "\n";
echo 'DOWNLOAD_URL=' . GFExcel\GFExcel::url( $form_id ) . "\n";
