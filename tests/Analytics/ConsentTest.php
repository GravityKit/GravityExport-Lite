<?php

namespace GFExcel\Tests\Analytics;

use GFExcel\Analytics\Consent;
use GFExcel\Analytics\Schema;
use GFExcel\Tests\TestCase;

/**
 * Unit tests for {@see Consent}.
 *
 * @since $ver$
 */
class ConsentTest extends TestCase {
	/**
	 * The class under test.
	 *
	 * @since $ver$
	 * @var Consent
	 */
	private $consent;

	/**
	 * @inheritdoc
	 * @since $ver$
	 */
	public function setUp(): void {
		parent::setUp();

		$this->consent = new Consent();
	}

	/**
	 * A fresh install must be undecided, not denied. The difference decides
	 * whether the user is ever asked.
	 *
	 * @since $ver$
	 */
	public function testAFreshInstallIsUndecided(): void {
		\WP_Mock::userFunction( 'get_option' )->andReturn( false );

		self::assertTrue( $this->consent->undecided() );
		self::assertFalse( $this->consent->granted() );
	}

	/**
	 * @since $ver$
	 */
	public function testADeclinedInstallIsDecidedAndNotGranted(): void {
		\WP_Mock::userFunction( 'get_option' )->andReturn( $this->record( [ 'granted' => false ] ) );

		self::assertFalse( $this->consent->undecided(), 'A decline must not re-prompt.' );
		self::assertFalse( $this->consent->granted() );
	}

	/**
	 * Returns a well-formed consent record with overrides applied.
	 *
	 * @since $ver$
	 *
	 * @param array $overrides Fields to override.
	 *
	 * @return array The record.
	 */
	private function record( array $overrides = [] ): array {
		return $overrides + [
			'granted'         => true,
			'ts'              => 1785000000,
			'source'          => 'lite_settings_card',
			'schema_version'  => Schema::SCHEMA_VERSION,
			'disclosure_hash' => hash( 'sha256', 'wording' ),
		];
	}

	/**
	 * A corrupt record must not authorize sending. Trusting any array with a
	 * truthy `granted` bit would transmit without a real opt-in.
	 *
	 * @since $ver$
	 *
	 * @dataProvider malformedRecords
	 *
	 * @param mixed $stored The stored value.
	 */
	public function testAMalformedRecordNeverGrants( $stored ): void {
		\WP_Mock::userFunction( 'get_option' )->andReturn( $stored );

		self::assertFalse( $this->consent->granted() );
	}

	/**
	 * A corrupt record must also not count as a decision, or the prompt is
	 * suppressed forever and the user can never answer it.
	 *
	 * @since $ver$
	 *
	 * @dataProvider malformedRecords
	 *
	 * @param mixed $stored The stored value.
	 */
	public function testAMalformedRecordIsTreatedAsUnasked( $stored ): void {
		\WP_Mock::userFunction( 'get_option' )->andReturn( $stored );

		self::assertTrue( $this->consent->undecided() );
	}

	/**
	 * @since $ver$
	 *
	 * @return array[] The malformed records.
	 */
	public function malformedRecords(): array {
		return [
			'truthy scalar'        => [ 1 ],
			'truthy string'        => [ 'yes' ],
			'bare granted flag'    => [ [ 'granted' => true ] ],
			'granted as string'    => [ [ 'granted' => 'true', 'ts' => 1, 'source' => 'lite_settings_card', 'schema_version' => 1 ] ],
			'missing timestamp'    => [ [ 'granted' => true, 'source' => 'lite_settings_card', 'schema_version' => 1 ] ],
			'unregistered source'  => [ [ 'granted' => true, 'ts' => 1, 'source' => 'somewhere_else', 'schema_version' => 1 ] ],
			'timestamp as string'  => [ [ 'granted' => true, 'ts' => '1', 'source' => 'lite_settings_card', 'schema_version' => 1 ] ],
		];
	}

