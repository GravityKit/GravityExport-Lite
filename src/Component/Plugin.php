<?php

namespace GFExcel\Component;

use GFExcel\GFExcel;

/**
 * Plugin component that handles all plugin specific hooks.
 * @since $ver$
 */
final class Plugin {
	/**
	 * Registers the plugin hooks.
	 * @since $ver$
	 */
	public function __construct() {
		add_filter( 'plugin_row_meta', \Closure::fromCallable( [ $this, 'plugin_row_meta' ] ), 10, 2 );
	}

	/**
	 * Shows row meta on the plugin screen.
	 *
	 * @param array $links Plugin Row Meta.
	 * @param string $file Plugin Base file.
	 *
	 * @return array
	 */
	private function plugin_row_meta( array $links, string $file ): array {
		if ( plugin_basename( GFEXCEL_PLUGIN_FILE ) !== $file ) {
			return $links;
		}

		return array_merge( $links, [
			'docs'   => sprintf( '<a href="%s" aria-label="%s" target="_blank">%s</a>',
				esc_url( 'https://gfexcel.com/docs/getting-started/' ),
				esc_attr__( 'Documentation', 'gk-gravityexport-lite' ),
				esc_html__( 'Documentation', 'gk-gravityexport-lite' )
			),
			'donate' => sprintf( '<a href="%s" aria-label="%s" target="_blank">%s</a>',
				esc_url( 'https://www.paypal.me/GravityView' ),
				esc_attr__( 'Make a donation', 'gk-gravityexport-lite' ),
				esc_html__( 'Make a donation', 'gk-gravityexport-lite' )
			),
		] );
	}
}
