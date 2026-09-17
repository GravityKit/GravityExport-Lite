<?php
/**
 * Minimal stand-in for Gravity Forms' settings Text field, which is not
 * available in the unit-test context. Mirrors the prop-copying constructor
 * the real base class uses.
 *
 * Loaded from tests/bootstrap.php only. Excluded from PHPStan analysis
 * (see phpstan.neon.dist) so static analysis resolves the real class from
 * vendor/gravityforms instead of this stub.
 *
 * @since TBD
 */

namespace Gravity_Forms\Gravity_Forms\Settings\Fields;

if ( ! class_exists( __NAMESPACE__ . '\Text' ) ) {
	// The real base class copies arbitrary props onto itself, so the stub must too.
	// PHP 7.x reads this attribute as a comment; PHP 8.2+ needs it to stay quiet.
	#[\AllowDynamicProperties]
	class Text {
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
