<?php

namespace GFExcel\GravityForms\Field;

use GFExcel\GFExcel;
use Gravity_Forms\Gravity_Forms\Settings\Fields\Base;

/**
 * A field that represents a form to download the file.
 * @since $ver$
 */
class DownloadFile extends Base {
	/**
	 * Whether the form was already rendered.
	 * @since $ver$
	 * @var bool
	 */
	private $is_rendered = false;

	/**
	 * The url for the download form.
	 * @since $ver$
	 * @var string
	 */
	public $url = '';

	/**
	 * @inheritdoc
	 * @since $ver$
	 */
	public function __construct( $props, $settings ) {
		parent::__construct( $props, $settings );

		// Add after settings form.
		add_filter( 'gk-gravityexport-after_feed_edit_page', \Closure::fromCallable( [ $this, 'render_form' ] ) );
	}

	/**
	 * @inheritdoc
	 * @since $ver$
	 */
	public function markup() {
		$html = '<div class="download-block">
                <div class="date-field">
                    <input form="%1$s" type="text" id="start_date" name="start_date" />
                    <label for="start_date">' . esc_html__( 'Start', 'gravityforms' ) . '</label>
                </div>

                <div class="date-field">
                    <input form="%1$s" type="text" id="end_date" name="end_date" />
                    <label for="end_date">' . esc_html__( 'End', 'gravityforms' ) . '</label>
                </div>

                <div class="download-button">
                    <button type="submit" form="%1$s" class="button primary button-primary">' . esc_html__( 'Download',
				GFExcel::$slug ) . '</button>
                </div>
            </div>';

		return sprintf( $html, $this->get_parsed_name() );
	}

	/**
	 * Helper method to render the download form.
	 * @since $ver$
	 */
	private function render_form() {
		if ( $this->is_rendered ) {
			return;
		}

		$this->is_rendered = true;

		printf(
			'<form method="post" action="%s" id="%s" target="_blank"></form>',
			$this->url,
			$this->get_parsed_name()
		);
	}
}
