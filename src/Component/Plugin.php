<?php

namespace GFExcel\Component;

/**
 * Plugin component that handles all plugin specific hooks.
 * @since 2.0.0
 */
final class Plugin {
	/**
	 * Registers the plugin hooks.
	 * @since 2.0.0
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

		$add_links = [
			'docs' => sprintf( '<a href="%s" title="%s" target="_blank" rel="noreferrer noopener">%s</a>',
				'https://gfexcel.com/docs/getting-started/',
				esc_attr__( 'Documentation', 'gk-gravityexport-lite' ),
				esc_html__( 'Documentation', 'gk-gravityexport-lite' )
			),
		];

		// Not running GravityExport
		if ( ! defined( 'GK_GRAVITYEXPORT_PLUGIN_VERSION' ) ) {
			$add_links['upgrade'] = sprintf( '<a href="%s" title="%s" target="_blank" rel="noreferrer noopener">%s</a>',
				'https://www.gravitykit.com/extensions/gravityexport/?utm_source=plugin&utm_campaign=gravityexport-lite&utm_content=plugin-meta-upgrade',
				esc_attr__( 'This link opens in a new window', 'gk-gravityexport-lite' ),
				'<strong>⚡&nbsp;' . esc_html__( 'Gain Powerful Features with GravityExport', 'gk-gravityexport-lite' ) . '</strong>'
			);
		}

		return array_merge( $links, $add_links );
	}
}
