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
		$record = get_option( Schema::CONSENT['option'] );

		return is_array( $record ) && ! empty( $record['granted'] );
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
	public function revoke(): void {
		$record = get_option( Schema::CONSENT['option'] );
		$record = is_array( $record ) ? $record : [];

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

		return is_array( $record ) ? $record : null;
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
