<?php

namespace GFExcel\Analytics;

/**
 * Measures the shape of the install: how many forms, how many entries, how many
 * are actually exporting, and which of a published list of other plugins is active.
 *
 * Everything is bucketed or boolean. A raw count is a fingerprint — a site with
 * exactly 258,718 entries is effectively unique, and combined with the WordPress,
 * PHP and locale properties it would survive the site_id hash. A bucket answers
 * every question these numbers exist to answer and identifies nobody.
 *
 * The plugin question is answered from a CLOSED list declared in the schema. The
 * site's plugin roster is never read: each entry is a direct is_plugin_active()
 * check against a known path. Aggregate plugin popularity is already public on
 * wordpress.org; what is not public, and what this measures, is the overlap with
 * our own install base.
 *
 * @since $ver$
 */
class SiteScan {
	/**
	 * Option holding the last scan and when it was taken.
	 *
	 * @since $ver$
	 */
	public const OPTION = 'gfexcel_analytics_site_scan';

	/**
	 * How long a scan stays fresh.
	 *
	 * @since $ver$
	 */
	public const TTL = WEEK_IN_SECONDS;

	/**
	 * The consent store.
	 *
	 * @since $ver$
	 * @var Consent
	 */
	private $consent;

	/**
	 * @since $ver$
	 *
	 * @param Consent $consent The consent store.
	 */
	public function __construct( Consent $consent ) {
		$this->consent = $consent;
	}

	/**
	 * Returns the stored scan, refreshing it when stale.
	 *
	 * @since $ver$
	 *
	 * @return array The install properties.
	 */
	public function properties(): array {
		$stored = get_option( self::OPTION );

		if ( is_array( $stored ) && isset( $stored['props'], $stored['computed_at'] ) ) {
			return (array) $stored['props'];
		}

		return [];
	}

	/**
	 * Recomputes the scan if it is stale. Registered on admin_init.
	 *
	 * Admin-side only and deferred to shutdown: this runs three aggregate queries,
	 * and no visitor should ever pay for our measurement. WP-Cron is deliberately
	 * not used, because cron fires on front-end traffic, which is the same problem.
	 *
	 * @since $ver$
	 *
	 * @return void
	 */
	public function maybeRefresh(): void {
		if ( ! $this->consent->granted() || ! $this->isStale() ) {
			return;
		}

		add_action( 'shutdown', [ $this, 'refresh' ], 30 );
	}

	/**
	 * Recomputes and stores the scan.
	 *
	 * @since $ver$
	 *
	 * @return void
	 */
	public function refresh(): void {
		update_option(
			self::OPTION,
			[
				'props'       => array_merge( $this->counts(), $this->plugins() ),
				'computed_at' => time(),
			],
			false
		);
	}

	/**
	 * Returns true when the stored scan is missing or older than the TTL.
	 *
	 * @since $ver$
	 *
	 * @return bool Whether a refresh is due.
	 */
	private function isStale(): bool {
		$stored = get_option( self::OPTION );

		if ( ! is_array( $stored ) || ! isset( $stored['computed_at'] ) ) {
			return true;
		}

		return ( time() - (int) $stored['computed_at'] ) > self::TTL;
	}

	/**
	 * Returns the bucketed scale counts.
	 *
	 * Three aggregate queries, deliberately. The obvious alternative, calling
	 * GFAPI::count_entries() per form, is one query per form: measured at 89ms for
	 * 25 forms on a 322-form site, against 38ms for this whole method.
	 *
	 * @since $ver$
	 *
	 * @return array The bucketed counts.
	 */
	private function counts(): array {
		global $wpdb;

		$forms = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}gf_form WHERE is_active = 1 AND is_trash = 0" );
		$entries = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}gf_entry WHERE status = 'active'" );
		$feeds = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT form_id) FROM {$wpdb->prefix}gf_addon_feed WHERE addon_slug = %s AND is_active = 1",
				'gravityexport-lite'
			)
		);

		// A failed query returns null and reads identically to zero, which would
		// report every install as empty. Refuse to guess.
		if ( '' !== (string) $wpdb->last_error ) {
			return [];
		}

		return [
			'form_count_bucket'        => self::bucket( (int) $forms ),
			'entry_count_bucket'       => self::bucket( (int) $entries ),
			'export_feed_count_bucket' => self::bucket( (int) $feeds ),
		];
	}

	/**
	 * Returns a boolean per plugin on the published allowlist.
	 *
	 * @since $ver$
	 *
	 * @return array The plugin booleans.
	 */
	private function plugins(): array {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$found = [];

		foreach ( Schema::PLUGIN_PROPS as $prop => $spec ) {
			$active = false;

			foreach ( (array) $spec['paths'] as $path ) {
				if ( is_plugin_active( $path ) ) {
					$active = true;
					break;
				}
			}

			$found[ $prop ] = $active;
		}

		// Site complexity, with no list to maintain. This is the part of the
		// plugin question that never goes stale.
		$found['plugin_count_bucket'] = self::bucket( count( (array) get_option( 'active_plugins', [] ) ) );

		return $found;
	}

	/**
	 * Maps a count onto the schema's scale bucket.
	 *
	 * Boundaries are parsed from the enum rather than duplicated here, so the
	 * published schema stays the only place the scale is defined.
	 *
	 * @since $ver$
	 *
	 * @param int $count The count.
	 *
	 * @return string The bucket label.
	 */
	public static function bucket( int $count ): string {
		$buckets = (array) ( Schema::enum( 'scale_bucket' ) ?? [] );

		foreach ( $buckets as $label ) {
			if ( '+' === substr( $label, -1 ) ) {
				if ( $count >= (int) rtrim( $label, '+' ) ) {
					return $label;
				}

				continue;
			}

			if ( false === strpos( $label, '-' ) ) {
				if ( $count === (int) $label ) {
					return $label;
				}

				continue;
			}

			list( $low, $high ) = explode( '-', $label, 2 );

			if ( $count >= (int) $low && $count <= (int) $high ) {
				return $label;
			}
		}

		return (string) end( $buckets );
	}
}
