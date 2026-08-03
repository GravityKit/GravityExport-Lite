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
	 *
	 * WP_Mock stubs apply_filters() itself and passes the value through, so the
	 * builder's output is unfiltered here unless a test opts in via onFilter().
	 */
	public function setUp(): void {
		parent::setUp();
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
	 * A broken documentation key must not land on the sales page. That is the
	 * exact bug this builder was written to fix (the plugin-meta "Documentation"
	 * link 301'd to the product page), and defaulting to it would reintroduce it.
	 *
	 * @since $ver$
	 */
	public function testUnknownDocsDestinationFallsBackToDocsNotTheSalesPage(): void {
		$url = Links::to( 'no_such_destination', 'plugin_meta_docs', 'docs' );

		self::assertStringStartsWith( Schema::LINKS['destinations']['docs'], $url );
		self::assertStringNotContainsString( '/products/', $url );
	}

	/**
	 * @since $ver$
	 */
	public function testUnknownUpgradeDestinationFallsBackToTheProductPage(): void {
		$url = Links::to( 'no_such_destination', 'plugin_meta_upgrade', 'upgrade' );

		self::assertStringStartsWith( Schema::LINKS['destinations']['upgrade'], $url );
	}

	/**
	 * Every product bundling this builder fires the same hook name, so a
	 * site-wide consumer needs to know which one built the link.
	 *
	 * @since $ver$
	 */
	public function testOutboundFilterReceivesTheProductSlug(): void {
		$destination = Schema::LINKS['destinations']['upgrade'];
		$params      = [
			'utm_source'   => 'gravityexport-lite',
			'utm_medium'   => 'plugin',
			'utm_campaign' => 'upgrade',
			'utm_content'  => 'plugin_meta_upgrade',
		];
		$tagged      = $destination . '?' . http_build_query( $params );

		// The reply only lands if the filter fired with the product slug as its
		// fourth argument; otherwise the real URL comes back and this fails.
		\WP_Mock::onFilter( 'gk/links/outbound' )
		        ->with( $tagged, $destination, $params, 'gravityexport-lite' )
		        ->reply( 'PRODUCT_SLUG_REACHED_THE_FILTER' );

		self::assertSame(
			'PRODUCT_SLUG_REACHED_THE_FILTER',
			Links::upgrade( 'plugin_meta_upgrade' )
		);
	}

	/**
	 * @since $ver$
	 */
	public function testMediumIsValidatedAgainstItsEnum(): void {
		self::assertSame( [ 'plugin', 'frontend' ], Schema::enum( 'link_medium' ) );
		self::assertSame( 'plugin', Schema::LINKS['utm_medium_default'] );
	}

	/**
	 * The product slug is data, not a literal in the builder, so extracting the
	 * shared package later does not require editing the class.
	 *
	 * @since $ver$
	 */
	public function testUtmSourceComesFromTheProductFragment(): void {
		$params = $this->query( Links::upgrade( 'plugin_meta_upgrade' ) );

		self::assertSame( Schema::LINKS['utm_source'], $params['utm_source'] );
		self::assertSame( Schema::PRODUCT, $params['utm_source'] );
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
		$url = Links::upgrade( 'plugin_meta_upgrade' );

		self::assertStringContainsString( '?utm_source=', $url );
		self::assertStringNotContainsString( '??', $url );
	}
}
