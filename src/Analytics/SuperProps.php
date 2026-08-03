<?php

namespace GFExcel\Analytics;

/**
 * Builds the install-global properties attached to every event (taxonomy §3).
 *
 * @since $ver$
 */
class SuperProps {
	/**
	 * The install identity.
	 *
	 * @since $ver$
	 * @var SiteIdentity
	 */
	private $identity;

	/**
	 * @since $ver$
	 *
	 * @param SiteIdentity $identity The install identity.
	 */
	public function __construct( SiteIdentity $identity ) {
		$this->identity = $identity;
	}

	/**
	 * Returns the super properties, or null when identity cannot be established.
	 *
	 * @since $ver$
	 *
	 * @return array|null The super properties.
	 */
	public function build(): ?array {
		$site_id = $this->identity->siteId();
		if ( null === $site_id ) {
			return null;
		}

		return [
			'environment'                 => 'production',
			'site_id'                     => $site_id,
			'gk_analytics_schema_version' => Schema::SCHEMA_VERSION,
			'wp_version'                  => get_bloginfo( 'version' ),
			'gf_version'                  => $this->gravityFormsVersion(),
			'php_version'                 => PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION,
			'locale'                      => get_locale(),
			'is_multisite'                => is_multisite(),
			'license_tier'                => 'free',
			'user_role'                   => $this->primaryRole(),
			'gk_product'                  => 'gravityexport-lite',
			'gk_product_version'          => defined( 'GFEXCEL_PLUGIN_VERSION' ) ? GFEXCEL_PLUGIN_VERSION : '',
			'trigger_source'              => 'real',
			'install_channel'             => $this->installChannel(),
		];
	}

	/**
	 * Returns the Gravity Forms version, or null when it is not active.
	 *
	 * @since $ver$
	 *
	 * @return string|null The version.
	 */
	private function gravityFormsVersion(): ?string {
		if ( ! class_exists( '\GFCommon' ) || ! property_exists( '\GFCommon', 'version' ) ) {
			return null;
		}

		$version = \GFCommon::$version;

		return is_string( $version ) && '' !== $version ? $version : null;
	}

	/**
	 * Returns the current user's first role name, never a username or id.
	 *
	 * @since $ver$
	 *
	 * @return string The role name.
	 */
	private function primaryRole(): string {
		if ( ! function_exists( 'wp_get_current_user' ) ) {
			return 'none';
		}

		$roles = (array) wp_get_current_user()->roles;

		return $roles ? (string) reset( $roles ) : 'none';
	}

	/**
	 * Distinguishes the wordpress.org build from the gravitykit.com-distributed one.
	 *
	 * These are different populations. Only the .org one appears in the
	 * wordpress.org active-install count, so folding them together computes the
	 * opt-in rate against a denominator that does not contain half its numerator.
	 *
	 * @since $ver$
	 *
	 * @return string The install channel.
	 */
	private function installChannel(): string {
		if ( ! defined( 'GFEXCEL_PLUGIN_FILE' ) ) {
			return 'unknown';
		}

		$dir = basename( dirname( (string) GFEXCEL_PLUGIN_FILE ) );

		if ( 'gf-entries-in-excel' === $dir ) {
			return 'wordpress_org';
		}

		if ( 'gravityexport-lite' === $dir ) {
			return 'gravitykit_com';
		}

		return 'unknown';
	}
}
