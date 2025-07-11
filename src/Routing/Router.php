<?php

namespace GFExcel\Routing;

/**
 * Represents an HTTP router for GravityExport Lite.
 * @since 2.4.0
 */
interface Router {
	/**
	 * The query keys.
	 *
	 * @since 2.4.0
	 */
	public const KEY_HASH = 'gfexcel_hash';
	public const KEY_ACTION = 'gfexcel_action';

	/**
	 * Initializes the router lazily.
	 *
	 * @since 2.4.0
	 */
	public function init(): void;

	/**
	 * Get the endpoints for this router.
	 *
	 * @since 2.4.0
	 *
	 * @return string[] The endpoints.
	 */
	public function endpoints(): array;

	/**
	 * Get the download URL for a specific form.
	 *
	 * @since 2.4.0
	 *
	 * @param int $form_id The form ID.
	 *
	 * @return string|null The download URL or null if not available.
	 */
	public function get_url_for_form( int $form_id ): ?string;

	/**
	 * Returns the download URL for a specific hash.
	 *
	 * @param string $hash The hash.
	 *
	 * @return string The download URL.
	 */
	public function get_url_for_hash( string $hash ): string;

	/**
	 * Check if the router can handle the given request.
	 *
	 * @since 2.4.0
	 *
	 * @param Request $request The Request object to match against.
	 *
	 * @return bool True if this router can handle the request.
	 */
	public function matches( Request $request ): bool;

	/**
	 * Get the feed by the request.
	 *
	 * @since 2.4.0
	 *
	 * @param Request $request The Request object to match against.
	 *
	 * @return array|null The feed or null if not available.
	 */
	public function get_feed_by_request( Request $request ): ?array;
}
