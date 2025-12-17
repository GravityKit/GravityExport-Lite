<?php
/**
 * Stub class for GFCommon to allow testing without Gravity Forms.
 *
 * @package GFExcel\Tests\Stubs
 * @since 2.4.2
 */

if ( ! class_exists( 'GFCommon' ) ) {
	/**
	 * Stub class for GFCommon to allow testing without Gravity Forms.
	 */
	class GFCommon {
		/**
		 * @var int|null The timestamp to return from get_local_timestamp.
		 */
		public static $mock_timestamp = null;

		/**
		 * Mock implementation of get_local_timestamp.
		 *
		 * @param int|null $timestamp The GMT timestamp.
		 *
		 * @return int The local timestamp.
		 */
		public static function get_local_timestamp( $timestamp = null ) {
			if ( self::$mock_timestamp !== null ) {
				return self::$mock_timestamp;
			}

			if ( $timestamp == null ) {
				$timestamp = time();
			}

			return $timestamp;
		}

		/**
		 * Reset the mock timestamp.
		 */
		public static function reset() {
			self::$mock_timestamp = null;
		}
	}
}
