<?php

namespace GFExcel\Tests\Analytics;

use GFExcel\Analytics\Scrub;
use GFExcel\Tests\TestCase;

/**
 * Unit tests for {@see Scrub}.
 *
 * @since $ver$
 */
class ScrubTest extends TestCase {
	/**
	 * The class under test.
	 *
	 * @since $ver$
	 * @var Scrub
	 */
	private $scrub;

	/**
	 * @inheritdoc
	 * @since $ver$
	 */
	public function setUp(): void {
		parent::setUp();

		$this->scrub = new Scrub();
	}

	/**
	 * @since $ver$
	 */
	public function testEmailIsRedacted(): void {
		$result = $this->scrub->scrub( [ 'a' => 'contact zack@example.com now' ] );

		self::assertSame( 'contact [email redacted] now', $result['a'] );
	}

	/**
	 * @since $ver$
	 */
	public function testUrlIsRedacted(): void {
		$result = $this->scrub->scrub( [ 'a' => 'see https://private.example.com/x?y=1' ] );

		self::assertSame( 'see [url redacted]', $result['a'] );
	}

	/**
	 * PostHog's $-prefixed location properties carry IP-derived geography.
	 *
	 * @since $ver$
	 */
	public function testDollarPrefixedKeysAreDropped(): void {
		$result = $this->scrub->scrub(
			[
				'$geoip_city_name' => 'Leverett',
				'column_count'     => 3,
			]
		);

		self::assertSame( [ 'column_count' => 3 ], $result );
	}

	/**
	 * @since $ver$
	 */
	public function testLongFreeTextIsTruncated(): void {
		$result = $this->scrub->scrub( [ 'a' => str_repeat( 'x', 500 ) ] );

		self::assertSame( 201, mb_strlen( $result['a'] ) );
	}

	/**
	 * Redaction must run before truncation. If it did not, an email sitting
	 * past the cutoff would survive inside the retained head of the string.
	 *
	 * @since $ver$
	 */
	public function testEmailPastTheCutoffIsStillRedacted(): void {
		$value  = str_repeat( 'x', 300 ) . ' zack@example.com';
		$result = $this->scrub->scrub( [ 'a' => $value ] );

		self::assertStringNotContainsString( 'zack@example.com', $result['a'] );
	}

	/**
	 * @since $ver$
	 */
	public function testNonStringValuesPassThroughUnchanged(): void {
		$result = $this->scrub->scrub(
			[
				'column_count' => 7,
				'is_multisite' => false,
			]
		);

		self::assertSame( [ 'column_count' => 7, 'is_multisite' => false ], $result );
	}
}
