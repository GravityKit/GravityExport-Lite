<?php

namespace GFExcel\Component;

use GFExcel\GFExcel;
use GravityKit\GravityExport\Addon\GravityExportAddon;

/**
 * MetaBoxes component that registers any meta boxes.
 * @since $ver$
 */
final class MetaBoxes {
	/**
	 * Registers the hooks.
	 * @since $ver$
	 */
	public function __construct() {
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
		if ( GFExcel::url( $form['id'] ) ) {
			$meta_boxes[] = [
				'title'    => esc_html__( 'GravityExport Lite', GFExcel::$slug ),
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
	public static function single_entry_download( array $args, array $metabox ) {
		$form  = \rgar( $args, 'form', [] );
		$entry = \rgar( $args, 'entry', [] );

		$html = '<div class="gfexcel_entry_download"><p>%s</p>%s</div>';

		// Get extensions, sorted by primary first.
		$extensions = array_unique( array_merge( [ GFExcel::getFileExtension( $form ) ], GFExcel::getPluginFileExtensions() ) );

		$url   = GFExcel::url( $form['id'] );
		$links = [];
		foreach ( $extensions as $i => $extension ) {
			$links[]= sprintf(
				'<a href="%s.%s?entry=%s" class="button %s">.%2$s</a>',
				$url,
				$extension,
				$entry['id'],
				$i ? 'secondary' : 'primary'
			);
		}

		printf(
			$html,
			esc_html__( 'Download this single entry as a file.', GFExcel::$slug ),
			implode(' ', $links)
		);
	}
}
