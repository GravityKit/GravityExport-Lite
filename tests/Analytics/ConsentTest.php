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
		\WP_Mock::userFunction( 'get_option' )->andReturn( [ 'granted' => false ] );

		self::assertFalse( $this->consent->undecided(), 'A decline must not re-prompt.' );
		self::assertFalse( $this->consent->granted() );
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
