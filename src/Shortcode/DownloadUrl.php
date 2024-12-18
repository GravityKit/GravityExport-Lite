<?php

namespace GFExcel\Shortcode;

use GFExcel\Addon\GravityExportAddon;
use GFExcel\GFExcel;

/**
 * A shortcode handler for [gravityexport_download_url].
 * Example usage: [gravityexport_download_url id=1 type=csv]
 * Id is required, type is optional.
 * @since 1.6.1
 */
class DownloadUrl {
	/**
	 * The short code name.
	 *
	 * @since 2.2.0
	 *
	 * @var string
	 */
	public const SHORTCODE = 'gravityexport_download_url';

	/**
	 * @deprecated 2.2.0 use {@see self::SHORTCODE}.
	 *
	 * @var string
	 */
	public const SHORTTAG = self::SHORTCODE;

	/**
	 * The length of the secret used to protect the embed tag.
	 * @since 2.2.0
	 */
	private const SECRET_LENGTH = 6;

	/**
	 * Adds the required hooks.
	 *
	 * @since 2.2.0
	 */
	public function __construct() {
		add_shortcode( 'gfexcel_download_url', [ $this, 'handle' ] ); // Backward compatibility.
		add_shortcode( self::SHORTCODE, [ $this, 'handle' ] );
		add_filter( 'gform_replace_merge_tags', [ $this, 'handleNotification' ], 10, 2 );
	}

	/**
	 * Handles the [gfexcel_download_url] shortcode.
	 * @since 1.6.1
	 *
	 * @param array|string $arguments
	 *
	 * @return string returns the replacing content, either a url or a message.
	 */
	public function handle( $arguments ) {
		if ( ! is_array( $arguments ) ) {
			$arguments = [];
		}

		if ( ! array_key_exists( 'id', $arguments ) ) {
			return $this->error( sprintf( 'Please add an `%s` argument to \'%s\' shortcode.', 'id', self::SHORTCODE ) );
		}

		$feed = GravityExportAddon::get_instance()->get_feed_by_form_id( $arguments['id'] );
		$hash = rgars( $feed, 'meta/hash' );

		if ( ! $feed || ! $hash ) {
			return $this->error( 'GravityExport: This form has no public download URL.' );
		}

		$is_protected = self::is_embed_protected( $feed );

		if ( ! $is_protected ) {
			return $this->getUrl( $arguments['id'], $arguments['type'] ?? null );
		}

		$secret = rgar( $arguments, 'secret', '' );

		if ( ! $this->validate_secret( $hash, $secret ) ) {
			return $this->error( strtr(
				esc_html_x( "Please add a valid 'secret' attribute to the '[shortcode]' shortcode.", 'Placeholders inside [] are not to be translated.', 'gk-gravityexport-lite' ),
				[ '[shortcode]' => self::SHORTCODE ]
			) );
		}

		return $this->getUrl( $arguments['id'], $arguments['type'] ?? null );
	}

	/**
	 * Handles the shortcode for Gravity Forms.
	 * @since 1.6.1
	 *
	 * @param string $text the text of the notification
	 * @param array|false $form The form object.
	 *
	 * @return string The url or an error message
	 */
	public function handleNotification( $text, $form ) {
		if ( ! is_array( $form ) || ! isset( $form['id'] ) ) {
			return $text;
		}

		foreach ( [ self::SHORTCODE, 'gfexcel_download_url' ] as $shortcode ) {
			$custom_merge_tag = '{' . $shortcode . '}';

			if ( strpos( $text, $custom_merge_tag ) === false ) {
				continue;
			}

			$text = str_replace( $custom_merge_tag, $this->getUrl( $form['id'] ), $text );
		}

		return $text;
	}

