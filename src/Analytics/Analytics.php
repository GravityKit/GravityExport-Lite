<?php

namespace GFExcel\Analytics;

/**
 * The analytics entry point. Every call site in the plugin uses this class.
 *
 * It lives in GFExcel\, which Strauss is configured never to prefix
 * (composer.json extra.strauss.exclude_from_prefix.namespaces), so the symbol
 * is identical in every build of this plugin. That is what makes the handover
 * clean: when Foundation reaches wordpress.org, Client and its collaborators
 * are deleted, this file stays, and every call site keeps working untouched.
 *
 * Delegation is duck-typed and never uses instanceof. Foundation is
 * Strauss-prefixed per product, so GravityKit\GravityView\Foundation\... and
 * GravityKit\GravityExport\Foundation\... are unrelated types; any cross-copy
 * type check fails by construction. Only the unprefixed GravityKitFoundation
 * alias is stable, and only method_exists() may be trusted on what it returns.
 *
 * Resolution is lazy. Foundation builds its components inside init at priority
 * 100, so anything resolved earlier would find nothing and wrongly conclude
 * Foundation is absent.
 *
 * @since $ver$
 */
final class Analytics {
	/**
	 * The resolved backend, or null when resolution has not succeeded yet.
	 *
	 * @since $ver$
	 * @var object|null
	 */
	private static $backend;

	/**
	 * Whether Foundation, rather than the local client, won resolution.
	 *
	 * @since $ver$
	 * @var bool
	 */
	private static $delegating = false;

	/**
	 * The locally-built fallback, used when Foundation is absent.
	 *
	 * @since $ver$
	 * @var Client|null
	 */
	private static $local;

	/**
	 * Registers the local implementation used when Foundation is not present.
	 *
	 * @since $ver$
	 *
	 * @param Client $client The local client.
	 *
	 * @return void
	 */
	public static function setLocalClient( Client $client ): void {
		self::$local = $client;
	}

	/**
	 * Captures an event.
	 *
	 * @since $ver$
	 *
	 * @param string $event The event name; a value from the schema registry.
	 * @param array  $props The event properties.
	 *
	 * @return void
	 */
	public static function capture( string $event, array $props = [] ): void {
		$backend = self::backend();

		if ( $backend && method_exists( $backend, 'capture' ) ) {
			$backend->capture( $event, $props );
		}
	}

	/**
	 * Records consent and emits the opt-in event.
	 *
	 * @since $ver$
	 *
	 * @param string $source     Where consent was collected.
	 * @param string $disclosure The exact wording shown to the user.
	 *
	 * @return void
	 */
	public static function optIn( string $source, string $disclosure ): void {
		$backend = self::backend();

		if ( ! $backend || ! method_exists( $backend, 'opt_in' ) ) {
			return;
		}

		// Foundation's opt_in() takes no arguments — it owns its own consent record
		// and wording — and PHP discards the extras, so this needs no branch.
		$backend->opt_in( $source, $disclosure );
	}

	/**
	 * Withdraws consent.
	 *
	 * @since $ver$
	 *
	 * @return void
	 */
	public static function optOut(): void {
		$backend = self::backend();

		if ( $backend && method_exists( $backend, 'opt_out' ) ) {
			$backend->opt_out();
		}
	}

	/**
	 * Returns true when analytics may send.
	 *
	 * @since $ver$
	 *
	 * @return bool Whether analytics is enabled.
	 */
	public static function isEnabled(): bool {
		$backend = self::backend();

		if ( ! $backend || ! method_exists( $backend, 'is_enabled' ) ) {
			return false;
		}

		return (bool) $backend->is_enabled();
	}

	/**
	 * Returns true when Foundation is providing the backend.
	 *
	 * @since $ver$
	 *
	 * @return bool Whether Foundation is handling analytics.
	 */
	public static function isDelegating(): bool {
		self::backend();

		return self::$delegating;
	}

	/**
	 * Resolves the backend, preferring Foundation when it is available.
	 *
	 * Not cached on failure: Foundation may not have booted yet, and caching a
	 * miss would bind this request to the local client forever.
	 *
	 * @since $ver$
	 *
	 * @return object|null The backend.
	 */
	private static function backend() {
		if ( null !== self::$backend ) {
			return self::$backend;
		}

		if ( is_callable( [ 'GravityKitFoundation', 'analytics' ] ) ) {
			$service = call_user_func( [ 'GravityKitFoundation', 'analytics' ] );

			if ( is_object( $service ) && method_exists( $service, 'capture' ) && method_exists( $service, 'is_enabled' ) ) {
				self::$backend    = $service;
				self::$delegating = true;

				return self::$backend;
			}
		}

		return self::$local;
	}

	/**
	 * Resets resolution. Test seam only.
	 *
	 * @since $ver$
	 *
	 * @return void
	 */
	public static function reset(): void {
		self::$backend    = null;
		self::$local      = null;
		self::$delegating = false;
	}
}
