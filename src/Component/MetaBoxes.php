<?php

namespace GFExcel\Component;

use GFExcel\GFExcel;
use GFExcel\Routing\Router;

/**
 * MetaBoxes component that registers any meta boxes.
 * @since 2.0.0
 */
final class MetaBoxes {
	/**
	 * The router.
	 *
	 * @since 2.4.0
	 *
	 * @var Router
	 */
	private $router;

	/**
	 * Registers the hooks.
	 * @since 2.0.0
	 */
	public function __construct( Router $router ) {
		$this->router = $router;

		add_filter(
			'gform_entry_detail_meta_boxes',
			\Closure::fromCallable( [ $this, 'gform_entry_detail_meta_boxes' ] ),
			10,
			3
		);
	}

	/**
	 * Registers the meta boxes for the entry detail page.
	 * @since 1.7.0
	 *
	 * @param array $meta_boxes The metaboxes.
	 * @param array $lead the lead data.
	 * @param array $form the form data.
	 *
	 * @return array All the meta boxes.
	 */
	public function gform_entry_detail_meta_boxes( array $meta_boxes, array $lead, array $form ): array {
		$url = $this->router->get_url_for_form( $form['id'] );
		if ( $url ) {
			$meta_boxes[] = [
				'title'    => defined( 'GK_GRAVITYEXPORT_PLUGIN_VERSION' ) ? 'GravityExport' : 'GravityExport Lite',
				'callback' => \Closure::fromCallable( [ $this, 'single_entry_download' ] ),
				'context'  => 'side',
				'priority' => 'high',
			];
		}

		return $meta_boxes;
	}

	/**
	 * Adds a download button for a single entry on the entry detail page.
	 * @since 1.7.0
	 *
	 * @param array $args arguments from metabox.
	 * @param array $metabox the metabox information.
	 */
	public function single_entry_download( array $args, array $metabox ) {
		$form  = \rgar( $args, 'form', [] );
		$entry = \rgar( $args, 'entry', [] );

		$html = '<div class="gfexcel_entry_download"><p>%s</p>%s</div>';

		// Get extensions, sorted by primary first.
		$extensions = array_unique( array_merge( [ GFExcel::getFileExtension( $form ) ], GFExcel::getPluginFileExtensions() ) );

		$url = $this->router->get_url_for_form( $form['id'] );
		$links = [];
		foreach ( $extensions as $i => $extension ) {
			$links[] = sprintf(
				'<a href="%1$s" class="button %2$s">.%3$s</a>',
				esc_url( add_query_arg( [ 'entry' => (int) $entry['id'] ], $url . '.' . $extension ) ),
				$i ? 'secondary' : 'primary',
				$extension
			);
		}

		printf(
			$html,
			esc_html__( 'Download this single entry as a file.', 'gk-gravityexport-lite' ),
			implode( ' ', $links )
		);
	}
}
