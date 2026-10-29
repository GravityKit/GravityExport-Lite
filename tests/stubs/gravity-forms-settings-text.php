<?php
/**
 * Minimal stand-ins for Gravity Forms' settings Base and Text fields, which
 * are not available in the unit-test context. Base mirrors the prop-copying
 * constructor of the real base class, and Text extends it as the real one does.
 *
 * Loaded from tests/bootstrap.php only. Excluded from PHPStan analysis
 * (see phpstan.neon.dist) so static analysis resolves the real classes from
 * vendor/gravityforms instead of these stubs.
 *
 * @since TBD
 */

namespace Gravity_Forms\Gravity_Forms\Settings\Fields;

if ( ! class_exists( __NAMESPACE__ . '\Base' ) ) {
	// The real base class copies arbitrary props onto itself, so the stub must too.
	// PHP 7.x reads this attribute as a comment; PHP 8.2+ needs it to stay quiet.
	#[\AllowDynamicProperties]
	class Base {
		/** @var mixed The settings renderer instance, kept for fields whose save() consults it. */
		public $settings;

		public function __construct( $props = [], $settings = null ) {
			$this->settings = $settings;

			foreach ( (array) $props as $prop => $value ) {
				$this->{$prop} = $value;
			}
		}
	}
}

if ( ! class_exists( __NAMESPACE__ . '\Text' ) ) {
	#[\AllowDynamicProperties]
	class Text extends Base {
	}
}
