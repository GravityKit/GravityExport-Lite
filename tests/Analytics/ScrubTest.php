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
	 * Site name the mocked get_bloginfo() returns.
	 *
	 * @since $ver$
	 * @var string
	 */
	private static $site_name = 'Example Site';

	/**
	 * @inheritdoc
	 * @since $ver$
	 */
	public function setUp(): void {
		parent::setUp();

		$this->scrub = new Scrub();

		// WP_Mock keeps the FIRST registration for a function, so a per-test
		// andReturn() would be silently ignored. The stub reads this property
		// instead, and each test sets it.
		self::$site_name = 'Example Site';

		\WP_Mock::userFunction( 'get_bloginfo' )->andReturnUsing(
			static function () {
				return self::$site_name;
			}
		);
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
	 * A registered key can still carry the site's own name inside free text — a
	 * form title, a feed label. Easy Digital Downloads strips this
	 * (EDD\Telemetry\Traits\Anonymize::anonymize_site_name) and we did not, which
	 * was the one place their telemetry was more careful than ours.
	 *
	 * @since $ver$
	 */
	public function testSiteNameIsRedactedFromFreeText(): void {
		self::$site_name = 'Acme Widgets';

		$result = $this->scrub->scrub( [ 'a' => 'Export for Acme Widgets monthly report' ] );

		self::assertStringNotContainsString( 'Acme Widgets', $result['a'] );
		self::assertSame( 'Export for [site name redacted] monthly report', $result['a'] );
	}

	/**
	 * @since $ver$
	 */
	public function testSiteNameIsRedactedRegardlessOfCase(): void {
		self::$site_name = 'Acme Widgets';

		$result = $this->scrub->scrub( [ 'a' => 'exported from ACME WIDGETS today' ] );

		self::assertStringNotContainsString( 'ACME WIDGETS', $result['a'] );
	}

	/**
	 * A site called "A" or "My" would match inside almost every string and reduce
	 * the payload to redaction markers, destroying data while protecting nobody.
	 *
	 * @since $ver$
	 */
	public function testAVeryShortSiteNameIsNotScrubbedFor(): void {
		self::$site_name = 'My';

		$result = $this->scrub->scrub( [ 'a' => 'My monthly summary' ] );

		self::assertSame( 'My monthly summary', $result['a'] );
	}

	/**
	 * @since $ver$
	 */
	public function testEmailRedactionIsStricterThanEddMasking(): void {
		self::$site_name = 'Acme Widgets';

		$result = $this->scrub->scrub( [ 'a' => 'ada@example.com' ] );

		// EDD would produce a*a@e*****e.com, which keeps length, first and last
		// characters, and the TLD. Nothing of the address survives here.
		self::assertSame( '[email redacted]', $result['a'] );
		self::assertStringNotContainsString( 'example', $result['a'] );
		self::assertStringNotContainsString( '.com', $result['a'] );
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
