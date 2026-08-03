<?php
/**
 * Fails the build when an outbound gravitykit.com URL is written outside the Links class.
 *
 * Hand-written links are how the ecosystem ended up with three incompatible UTM
 * schemes and untagged links nobody noticed. Centralising the builder only helps
 * if bypassing it is caught, so this runs in CI.
 *
 * Usage: php bin/check-links.php
 */

$root = dirname( __DIR__ );

// Links.php owns the builder; Schema.php is generated from the schema JSON.
$allowed_files = [
	'src/Links/Links.php',
	'src/Analytics/Schema.php',
];

// The analytics ingest host is not a link.
$allowed_patterns = [ 'understand.gravitykit.com' ];

$violations = [];

$iterator = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator( $root . '/src', RecursiveDirectoryIterator::SKIP_DOTS )
);

foreach ( $iterator as $file ) {
	if ( 'php' !== $file->getExtension() ) {
		continue;
	}

	$relative = str_replace( $root . '/', '', $file->getPathname() );
	if ( in_array( $relative, $allowed_files, true ) ) {
		continue;
	}

	foreach ( file( $file->getPathname() ) as $number => $line ) {
		if ( 1 !== preg_match( '#https?://[^\s\'"]*(gravitykit|gfexcel)\.com#', $line ) ) {
			continue;
		}

		// A docblock or comment reference is documentation, not a rendered link.
		if ( 1 === preg_match( '#^\s*(\*|//|/\*)#', $line ) ) {
			continue;
		}

		foreach ( $allowed_patterns as $pattern ) {
			if ( false !== strpos( $line, $pattern ) ) {
				continue 2;
			}
		}

		$violations[] = sprintf( '%s:%d  %s', $relative, $number + 1, trim( $line ) );
	}
}

if ( $violations ) {
	fwrite( STDERR, "Outbound URLs must be built with GFExcel\\Links\\Links so they carry campaign tagging:\n\n" );
	foreach ( $violations as $violation ) {
		fwrite( STDERR, "  {$violation}\n" );
	}
	fwrite( STDERR, "\n" . count( $violations ) . " violation(s).\n" );
	exit( 1 );
}

fwrite( STDOUT, "All outbound links are built through Links.\n" );