	/**
	 * A grant covers the wording that was on screen when it was given. If the
	 * disclosure has changed, the grant no longer covers what would be sent.
	 *
	 * @since $ver$
	 */
	public function testDisclosureChangeIsDetectable(): void {
		\WP_Mock::userFunction( 'get_option' )->andReturn(
			$this->record( [ 'disclosure_hash' => hash( 'sha256', 'the original wording' ) ] )
		);

		self::assertFalse( $this->consent->disclosureChanged( 'the original wording' ) );
		self::assertTrue( $this->consent->disclosureChanged( 'materially different wording' ) );
	}

	/**
	 * A first-time decline has no prior record, and must still produce a complete
	 * one; otherwise it fails validation, reads as "never asked", and the prompt
	 * returns forever despite the user having answered.
	 *
	 * @since $ver$
	 */
	public function testAFirstTimeDeclineWritesACompleteRecord(): void {
		$written = null;

		\WP_Mock::userFunction( 'get_option' )->andReturn( false );
		\WP_Mock::userFunction( 'update_option' )->andReturnUsing(
			static function ( string $option, $value ) use ( &$written ): bool {
				$written = $value;

				return true;
			}
		);

		$this->consent->revoke( 'lite_settings_card' );

		self::assertNotNull( $written );
		self::assertFalse( $written['granted'] );
		self::assertIsInt( $written['ts'] );
		self::assertSame( 'lite_settings_card', $written['source'] );
		self::assertSame( Schema::SCHEMA_VERSION, $written['schema_version'] );
	}

	/**
	 * The stored record must carry a hash of the exact wording shown, so a
	 * later reader can tell whether a grant covers the promise now being made.
	 *
	 * @since $ver$
	 */
	public function testGrantRecordsTheDisclosureHash(): void {
		$option     = null;
		$record     = null;
		$disclosure = 'We collect anonymous usage data.';

		\WP_Mock::userFunction( 'update_option' )->andReturnUsing(
			static function ( string $name, $value ) use ( &$option, &$record ): bool {
				$option = $name;
				$record = $value;

				return true;
			}
		);

		$this->consent->grant( 'lite_settings_card', $disclosure );

		self::assertNotNull( $record, 'grant() never wrote the record.' );
		self::assertSame( Schema::CONSENT['option'], $option );
		self::assertTrue( $record['granted'] );
		self::assertSame( 'lite_settings_card', $record['source'] );
		self::assertSame( hash( 'sha256', $disclosure ), $record['disclosure_hash'] );
		self::assertSame( Schema::SCHEMA_VERSION, $record['schema_version'] );
	}

	/**
	 * Different wording must produce a different hash, or the field cannot do
	 * the job it exists for.
	 *
	 * @since $ver$
	 */
	public function testDifferentWordingProducesADifferentHash(): void {
		$hashes = [];

		\WP_Mock::userFunction( 'update_option' )->andReturnUsing(
			static function ( string $option, $value ) use ( &$hashes ): bool {
				$hashes[] = $value['disclosure_hash'];

				return true;
			}
		);

		$this->consent->grant( 'lite_settings_card', 'Original wording.' );
		$this->consent->grant( 'lite_settings_card', 'Revised wording.' );

		self::assertCount( 2, $hashes );
		self::assertCount( 2, array_unique( $hashes ), 'Different wording must hash differently.' );
	}

	/**
	 * Revocation keeps the audit trail rather than deleting the row, so the
	 * record still shows that consent was once given and when it ended.
	 *
	 * @since $ver$
	 */
	public function testRevokePreservesTheRecord(): void {
		$written = null;

		\WP_Mock::userFunction( 'get_option' )->andReturn(
			[ 'granted' => true, 'ts' => 1, 'source' => 'lite_settings_card' ]
		);
		\WP_Mock::userFunction( 'update_option' )->andReturnUsing(
			static function ( string $option, $value ) use ( &$written ): bool {
				$written = $value;

				return true;
			}
		);

		$this->consent->revoke();

		self::assertNotNull( $written );
		self::assertFalse( $written['granted'] );
		self::assertSame( 'lite_settings_card', $written['source'], 'The original grant context must survive.' );
		self::assertArrayHasKey( 'revoked_ts', $written );
	}
}
