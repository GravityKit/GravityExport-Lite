<?php

namespace GFExcel\Routing;

use GFExcel\Addon\GravityExportAddon;

/**
 * Represents a WordPress router for GravityExport Lite.
 * @since 2.4.0
 */
final class WordPressRouter implements Router {
	/**
	 * The slug used in the generated URL.
	 *
	 * @since 2.4.0
	 *
	 * @var string
	 */
	public const DEFAULT_ACTION = 'gravityexport-lite';

	/**
	 * Instantiates the router.
	 *
	 * @since 2.4.0
	 */
	public function __construct() {
		add_filter( 'query_vars', [ $this, 'update_query_vars' ] );
	}

	/**
	 * @inheritDoc
	 * @since 2.4.0
	 */
	public function init(): void {
		$this->register_endpoints( $this->endpoints() );
	}

	/**
	 * @inheritDoc
	 * @since 2.4.0
	 */
	public function matches( Request $request ): bool {
		return in_array( $request->action(), $this->endpoints(), true );
	}


	/**
	 * Registers the permalink structures for the download
	 *
	 * @since 1.0.0
	 */
	public function register_endpoints( array $endpoints ): void {
		$rewrite_rules = get_option( 'rewrite_rules' );
		$flush_rules   = false;

		foreach ( $endpoints as $endpoint ) {
			$endpoint_regex = '^' . $endpoint . '/(.+)/?$';

			add_rewrite_rule(
				$endpoint_regex,
				'index.php?' . Router::KEY_ACTION . '=' . $endpoint . '&' . Router::KEY_HASH . '=$matches[1]',
				'top'
			);

			if ( ! isset( $rewrite_rules[ $endpoint_regex ] ) ) {
				$flush_rules = true;
			}
		}

		if ( $flush_rules ) {
			flush_rewrite_rules();
		}
	}

	/**
	 * @inheritDoc
	 * @since 2.4.0
	 */
	public function get_url_for_form( int $form_id ): ?string {
		$hash = $this->get_hash_for_form( $form_id );

		if ( ! $hash ) {
			return null;
		}

		return $this->get_url_for_hash( $hash );
	}

	/**
	 * Helper method to retrieve feed data using unique URL hash value.
	 *
	 * @since 1.9
	 *
	 * @return array|null Feed data.
	 */
	public function get_feed_by_request( Request $request ): ?array {
		global $wpdb;

		$hash = $request->hash();

		$feeds = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}gf_addon_feed WHERE is_active=1 AND meta LIKE '%s' ORDER BY `feed_order`, `id` LIMIT 1",
			'%' . $wpdb->esc_like( $hash ) . '%'
		), ARRAY_A );

		$feed = reset( $feeds );

		if ( ! $feed || ! isset( $feed['meta'] ) ) {
			return apply_filters( 'gfexcel_hash_feed', null, $hash );
		}

		$feed['meta'] = json_decode( $feed['meta'], true );

		return $hash === rgars( $feed, 'meta/hash' ) ? $feed : null;
	}


	/**
	 * Returns the download hash for a form.
	 *
	 * @since 2.4.0
	 *
	 * @param int $form_id the form id to get the hash for.
	 *
	 * @return string|null the hash
	 */
	public function get_hash_for_form( int $form_id ): ?string {
		if ( ! \GFAPI::form_id_exists( $form_id ) ) {
			return null;
		}

		$addon = GravityExportAddon::get_instance();
		if ( $hash = $addon->get_feed_meta_field( 'hash', $form_id ) ) {
			return $hash;
		}

		$meta = \GFFormsModel::get_form_meta( $form_id );
		if ( ! isset( $meta[ self::KEY_HASH ] ) || empty( $meta[ self::KEY_HASH ] ) ) {
			return null;
		}

		return $meta[ self::KEY_HASH ];
	}

	/**
	 * @inheritDoc
	 * @since 2.4.0
	 */
	public function endpoints(): array {
		return [
			'gf-entries-in-excel',
			'gravityexport-lite',
			'gravityexport',
		];
	}

	/**
	 * Adds the query vars for the permalink.
	 * @since 1.0.0
	 *
	 * @param string[] $vars The original query vars.
	 *
	 * @return string[] The new query vars.
	 */
	public function update_query_vars( array $vars ): array {
		return array_merge( $vars, [
			Router::KEY_ACTION,
			Router::KEY_HASH,
		] );
	}

	/**
	 * @inheritDoc
	 * @since 2.4.0
	 */
	public function get_url_for_hash( string $hash ): string {
		$blogurl   = get_bloginfo( 'url' );
		$permalink = '/index.php?' . self::KEY_ACTION . '=%s&' . self::KEY_HASH . '=%s';

		if ( get_option( 'permalink_structure' ) ) {
			$permalink = '/%s/%s';
		} else {
			$hash = urlencode( $hash );
		}

		return $blogurl . sprintf( $permalink, self::DEFAULT_ACTION, $hash );
	}
}
