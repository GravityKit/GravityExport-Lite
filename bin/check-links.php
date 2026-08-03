<?php
/**
 * Fails the build when an outbound gravitykit.com URL is written outside the Links class.
 *
 * Hand-written links are how the ecosystem ended up with four incompatible UTM schemes
 * and untagged links nobody noticed. Centralising the builder only helps if bypassing it
 * is caught, so this runs in CI.
 *
 * PHP files are tokenised rather than pattern-matched. A line-anchored "is this a comment"
 * regex misses a URL after trailing code on a comment line, and misses heredocs entirely;
 * the tokeniser knows exactly which bytes are a comment and which are a string.
 *
 * Usage: php bin/check-links.php
 */

$root = dirname( __DIR__ );

// Directories that can contain a rendered link. Products keep URLs in templates and views,
// not only in src/ — GravityView's worst offender is a view file.
$scan_roots = [ 'src', 'templates', 'views', 'public', 'assets' ];

// Links.php owns the builder; Schema.php is generated from the schema JSON.
$allowed_files = [
	'src/Links/Links.php',
	'src/Analytics/Schema.php',
];

// The analytics ingest host is not a link.
$allowed_patterns = [ 'understand.gravitykit.com' ];

// Case-insensitive, and matches a bare host too, so a URL assembled by
// concatenation or written in mixed case cannot slip past the gate.
$pattern    = '#(https?://[^\s\'"`]*)?(gravitykit|gfexcel)\.com#i';
$violations = [];

/** Returns true when the line is exempt by an allowlisted substring. */
$exempt = static function ( string $line ) use ( $allowed_patterns ): bool {
	foreach ( $allowed_patterns as $allowed ) {
		if ( false !== strpos( $line, $allowed ) ) {
			return true;
		}
	}

	return false;
};

foreach ( $scan_roots as $scan_root ) {
	$path = $root . '/' . $scan_root;
	if ( ! is_dir( $path ) ) {
		continue;
	}

	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $path, RecursiveDirectoryIterator::SKIP_DOTS )
	);

	foreach ( $iterator as $file ) {
		$extension = strtolower( $file->getExtension() );
		if ( ! in_array( $extension, [ 'php', 'js', 'jsx', 'html', 'twig' ], true ) ) {
			continue;
		}

		$relative = str_replace( $root . '/', '', $file->getPathname() );
		if ( in_array( $relative, $allowed_files, true ) ) {
			continue;
		}

		$contents = (string) file_get_contents( $file->getPathname() );

		if ( 'php' === $extension ) {
			// Only code and markup can render a link; comments cannot.
			foreach ( token_get_all( $contents ) as $token ) {
				if ( ! is_array( $token ) ) {
					continue;
				}

				list( $id, $text, $line ) = $token;

				if ( in_array( $id, [ T_COMMENT, T_DOC_COMMENT ], true ) ) {
					continue;
				}

				if ( 1 !== preg_match( $pattern, $text ) || $exempt( $text ) ) {
					continue;
				}

				$violations[] = sprintf( '%s:%d  %s', $relative, $line, trim( explode( "\n", $text )[0] ) );
			}

			continue;
		}

		// Non-PHP: strip block and line comments, then scan what is left.
		$stripped = preg_replace( [ '#/\*.*?\*/#s', '#(^|\s)//[^\n]*#' ], '', $contents );

		foreach ( explode( "\n", (string) $stripped ) as $number => $line ) {
			if ( 1 !== preg_match( $pattern, $line ) || $exempt( $line ) ) {
				continue;
			}

			$violations[] = sprintf( '%s:%d  %s', $relative, $number + 1, trim( $line ) );
		}
	}
}

if ( $violations ) {
	fwrite( STDERR, "Outbound URLs must be built with the Links class so they carry campaign tagging:\n\n" );
	foreach ( $violations as $violation ) {
		fwrite( STDERR, "  {$violation}\n" );
	}
	fwrite( STDERR, "\n" . count( $violations ) . " violation(s).\n" );
	exit( 1 );
}

fwrite( STDOUT, "All outbound links are built through Links.\n" );
