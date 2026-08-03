<?php
/**
 * Generates a locally-namespaced PHP constant class from analytics-schema.json.
 *
 * Each consumer runs this at BUILD time into its OWN namespace, so Strauss has
 * nothing to prefix and the two sides cannot drift: same JSON in, same values out.
 *
 * Usage: php analytics-schema/build/generate-php.php <namespace> <output-file>
 *   e.g. php analytics-schema/build/generate-php.php 'GFExcel\Analytics' src/Analytics/Schema.php
 */

$schema_file = __DIR__ . '/../schema/v1/analytics-schema.json';
$namespace   = $argv[1] ?? 'GFExcel\Analytics';
$out_file    = $argv[2] ?? dirname( __DIR__, 2 ) . '/src/Analytics/Schema.php';

$json = json_decode( (string) file_get_contents( $schema_file ), true );
if ( ! is_array( $json ) ) {
	fwrite( STDERR, "Cannot parse {$schema_file}: " . json_last_error_msg() . "\n" );
	exit( 1 );
}

/** Renders a value as PHP 7.2-compatible source. */
function gk_render( $value, int $indent = 1 ): string {
	$pad = str_repeat( "\t", $indent );
	if ( is_array( $value ) ) {
		$is_list = array_keys( $value ) === range( 0, count( $value ) - 1 );
		$lines   = [];
		foreach ( $value as $k => $v ) {
			$lines[] = $is_list
				? $pad . "\t" . gk_render( $v, $indent + 1 ) . ','
				: $pad . "\t" . var_export( (string) $k, true ) . ' => ' . gk_render( $v, $indent + 1 ) . ',';
		}
		return $lines ? "[\n" . implode( "\n", $lines ) . "\n" . $pad . ']' : '[]';
	}
	if ( null === $value ) {
		return 'null';
	}
	if ( is_bool( $value ) ) {
		return $value ? 'true' : 'false';
	}
	if ( is_int( $value ) || is_float( $value ) ) {
		return (string) $value;
	}
	return var_export( (string) $value, true );
}

// Strip $comment and notes keys; they document the JSON, not the runtime.
$strip = static function ( array $a ) use ( &$strip ): array {
	$out = [];
	foreach ( $a as $k => $v ) {
		if ( '$comment' === $k || 'notes' === $k ) {
			continue;
		}
		$out[ $k ] = is_array( $v ) ? $strip( $v ) : $v;
	}
	return $out;
};
$json = $strip( $json );

$consts = [
	'SCHEMA_VERSION' => $json['schema_version'],
	'EVENTS'         => $json['events'],
	'SUPER_PROPS'    => $json['super_props'],
	'PROPS'          => $json['props'],
	'ENUMS'          => $json['enums'],
	'ACTIVATION'     => $json['activation'],
	'IDENTITY'       => $json['identity'],
	'CONSENT'        => $json['consent'],
	'ATTRIBUTION'    => $json['attribution'],
	'TRANSPORT'      => $json['transport'],
	'SCRUB'          => $json['scrub'],
	'LINKS'          => $json['links'],
];

$body = '';
foreach ( $consts as $name => $value ) {
	$body .= "\n\tpublic const {$name} = " . gk_render( $value ) . ";\n";
}

$source = <<<PHP
<?php
/**
 * GENERATED FILE — DO NOT EDIT.
 *
 * Source:    analytics-schema/schema/v1/analytics-schema.json
 * Generator: analytics-schema/build/generate-php.php
 *
 * Regenerate with `composer analytics:generate`. CI fails if this file differs
 * from a fresh generation, which is what stops Lite and Foundation drifting.
 */

namespace {$namespace};

/**
 * The analytics wire contract, as constants.
 *
 * @since \$ver\$
 */
final class Schema {{$body}
	/**
	 * Returns the legal values for a closed enum, or null when the name is not a closed enum.
	 *
	 * @since \$ver\$
	 *
	 * @param string \$enum The enum name.
	 *
	 * @return string[]|null The legal values.
	 */
	public static function enum( string \$enum ): ?array {
		return self::ENUMS[ \$enum ] ?? null;
	}

	/**
	 * Returns the closed-enum name backing a property, or null when the property is not enum-typed.
	 *
	 * @since \$ver\$
	 *
	 * @param string \$prop The property key.
	 *
	 * @return string|null The enum name.
	 */
	public static function enumForProp( string \$prop ): ?string {
		\$spec = self::PROPS[ \$prop ] ?? self::SUPER_PROPS[ \$prop ] ?? null;

		return \$spec['enum'] ?? null;
	}
}

PHP;

if ( ! is_dir( dirname( $out_file ) ) ) {
	mkdir( dirname( $out_file ), 0755, true );
}
file_put_contents( $out_file, $source );
fwrite( STDOUT, "Generated {$out_file} (" . count( $json['events'] ) . " events)\n" );
