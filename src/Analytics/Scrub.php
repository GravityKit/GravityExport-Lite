<?php

namespace GFExcel\Analytics;

/**
 * The server-side PII scrub (taxonomy §8), the PHP twin of the JS before_send.
 *
 * Runs after the allowlist. The allowlist governs which keys are legal; this
 * governs what may sit inside a legal key. A registered free-text key can still
 * carry an email a user typed into a form label, so both layers are needed.
 *
 * @since $ver$
 */
class Scrub {
	/**
	 * Removes or redacts anything that could carry personal data.
	 *
	 * @since $ver$
	 *
	 * @param array $props The properties to scrub.
	 *
	 * @return array The scrubbed properties.
	 */
	public function scrub( array $props ): array {
		$clean = [];
		$site  = $this->siteName();

		foreach ( $props as $key => $value ) {
			if ( $this->keyIsDropped( (string) $key ) ) {
				continue;
			}

			$clean[ $key ] = is_string( $value ) ? $this->scrubString( $value, $site ) : $value;
		}

		return $clean;
	}

	/**
	 * Returns true when a key matches a drop pattern (PostHog $-location props).
	 *
	 * @since $ver$
	 *
	 * @param string $key The property key.
	 *
	 * @return bool Whether to drop it.
	 */
	/**
	 * Returns the site name when it is safe to scrub for, otherwise null.
	 *
	 * Very short names are skipped deliberately. A site called "A" or "My" would
	 * match inside almost every string and reduce the payload to redaction
	 * markers, destroying data without protecting anything, since a two-letter
	 * name identifies nobody. Easy Digital Downloads, which this behaviour is
	 * taken from, does not guard against that.
	 *
	 * There is no off switch by design. The threshold is a minimum length, not a
	 * toggle: a scrub that can be disabled is one that will be, and the consent
	 * card's promise does not have an exception clause.
	 *
	 * @since $ver$
	 *
	 * @return string|null The site name, or null when it should not be scrubbed for.
	 */
	private function siteName(): ?string {
		if ( ! function_exists( 'get_bloginfo' ) ) {
			return null;
		}

		$name = trim( (string) get_bloginfo( 'name' ) );

		return strlen( $name ) >= (int) Schema::SCRUB['min_site_name_length'] ? $name : null;
	}

	private function keyIsDropped( string $key ): bool {
		// A narrow exception, and the reason it exists matters: the drop rule below
		// removes every $-prefixed key so PostHog's location properties can never
		// leak. The instructions telling the sink NOT to collect an IP are also
		// $-prefixed, so without this they would be scrubbed away and the payload
		// would silently lose the very control it is asserting.
		if ( in_array( $key, Schema::SCRUB['preserve_keys'], true ) ) {
			return false;
		}

		foreach ( Schema::SCRUB['drop_key_patterns'] as $pattern ) {
			if ( 1 === preg_match( '/' . $pattern . '/', $key ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Redacts emails and URLs, then truncates long free text.
	 *
	 * Truncation is last: redaction must see the whole string, or an email
	 * sitting past the cutoff would survive in a truncated tail.
	 *
	 * @since $ver$
	 *
	 * @param string $value The value to scrub.
	 *
	 * @return string The scrubbed value.
	 */
	private function scrubString( string $value, ?string $site ): string {
		foreach ( Schema::SCRUB['redact_value_patterns'] as $label => $pattern ) {
			$value = (string) preg_replace( $pattern, '[' . $label . ' redacted]', $value );
		}

		// A registered key can still carry the site's own name inside free text —
		// a form title, a feed label. Easy Digital Downloads strips this and we
		// did not, which was the one place their telemetry was more careful.
		if ( null !== $site ) {
			$value = (string) str_ireplace( $site, '[site name redacted]', $value );
		}

		$cutoff = (int) Schema::SCRUB['free_text_cutoff'];
		if ( strlen( $value ) > $cutoff ) {
			$value = substr( $value, 0, $cutoff ) . '…';
		}

		return $value;
	}
}
