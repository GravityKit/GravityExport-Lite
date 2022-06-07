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
		add_filter( 'plugin_action_links', \Closure::fromCallable( [ $this, 'plugin_action_links' ] ), 10, 2 );
	}

	/**
	 * Adds the settings link to the plugin row.
	 * @since 1.8.0
	 *
	 * @param string[] $actions The action links.
	 * @param string $plugin_file The name of the plugin file.
	 *
	 * @return string[] The new action links.
	 */
	private function plugin_action_links( array $actions, string $plugin_file ): array {
		if ( plugin_basename( GFEXCEL_PLUGIN_FILE ) !== $plugin_file ) {
			return $actions;
		}

		// Already has GravityExport
		if ( class_exists( 'GravityKit\GravityExport\GravityExport' ) ) {
			return $actions;
		}

		// Lite is active
		if ( array_key_exists( 'deactivate', $actions ) ) {
			$actions[] = implode( '', [
				'<a target="_blank" rel="nofollow noopener" href="https://gravityview.co/extensions/gravityexport/"><b>⚡️ ',
				esc_html__( 'Gain Access to More Features', 'gk-gravityexport' ),
				'</b></a>',
			] );
		}

		return $actions;
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
				esc_attr__( 'Documentation', GFExcel::$slug ),
				esc_html__( 'Documentation', GFExcel::$slug )
			),
			'donate' => sprintf( '<a href="%s" aria-label="%s" target="_blank">%s</a>',
				esc_url( 'https://www.paypal.me/GravityView' ),
				esc_attr__( 'Make a donation', GFExcel::$slug ),
				esc_html__( 'Make a donation', GFExcel::$slug )
			),
		] );
	}
}
