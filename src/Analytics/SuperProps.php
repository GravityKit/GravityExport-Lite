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
	 * The install scan.
	 *
	 * @since $ver$
	 * @var SiteScan
	 */
	private $scan;

	/**
	 * @since $ver$
	 *
	 * @param SiteIdentity $identity The install identity.
	 * @param SiteScan     $scan     The install scan.
	 */
	public function __construct( SiteIdentity $identity, SiteScan $scan ) {
		$this->identity = $identity;
		$this->scan     = $scan;
	}

	/**
	 * Returns the install-scale facts, which belong to the site rather than to
	 * any single event and are therefore sent as group properties.
	 *
	 * @since $ver$
	 *
	 * @return array The group properties.
	 */
	public function groupProperties(): array {
		return $this->scan->properties();
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
			'mysql_version'               => $this->databaseVersion(),
			'theme'                       => $this->theme(),
			'is_block_theme'              => $this->isBlockTheme(),
			'theme_is_child'              => is_child_theme(),
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
	 * Returns the database server version, major.minor only.
	 *
	 * The full string carries the build and distribution, which is unnecessarily
	 * precise for a compatibility question and adds fingerprint surface.
	 *
	 * @since $ver$
	 *
	 * @return string The version.
	 */
	private function databaseVersion(): string {
		global $wpdb;

		$version = preg_replace( '/[^0-9.].*/', '', (string) $wpdb->db_version() );
		$parts   = explode( '.', $version );

		return isset( $parts[1] ) ? $parts[0] . '.' . $parts[1] : (string) $parts[0];
	}

	/**
	 * Returns the active theme, or "other" when it is not on the published list.
	 *
	 * The raw name is refused: child themes are routinely named after the agency
	 * or the client that commissioned them, which makes the name a direct
	 * identifier far more often than it looks.
	 *
	 * @since $ver$
	 *
	 * @return string The theme key.
	 */
	private function theme(): string {
		$theme = wp_get_theme();

		// The parent first, deliberately. A child theme is usually named after the
		// agency or the client, so it is both the highest-cardinality value
		// available and frequently a direct identifier, while its parent is a
		// well-known framework. "Divi Child" and "Divi" are the same fact about
		// the site, and only one of them is safe to record.
		foreach ( [ $theme->get_template(), $theme->get_stylesheet() ] as $slug ) {
			if ( '' === (string) $slug ) {
				continue;
			}

			foreach ( Schema::THEMES as $key => $known ) {
				foreach ( (array) $known as $candidate ) {
					if ( 0 === strcasecmp( $candidate, (string) $slug ) ) {
						return (string) $key;
					}
				}
			}
		}

		return 'other';
	}

	/**
	 * Returns true when the active theme is a block theme.
	 *
	 * @since $ver$
	 *
	 * @return bool Whether it is a block theme.
	 */
	private function isBlockTheme(): bool {
		return function_exists( 'wp_is_block_theme' ) && wp_is_block_theme();
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
