<?php

/**
 * Gravity Forms array helpers, needed by code under test that runs outside a WordPress bootstrap.
 *
 * Copied verbatim from `vendor/gravityforms/gravityforms/gravityforms.php` (`rgar()` at line 7109,
 * `rgars()` at line 7138). Their exact semantics are load-bearing for the download-URL authorisation
 * check: `rgar()` returns an empty string, not null, for a missing key when `$default` is null.
 * {@see \GFExcel\Tests\Routing\WordPressRouterTest::test_rgars_returns_empty_string_for_a_missing_key}
 * pins that behavior so this copy cannot silently drift from Gravity Forms.
 *
 * @since TBD
 */

if ( ! function_exists( 'rgar' ) ) {
	function rgar( $array, $prop, $default = null ) {

		if ( ! is_array( $array ) && ! ( is_object( $array ) && $array instanceof ArrayAccess ) ) {
			return $default;
		}

		if ( isset( $array[ $prop ] ) ) {
			$value = $array[ $prop ];
		} else {
			$value = '';
		}

		return empty( $value ) && $default !== null ? $default : $value;
	}
}

if ( ! function_exists( 'rgars' ) ) {
	function rgars( $array, $name, $default = null ) {

		if ( ! is_array( $array ) && ! ( is_object( $array ) && $array instanceof ArrayAccess ) ) {
			return $default;
		}

		$names = explode( '/', $name );
		$val   = $array;
		foreach ( $names as $current_name ) {
			$val = rgar( $val, $current_name, $default );
		}

		return $val;
	}
}