	/**
	 * Gets the actual URL by providing the form ID and file extension.
	 * @since 1.6.1
	 *
	 * @param int $id
	 * @param string|null $type either 'csv' or 'xlsx'.
	 *
	 * @return string
	 */
	private function getUrl( $id, $type = null ): string {
		$url = GFExcel::url( $id );

		if ( $type && in_array( strtolower( $type ), GFExcel::getPluginFileExtensions(), true ) ) {
			$url .= '.' . strtolower( $type );
		}

		return $url;
	}

	/**
	 * Returns the error message. Can be overwritten by filter hook.
	 * @since 1.6.1
	 *
	 * @param string $message The error message.
	 *
	 * @return string The filtered error message.
	 */
	private function error( string $message ): string {
		return (string) gf_apply_filters( [
			'gfexcel_shortcode_error',
		], $message );
	}

	/**
	 * Validates if the secret matches the hash.
	 * @since 2.2.0
	 *
	 * @param string $hash The hash.
	 * @param string $secret The secret.
	 *
	 * @return bool
	 */
	private function validate_secret( string $hash, string $secret ): bool {
		$test = self::generate_secret_from_hash( $hash );
		if ( strlen( $test ) !== self::SECRET_LENGTH || strlen( $secret ) !== self::SECRET_LENGTH ) {
			return false;
		}

		return $test === $secret;
	}

	/**
	 * Returns whether the shortcode for this feed is protected.
	 * @since 2.2.0
	 *
	 * @param array $feed The feed object.
	 *
	 * @return bool Whether the embed is protected.
	 * @filter gk/gravityexport/embed/is-protected Enabled embed protection for all shortcodes.
	 */
	private static function is_embed_protected( array $feed ): bool {
		$is_global_embed_protected = (bool) apply_filters( 'gk/gravityexport/embed/is-protected', false );
		if ( $is_global_embed_protected ) {
			return true;
		}

		return (bool) rgars( $feed, 'meta/has_embed_secret' );
	}

	/**
	 * Returns the secret for a form.
	 * @since 2.2.0
	 *
	 * @param int $form_id The form id.
	 *
	 * @return string The secret.
	 */
	public static function get_secret( int $form_id ): string {
		$hash = self::get_form_hash( $form_id );
		if ( ! $hash ) {
			return '';
		}

		return self::generate_secret_from_hash($hash);
	}

	/**
	 * Generates the secret from the hash.
	 * @since 2.2.0
	 *
	 * @param string $hash The hash.
	 *
	 * @return string The secret.
	 */
	private static function generate_secret_from_hash( string $hash ): string {
		return strrev( substr( $hash, self::SECRET_LENGTH, self::SECRET_LENGTH ) );
	}

	/**
	 * Returns the hash for a form.
	 * @since 2.2.0
	 *
	 * @param int $form_id The form ID.
	 *
	 * @return string|null
	 */
	private static function get_form_hash( int $form_id ): ?string {
		$feed = GravityExportAddon::get_instance()->get_feed_by_form_id( $form_id );

		return rgars( $feed ?? [], 'meta/hash' );
	}

	/**
	 * Generates the embed code for a form.
	 * @since 2.2.0
	 *
	 * @param int $form_id The form ID.
	 * @param string|null $type The type of the download.
	 *
	 * @return string|null The embed code.
	 */
	public static function generate_embed_short_code( int $form_id, ?string $type = null ): ?string {
		$feed = GravityExportAddon::get_instance()->get_feed_by_form_id( $form_id );

		$hash = self::get_form_hash( $form_id );
		if ( ! $hash ) {
			return null;
		}

		$attributes = [
			'id'   => $form_id,
			'type' => $type ?: GFExcel::getFileExtension( [ 'id' => $form_id ] ),
		];

		if ( self::is_embed_protected( $feed ) ) {
			$attributes['secret'] = self::generate_secret_from_hash( $hash );
		}

		foreach ( $attributes as $key => $value ) {
			$attributes[ $key ] = sprintf( '%s="%s"', $key, esc_attr( $value ) );
		}

		return sprintf( '[%s %s]', self::SHORTCODE, implode( ' ', $attributes ) );
	}
}
