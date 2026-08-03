<?php

namespace GFExcel\Links;

use GFExcel\Analytics\Schema;

/**
 * Builds every outbound gravitykit.com link, with campaign tagging attached.
 *
 * Tagging is applied here rather than at each call site because the previous
 * approach — hand-written query strings — produced three different schemes
 * across GravityView, Foundation and this plugin, with no two keys meaning the
 * same thing, so nothing aggregated. A URL cannot be built through this class
 * without being tagged, and `composer links:verify` fails the build on a raw
 * gravitykit.com URL written anywhere else.
 *
 * The scheme follows standard UTM semantics:
 *
 *   utm_source   the product that sent the traffic  (gravityexport-lite)
 *   utm_medium   the channel                        (plugin)
 *   utm_campaign the intent                         (upgrade | docs | support)
 *   utm_content  the specific element               (a cta_id from the registry)
 *
 * `utm_content` deliberately reuses the analytics `cta_id` vocabulary, so the
 * same identifier appears in PostHog and in Google Analytics and the two can be
 * joined in aggregate. It carries no per-install identifier: `site_id` must
 * never travel in a URL, or a landing-page hit would re-identify an install
 * inside a system the consent card never mentioned.
 *
 * Destinations are stored as final canonical URLs. The legacy gfexcel.com
 * redirect drops the query string, so a tagged link routed through it arrives
 * untagged and the campaign data is silently lost.
 *
 * @since $ver$
 */
final class Links {
	/**
	 * Returns a tagged URL for a named destination.
	 *
	 * @since $ver$
	 *
	 * @param string $destination A key from the schema's link destinations.
	 * @param string $cta_id      The element the link sits in; a registered cta_id.
	 * @param string $campaign    The intent; a registered link_campaign.
	 *
	 * @return string The tagged URL.
	 */
	public static function to( string $destination, string $cta_id, string $campaign = 'docs' ): string {
		$url = Schema::LINKS['destinations'][ $destination ] ?? null;

		if ( null === $url ) {
			return self::fallback( $cta_id, $campaign );
		}

		return self::tag( $url, $cta_id, $campaign );
	}

	/**
	 * Returns the tagged upgrade URL.
	 *
	 * @since $ver$
	 *
	 * @param string $cta_id The element the link sits in.
	 *
	 * @return string The tagged URL.
	 */
	public static function upgrade( string $cta_id ): string {
		return self::to( 'upgrade', $cta_id, 'upgrade' );
	}

	/**
	 * Returns a tagged documentation URL.
	 *
	 * @since $ver$
	 *
	 * @param string $cta_id      The element the link sits in.
	 * @param string $destination The documentation destination key.
	 *
	 * @return string The tagged URL.
	 */
	public static function docs( string $cta_id, string $destination = 'docs' ): string {
		return self::to( $destination, $cta_id, 'docs' );
	}

	/**
	 * Appends the campaign parameters to a URL.
	 *
	 * Unregistered values are replaced rather than passed through. A typo would
	 * otherwise create a new bucket in the reporting that looks like real
	 * traffic, which is worse than an obviously wrong one.
	 *
	 * @since $ver$
	 *
	 * @param string $url      The destination URL.
	 * @param string $cta_id   The element the link sits in.
	 * @param string $campaign The intent.
	 *
	 * @return string The tagged URL.
	 */
	private static function tag( string $url, string $cta_id, string $campaign, string $medium = 'plugin' ): string {
		$params = [
			'utm_source'   => Schema::LINKS['utm_source'],
			'utm_medium'   => self::enumOr( 'link_medium', $medium, Schema::LINKS['utm_medium_default'] ),
			'utm_campaign' => self::enumOr( 'link_campaign', $campaign, 'docs' ),
			'utm_content'  => self::enumOr( 'cta_id', $cta_id, 'unregistered' ),
		];

		$separator = false === strpos( $url, '?' ) ? '?' : '&';

		/**
		 * Filters an outbound tagged link.
		 *
		 * Carries the product slug because every GravityKit product that bundles this
		 * builder fires this same hook name. Without it a site-wide consumer cannot
		 * tell which product built the link.
		 *
		 * @since $ver$
		 *
		 * @param string $tagged  The tagged URL.
		 * @param string $url     The untagged destination.
		 * @param array  $params  The campaign parameters.
		 * @param string $product The product slug that built the link.
		 */
		return apply_filters(
			'gk/links/outbound',
			$url . $separator . http_build_query( $params ),
			$url,
			$params,
			Schema::PRODUCT
		);
	}

	/**
	 * Returns the value when it is legal for the enum, otherwise the fallback.
	 *
	 * An unregistered value is replaced rather than passed through: a typo would
	 * otherwise open a new bucket in the reporting that looks like real traffic,
	 * which is harder to notice than an obviously wrong one.
	 *
	 * @since $ver$
	 *
	 * @param string $enum     The enum name.
	 * @param string $value    The candidate value.
	 * @param string $fallback The value to use when the candidate is unregistered.
	 *
	 * @return string The resolved value.
	 */
	private static function enumOr( string $enum, string $value, string $fallback ): string {
		$legal = (array) ( Schema::enum( $enum ) ?? [] );

		if ( in_array( $value, $legal, true ) ) {
			return $value;
		}

		self::warn( sprintf( 'Unregistered %s "%s"; using "%s".', $enum, $value, $fallback ) );

		return $fallback;
	}

	/**
	 * Returns a destination appropriate to the campaign when the key is unknown.
	 *
	 * Falls back within the intent rather than to a single page. Sending someone
	 * who followed a broken documentation link to the sales page is the same bug
	 * this builder was written to fix, reintroduced as a default.
	 *
	 * @since $ver$
	 *
	 * @param string $cta_id   The element the link sits in.
	 * @param string $campaign The intent.
	 *
	 * @return string The tagged URL.
	 */
	private static function fallback( string $cta_id, string $campaign ): string {
		self::warn( sprintf( 'Unknown link destination for campaign "%s".', $campaign ) );

		$key = 'upgrade' === $campaign ? 'upgrade' : 'docs';

		return self::tag( Schema::LINKS['destinations'][ $key ], $cta_id, $campaign );
	}

	/**
	 * Logs a misuse under WP_DEBUG.
	 *
	 * The build gate catches raw URLs, but a typo'd key is legal PHP that ships
	 * quietly, so it needs a runtime signal the same way dropped analytics
	 * properties do.
	 *
	 * @since $ver$
	 *
	 * @param string $message The message.
	 *
	 * @return void
	 */
	private static function warn( string $message ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'GravityExport links: ' . $message );
		}
	}
}
