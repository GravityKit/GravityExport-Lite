<?php

namespace GFExcel\Tests\Links;

use GFExcel\Analytics\Schema;
use GFExcel\Links\Links;
use GFExcel\Tests\TestCase;

/**
 * Unit tests for {@see Links}.
 *
 * @since $ver$
 */
class LinksTest extends TestCase {
	/**
	 * @inheritdoc
	 * @since $ver$
	 */
	public function setUp(): void {
		parent::setUp();

		// Pass the outbound filter through untouched.
		\WP_Mock::userFunction( 'apply_filters' )->andReturnUsing(
			static function () {
				$args = func_get_args();

				return $args[1];
			}
		);
	}

	/**
	 * Parses a URL's query string into an array.
	 *
	 * @since $ver$
	 *
	 * @param string $url The URL.
	 *
	 * @return array The query parameters.
	 */
	private function query( string $url ): array {
		parse_str( (string) parse_url( $url, PHP_URL_QUERY ), $params );

		return $params;
	}

	/**
	 * @since $ver$
	 */
	public function testUpgradeLinkCarriesTheFullScheme(): void {
		$params = $this->query( Links::upgrade( 'plugin_meta_upgrade' ) );

		self::assertSame(
			[
				'utm_source'   => 'gravityexport-lite',
				'utm_medium'   => 'plugin',
				'utm_campaign' => 'upgrade',
				'utm_content'  => 'plugin_meta_upgrade',
			],
			$params
		);
	}

	/**
	 * @since $ver$
	 */
	public function testDocsLinkUsesTheDocsCampaign(): void {
		$params = $this->query( Links::docs( 'plugin_meta_docs' ) );

		self::assertSame( 'docs', $params['utm_campaign'] );
		self::assertSame( 'plugin_meta_docs', $params['utm_content'] );
	}

	/**
	 * Destinations must be the final canonical URLs. The legacy gfexcel.com
	 * redirect drops the query string, so a tagged link routed through it
	 * arrives untagged and the campaign data is lost with no error.
	 *
	 * @since $ver$
	 */
	public function testNoDestinationUsesADomainThatStripsTheQueryString(): void {
		foreach ( Schema::LINKS['destinations'] as $key => $url ) {
			self::assertStringStartsWith(
				'https://www.gravitykit.com/',
				$url,
				sprintf( 'Destination "%s" does not point at the canonical host.', $key )
			);
		}
	}

	/**
	 * A typo must not silently create a new bucket in the reporting that looks
	 * like real traffic.
	 *
	 * @since $ver$
	 */
	public function testUnregisteredCtaIdIsReplaced(): void {
		$params = $this->query( Links::upgrade( 'not_a_registered_cta' ) );

		self::assertSame( 'unregistered', $params['utm_content'] );
	}

	/**
	 * @since $ver$
	 */
	public function testUnregisteredCampaignFallsBackToDocs(): void {
		$params = $this->query( Links::to( 'docs', 'plugin_meta_docs', 'made_up' ) );

		self::assertSame( 'docs', $params['utm_campaign'] );
	}

	/**
	 * @since $ver$
	 */
	public function testUnknownDestinationFallsBackToTheProductPage(): void {
		$url = Links::to( 'no_such_destination', 'plugin_meta_upgrade' );

		self::assertStringStartsWith( Schema::LINKS['destinations']['upgrade'], $url );
	}

	/**
	 * utm_content and the analytics cta_id are deliberately the same vocabulary,
	 * so a click in PostHog and a landing in Google Analytics carry the same
	 * identifier and can be joined without anything per-install.
	 *
	 * @since $ver$
	 */
	public function testUtmContentVocabularyIsTheAnalyticsCtaIdVocabulary(): void {
		$cta_ids = Schema::enum( 'cta_id' );

		self::assertIsArray( $cta_ids );
		self::assertNotEmpty( $cta_ids );

		foreach ( $cta_ids as $cta_id ) {
			$params = $this->query( Links::upgrade( $cta_id ) );

			self::assertSame(
				$cta_id,
				$params['utm_content'],
				sprintf( 'Registered cta_id "%s" was rejected by the link builder.', $cta_id )
			);
		}
	}

	/**
	 * No per-install identifier may travel in a URL. A landing-page hit would
	 * otherwise re-identify an install inside a system the consent card never
	 * mentioned, which is the same failure as reusing site_id as a discount token.
	 *
	 * @since $ver$
	 */
	public function testLinksCarryNoPerInstallIdentifier(): void {
		$url    = Links::upgrade( 'plugin_meta_upgrade' );
		$params = $this->query( $url );

		self::assertSame( [ 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content' ], array_keys( $params ) );

		foreach ( [ 'site_id', 'site', 'token', 'hash', 'salt' ] as $forbidden ) {
			self::assertStringNotContainsString( $forbidden, $url );
		}
	}

	/**
	 * @since $ver$
	 */
	public function testExistingQueryStringIsPreserved(): void {
		\WP_Mock::userFunction( 'apply_filters' )->andReturnUsing(
			static function () {
				$args = func_get_args();

				return $args[1];
			}
		);

		$url = Links::upgrade( 'plugin_meta_upgrade' );

		self::assertStringContainsString( '?utm_source=', $url );
		self::assertStringNotContainsString( '??', $url );
	}
}
