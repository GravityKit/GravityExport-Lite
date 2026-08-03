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

		foreach ( $props as $key => $value ) {
			if ( $this->keyIsDropped( (string) $key ) ) {
				continue;
			}

			$clean[ $key ] = is_string( $value ) ? $this->scrubString( $value ) : $value;
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
	private function keyIsDropped( string $key ): bool {
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
	private function scrubString( string $value ): string {
		foreach ( Schema::SCRUB['redact_value_patterns'] as $label => $pattern ) {
			$value = (string) preg_replace( $pattern, '[' . $label . ' redacted]', $value );
		}

		$cutoff = (int) Schema::SCRUB['free_text_cutoff'];
		if ( strlen( $value ) > $cutoff ) {
			$value = substr( $value, 0, $cutoff ) . '…';
		}

		return $value;
	}
}
