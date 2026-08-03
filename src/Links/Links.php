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
			return self::fallback();
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
	private static function tag( string $url, string $cta_id, string $campaign ): string {
		$known_cta      = (array) ( Schema::enum( 'cta_id' ) ?? [] );
		$known_campaign = (array) ( Schema::enum( 'link_campaign' ) ?? [] );

		$params = [
			'utm_source'   => Schema::LINKS['utm_source'],
			'utm_medium'   => Schema::LINKS['utm_medium'],
			'utm_campaign' => in_array( $campaign, $known_campaign, true ) ? $campaign : 'docs',
			'utm_content'  => in_array( $cta_id, $known_cta, true ) ? $cta_id : 'unregistered',
		];

		$separator = false === strpos( $url, '?' ) ? '?' : '&';

		/**
		 * Filters an outbound tagged link.
		 *
		 * @since $ver$
		 *
		 * @param string $tagged The tagged URL.
		 * @param string $url    The untagged destination.
		 * @param array  $params The campaign parameters.
		 */
		return apply_filters(
			'gk/links/outbound',
			$url . $separator . http_build_query( $params ),
			$url,
			$params
		);
	}

	/**
	 * Returns the product page, used when a destination key is unknown.
	 *
	 * @since $ver$
	 *
	 * @return string The tagged URL.
	 */
	private static function fallback(): string {
		return self::tag( Schema::LINKS['destinations']['upgrade'], 'unregistered', 'upgrade' );
	}
}
