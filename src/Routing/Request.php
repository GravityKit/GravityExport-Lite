<?php

namespace GFExcel\Routing;

/**
 * Represents a request to the router.
 * @since 2.4.0
 */
final class Request {
	/**
	 * The action name.
	 *
	 * @since 2.4.0
	 *
	 * @var string
	 */
	private $action;

	/**
	 * The hash.
	 *
	 * @since 2.4.0
	 *
	 * @var string
	 */
	private $hash;

	/**
	 * Prevents instantiation.
	 *
	 * @since 2.4.0
	 */
	private function __construct() {
	}

	/**
	 * Named constructor for a Request from WordPress query vars.
	 *
	 * @since 2.4.0
	 *
	 * @param array $query_vars The query vars.
	 *
	 * @return self The Request.
	 */
	public static function from_query_vars( array $query_vars ): self {
		$request         = new self;
		$request->action = $query_vars[ Router::KEY_ACTION ] ?? '';
		$request->hash   = $query_vars[ Router::KEY_HASH ] ?? '';

		return $request;
	}

	/**
	 * Returns the hash.
	 *
	 * @since 2.4.0
	 *
	 * @return string
	 */
	public function hash(): string {
		// Remove the extension if it has one.
		return explode( '.', $this->hash )[0];
	}

	/**
	 * Get the requested file extension.
	 *
	 * @since 2.4.0
	 *
	 * @return string The extension or null if not available.
	 */
	public function extension(): ?string {
		$parts = explode( '.', $this->hash );

		$extension = strtolower( $parts[1] ?? '' );

		return $extension ?: null;
	}

	/**
	 * The recorded action.
	 *
	 * @since 2.4.0
	 *
	 * @return string
	 */
	public function action(): string {
		return $this->action;
	}
}
