<?php

namespace GFExcel\Analytics;

/**
 * Stores and reports analytics consent.
 *
 * The record carries a `disclosure_hash` — a digest of the exact wording shown
 * when consent was granted. That turns the eventual handover to Foundation into
 * a checkable fact: Foundation honors an inherited grant whose hash it still
 * recognizes, and re-prompts when its own disclosure has since changed. Without
 * it, "the user already opted in" is an assumption nobody can verify.
 *
 * @since $ver$
 */
class Consent {
	/**
	 * Returns true when the site has granted consent.
	 *
	 * @since $ver$
	 *
	 * @return bool Whether consent was granted.
	 */
	public function granted(): bool {
		$record = $this->record();

		return null !== $record && true === $record['granted'];
	}

	/**
	 * Records a grant.
	 *
	 * @since $ver$
	 *
	 * @param string $source     Where consent was collected; a closed enum value.
	 * @param string $disclosure The exact text shown to the user.
	 *
	 * @return void
	 */
	public function grant( string $source, string $disclosure ): void {
		update_option(
			Schema::CONSENT['option'],
			[
				'granted'         => true,
				'ts'              => time(),
				'source'          => $source,
				'schema_version'  => Schema::SCHEMA_VERSION,
				'disclosure_hash' => hash( 'sha256', $disclosure ),
			],
			false
		);
	}

	/**
	 * Records a withdrawal, preserving the audit trail rather than deleting it.
	 *
	 * @since $ver$
	 *
	 * @return void
	 */
	public function revoke( string $source = 'lite_settings_card' ): void {
		$record = $this->record();

		// A first-time decline has no prior record to amend. It must still write a
		// complete one, or the result fails validation, reads as "never asked",
		// and the prompt returns forever despite the user having answered it.
		if ( null === $record ) {
			$record = [
				'granted'         => false,
				'ts'              => time(),
				'source'          => $source,
				'schema_version'  => Schema::SCHEMA_VERSION,
				'disclosure_hash' => '',
			];
		}

		$record['granted']    = false;
		$record['revoked_ts'] = time();

		update_option( Schema::CONSENT['option'], $record, false );
	}

	/**
	 * Returns the stored record, or null when consent was never answered.
	 *
	 * @since $ver$
	 *
	 * @return array|null The consent record.
	 */
	public function record(): ?array {
		$record = get_option( Schema::CONSENT['option'] );

		return $this->isWellFormed( $record ) ? $record : null;
	}

	/**
	 * Returns true when the stored record has the shape this version wrote.
	 *
	 * A malformed record must not be trusted in either direction: treating it as
	 * a grant would transmit without a real opt-in, and treating it as a decision
	 * would suppress the prompt forever, leaving the user unable to answer.
	 * Anything unrecognised is therefore "not yet asked".
	 *
	 * @since $ver$
	 *
	 * @param mixed $record The stored value.
	 *
	 * @return bool Whether the record is usable.
	 */
	private function isWellFormed( $record ): bool {
		if ( ! is_array( $record ) ) {
			return false;
		}

		foreach ( [ 'granted' => 'boolean', 'ts' => 'integer', 'source' => 'string', 'schema_version' => 'integer' ] as $key => $type ) {
			if ( ! array_key_exists( $key, $record ) || $type !== gettype( $record[ $key ] ) ) {
				return false;
			}
		}

		$sources = (array) ( Schema::enum( 'consent_source' ) ?? [] );

		return in_array( $record['source'], $sources, true );
	}

	/**
	 * Returns true when the stored grant was given against different wording.
	 *
	 * This is what `disclosure_hash` is for. A grant covers the promise that was
	 * on screen when it was given, so if the disclosure has since changed the
	 * grant no longer covers what would now be sent.
	 *
	 * @since $ver$
	 *
	 * @param string $disclosure The wording that would be shown now.
	 *
	 * @return bool Whether the disclosure has changed since consent was given.
	 */
	public function disclosureChanged( string $disclosure ): bool {
		$record = $this->record();

		if ( null === $record || true !== $record['granted'] ) {
			return false;
		}

		return ( $record['disclosure_hash'] ?? '' ) !== hash( 'sha256', $disclosure );
	}

	/**
	 * Returns true when the user has neither granted nor declined.
	 *
	 * @since $ver$
	 *
	 * @return bool Whether the question is still open.
	 */
	public function undecided(): bool {
		return null === $this->record();
	}
}
