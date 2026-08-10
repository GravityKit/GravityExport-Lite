<?php
/**
 * Rewrites PHP deprecations in the Strauss-prefixed vendor tree.
 *
 * Strauss rewrites these files under our `GFExcel\Vendor\` prefix, so any deprecation they emit reads as our
 * code in a customer's error log. Both dependencies below are pinned to versions that predate the deprecation
 * and have no fixed release we can move to without an API break, so the shipped copy is patched instead.
 *
 * - league/container 3.4.1 writes `Type $param = null`, implicitly nullable, which PHP 8.4 deprecates at
 *   compile time — so it fires on every request that boots the container, not only when the method is called.
 *   3.4.1 is the last 3.x release. Moving to 4.x drops the third `$shared` argument from `Container::add()`,
 *   which `AbstractServiceProvider::addAction()` and `AddOnProvider::addAutoStart()` both pass, so it needs a
 *   compatibility shim and a review of every add-on built against the current service-provider API.
 * - phpspreadsheet 1.19.0 uses `${var}` string interpolation, deprecated in PHP 8.2. It reaches customers
 *   through the `PhpOffice\PhpSpreadsheet\*` compatibility aliases registered in gfexcel.php.
 *
 * Runs during `composer build`, after Strauss has written build/vendor_prefixed/ and before the autoloader is
 * generated. Fails the build if a target signature no longer matches, so a dependency bump cannot silently
 * ship unpatched.
 *
 * @since TBD
 */

$vendor_dir = __DIR__ . '/../build/vendor_prefixed';

$replacements = [
	'league/container/src/Container.php'                            => [
		[
			"        DefinitionAggregateInterface      \$definitions = null,\n"
			. "        ServiceProviderAggregateInterface \$providers = null,\n"
			. "        InflectorAggregateInterface       \$inflectors = null\n",
			"        ?DefinitionAggregateInterface      \$definitions = null,\n"
			. "        ?ServiceProviderAggregateInterface \$providers = null,\n"
			. "        ?InflectorAggregateInterface       \$inflectors = null\n",
		],
		[
			'public function add(string $id, $concrete = null, bool $shared = null)',
			'public function add(string $id, $concrete = null, ?bool $shared = null)',
		],
		[
			'public function inflector(string $type, callable $callback = null)',
			'public function inflector(string $type, ?callable $callback = null)',
		],
	],
	'league/container/src/Inflector/Inflector.php'                  => [
		[
			'public function __construct(string $type, callable $callback = null)',
			'public function __construct(string $type, ?callable $callback = null)',
		],
	],
	'league/container/src/Inflector/InflectorAggregate.php'         => [
		[
			'public function add(string $type, callable $callback = null)',
			'public function add(string $type, ?callable $callback = null)',
		],
	],
	'league/container/src/Inflector/InflectorAggregateInterface.php' => [
		[
			'public function add(string $type, callable $callback = null)',
			'public function add(string $type, ?callable $callback = null)',
		],
	],
	'phpoffice/phpspreadsheet/src/PhpSpreadsheet/Reader/Xlsx.php'   => [
		[
			'"xl/_rels/${workbookBasename}.rels"',
			'"xl/_rels/{$workbookBasename}.rels"',
		],
	],
];

$errors  = [];
$patched = 0;

foreach ( $replacements as $relative_path => $pairs ) {
	$file = $vendor_dir . '/' . $relative_path;

	if ( ! is_file( $file ) ) {
		$errors[] = sprintf( '%s: file not found.', $relative_path );

		continue;
	}

	$contents = file_get_contents( $file );
	$original = $contents;

	foreach ( $pairs as list( $search, $replace ) ) {
		$occurrences = substr_count( $contents, $search );

		// Re-running against an already-patched tree is a no-op, not a failure.
		if ( 0 === $occurrences && 1 === substr_count( $contents, $replace ) ) {
			continue;
		}

		if ( 1 !== $occurrences ) {
			$errors[] = sprintf(
				'%s: expected 1 occurrence of "%s", found %d.',
				$relative_path,
				trim( strtok( $search, "\n" ) ),
				$occurrences
			);

			continue;
		}

		$contents = str_replace( $search, $replace, $contents );
		$patched ++;
	}

	if ( $contents !== $original && false === file_put_contents( $file, $contents ) ) {
		$errors[] = sprintf( '%s: could not be written.', $relative_path );
	}
}

if ( $errors ) {
	fwrite( STDERR, "Failed to patch the vendored dependencies for PHP 8.2+:\n - " . implode( "\n - ", $errors ) . "\n" );

	exit( 1 );
}

printf( "Patched %d deprecated declarations in vendor_prefixed.\n", $patched );
