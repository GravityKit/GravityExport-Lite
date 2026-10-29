<?php

/**
 * The `WP_Http` status constants that {@see \GFExcel\GFExcel::request()} reads, for tests that run without WordPress.
 *
 * @since 2.7.6
 */
class WP_Http {
	const FORBIDDEN = 403;
	const NOT_FOUND = 404;
}
