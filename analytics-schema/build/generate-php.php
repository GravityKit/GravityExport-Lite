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

$product   = $argv[1] ?? 'gravityexport-lite';
$namespace = $argv[2] ?? 'GFExcel\Analytics';
$out_file  = $argv[3] ?? dirname( __DIR__, 2 ) . '/src/Analytics/Schema.php';

$core_file    = __DIR__ . '/../schema/v1/core.json';
$product_file = __DIR__ . '/../schema/v1/products/' . $product . '.json';

/** Reads and decodes a schema file, or dies loudly. */
function gk_read( string $file ): array {
	$decoded = json_decode( (string) @file_get_contents( $file ), true );
	if ( ! is_array( $decoded ) ) {
		fwrite( STDERR, "Cannot read or parse {$file}: " . json_last_error_msg() . "\n" );
		exit( 1 );
	}

	return $decoded;
}

$core     = gk_read( $core_file );
$fragment = gk_read( $product_file );

// Merge the product fragment over core. Keys that exist in both are unioned, not replaced,
// so a product can add enum values and props without restating the platform's.
$json = $core;
foreach ( [ 'props', 'enums' ] as $section ) {
	$json[ $section ] = array_merge( $core[ $section ] ?? [], $fragment[ $section ] ?? [] );
}
$json['activation'] = $fragment['activation'] ?? [];
$json['links']      = array_merge( $core['links'] ?? [], $fragment['links'] ?? [] );
$json['product']    = $fragment['product'] ?? $product;

// Expand the plugin allowlist into boolean props. Declaring them here rather than
// by hand keeps one source of truth: a plugin cannot be detected without appearing
// in the published list, and the runtime allowlist guard rejects anything else.
$json['plugin_props'] = [];
foreach ( $json['plugin_detection'] ?? [] as $group => $entries ) {
	if ( '$comment' === $group || ! is_array( $entries ) ) {
		continue;
	}

	foreach ( $entries as $key => $paths ) {
		$json['plugin_props'][ 'has_' . $key ] = [ 'group' => $group, 'paths' => $paths ];
		$json['props'][ 'has_' . $key ]        = [ 'type' => 'bool' ];
	}
}

// The allowlist is what keeps plugin detection from being a roster, so its shape
// is enforced at build time. A phpunit assertion cannot do this job: the schema is
// a compile-time constant, so static analysis folds any such check to "always true".
if ( count( $json['plugin_props'] ) > 20 ) {
	fwrite( STDERR, sprintf(
		"The plugin allowlist has %d entries. Past ~20 it stops being a few questions and becomes an inventory.\n",
		count( $json['plugin_props'] )
	) );
	exit( 1 );
}

// A hand-maintained list rots silently unless something says so. This is a
// warning rather than a failure: a stale list still produces correct data, it
// just stops asking about anything new.
$reviewed = $json['plugin_detection']['reviewed'] ?? null;
$interval = (int) ( $json['plugin_detection']['review_interval_months'] ?? 12 );

if ( $reviewed ) {
	$months = (int) floor( ( time() - strtotime( $reviewed ) ) / 2629800 );

	if ( $months >= $interval ) {
		fwrite( STDERR, sprintf(
			"NOTICE: the plugin allowlist was last reviewed %s (%d months ago, interval %d).\n"
			. "        Refresh it from data using plugin_detection.review_procedure, not from memory.\n",
			$reviewed, $months, $interval
		) );
	}
}

foreach ( $json['plugin_props'] as $prop => $spec ) {
	if ( empty( $spec['paths'] ) ) {
		fwrite( STDERR, "Plugin \"{$prop}\" declares no detection path.\n" );
		exit( 1 );
	}
}

// Every destination must sit on a host that preserves query strings. The legacy gfexcel.com
// redirect drops them, so a link routed through it arrives untagged with no error at all.
// Validating here rather than at runtime means a bad destination cannot ship.
$allowed = $json['links']['host_allowlist'] ?? [];
foreach ( $json['links']['destinations'] ?? [] as $key => $url ) {
	$ok = false;
	foreach ( $allowed as $prefix ) {
		if ( 0 === strpos( (string) $url, (string) $prefix ) ) {
			$ok = true;
			break;
		}
	}

	if ( ! $ok ) {
		fwrite( STDERR, sprintf(
			"Destination \"%s\" (%s) is not on an allowed host.\nAllowed: %s\n",
			$key,
			$url,
			implode( ', ', $allowed )
		) );
		exit( 1 );
	}
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
	'PLUGIN_PROPS'   => $json['plugin_props'],
	'THEMES'         => $json['theme_detection']['known'],
	'PRODUCT'        => $json['product'],
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
fwrite( STDOUT, "Generated {$out_file} for {$product} (" . count( $json['events'] ) . " events, " . count( $json['links']['destinations'] ?? [] ) . " destinations)\n" );
