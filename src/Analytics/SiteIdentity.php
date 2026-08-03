<?php

namespace GFExcel\Analytics;

/**
 * Derives the anonymous, non-reversible install identifier.
 *
 * Deliberate deviation from taxonomy §4: that section allows reusing `NONCE_SALT`
 * when it is not WordPress's public default. We always mint a dedicated salt
 * instead. `NONCE_SALT` lives in wp-config.php and routinely travels through
 * backups and support requests; if it leaks, every `site_id` derived from it
 * becomes verifiable against a guessed URL. A dedicated salt has no other
 * consumer, so it cannot leak through those channels.
 *
 * @since $ver$
 */
class SiteIdentity {
	/**
	 * Cached identifier for this request.
	 *
	 * @since $ver$
	 * @var string|null
	 */
	private $site_id;

	/**
	 * Returns the HMAC of the site URL, or null when no salt can be established.
	 *
	 * Returning null is a hard stop, not a fallback: shipping a hash under a
	 * known or empty salt would be reversible, so the caller must refuse to emit.
	 *
	 * @since $ver$
	 *
	 * @return string|null The site identifier.
	 */
	public function siteId(): ?string {
		if ( null !== $this->site_id ) {
			return $this->site_id;
		}

		$salt = $this->salt( Schema::IDENTITY['salt_option'] );
		if ( null === $salt ) {
			return null;
		}

		$this->site_id = hash_hmac( Schema::IDENTITY['hmac_algo'], $this->normalizedUrl(), $salt );

		return $this->site_id;
	}

	/**
	 * Returns the attribution token, which uses a SEPARATE salt from siteId().
	 *
	 * This separation is the whole point. The attribution token is stored on
	 * gravitykit.com against a discount code and therefore against a real
	 * customer email. Deriving it from the analytics salt would make every
	 * event an install ever sent retroactively attributable to a named person.
	 *
	 * @since $ver$
	 *
	 * @return string|null The attribution token.
	 */
	public function attributionToken(): ?string {
		$salt = $this->salt( Schema::ATTRIBUTION['salt_option'] );
		if ( null === $salt ) {
			return null;
		}

		return hash_hmac( Schema::IDENTITY['hmac_algo'], $this->normalizedUrl(), $salt );
	}

	/**
	 * Reads or mints a 32-byte salt, stored under the given option name.
	 *
	 * Uses add_option() rather than get+update: add_option() will not overwrite
	 * an existing row, so two concurrent requests that both find the option
	 * missing cannot orphan each other's identifiers.
	 *
	 * @since $ver$
	 *
	 * @param string $option The option name.
	 *
	 * @return string|null The salt.
	 */
	private function salt( string $option ): ?string {
		$stored = get_option( $option );
		if ( is_string( $stored ) && '' !== $stored ) {
			return $stored;
		}

		try {
			$salt = bin2hex( random_bytes( 32 ) );
		} catch ( \Exception $e ) {
			return null; // No CSPRNG: refuse rather than emit a weak hash.
		}

		add_option( $option, $salt, '', false );

		$after = get_option( $option );

		return is_string( $after ) && '' !== $after ? $after : null;
	}

	/**
	 * Normalizes the site URL so cosmetic differences do not re-baseline identity.
	 *
	 * @since $ver$
	 *
	 * @return string The normalized URL.
	 */
	private function normalizedUrl(): string {
		$url = home_url();

		$url = preg_replace_callback(
			'#^[A-Za-z]+://#',
			static function ( array $m ): string {
				return strtolower( $m[0] );
			},
			(string) $url
		);

		return rtrim( (string) $url, '/' );
	}
}
