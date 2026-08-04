<?php

namespace GFExcel\Upsell;

/**
 * Puts the export history on the form's own export settings screen.
 *
 * The reason this exists is not decoration. A count that only ever appears
 * beside an offer reads as having been kept in order to make the offer; the
 * same count, on a screen the user opened themselves, is something the plugin
 * gives them. Showing it here first is what makes it fair to mention it later.
 *
 * It is shown to paying customers too. It is a feature, not a lever.
 *
 * @since $ver$
 */
class DownloadChart {
	/**
	 * How many days the chart covers.
	 *
	 * @since $ver$
	 */
	public const DAYS = 30;

	/**
	 * The export history.
	 *
	 * @since $ver$
	 * @var DownloadHistory
	 */
	private $history;

	/**
	 * @since $ver$
	 *
	 * @param DownloadHistory $history The export history.
	 */
	public function __construct( DownloadHistory $history ) {
		$this->history = $history;

		add_filter( 'gfexcel_general_settings', [ $this, 'addSection' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
	}

	/**
	 * Adds the chart as the first section of the Export Settings tab.
	 *
	 * @since $ver$
	 *
	 * @param array[] $sections The existing sections.
	 *
	 * @return array[] The sections.
	 */
	public function addSection( array $sections ): array {
		$form_id = $this->currentFormId();

		if ( $form_id < 1 || $this->history->isEmpty( $form_id ) ) {
			return $sections;
		}

		array_unshift( $sections, [
			'title'  => esc_html__( 'Exports over time', 'gk-gravityexport-lite' ),
			'fields' => [
				[
					'name' => 'gfexcel_download_chart',
					'type' => 'html',
					'html' => $this->markup( $form_id ),
				],
			],
		] );

		return $sections;
	}

	/**
	 * Loads Chart.js, but only on this screen.
	 *
	 * At roughly 200KB it is the largest asset in the plugin, and no other
	 * screen has anything to draw with it.
	 *
	 * @since $ver$
	 *
	 * @return void
	 */
	public function enqueue(): void {
		$form_id = $this->currentFormId();

		if ( $form_id < 1 || $this->history->isEmpty( $form_id ) ) {
			return;
		}

		$base    = plugin_dir_url( GFEXCEL_PLUGIN_FILE ) . 'public/js/';
		$version = defined( 'GFEXCEL_PLUGIN_VERSION' ) ? GFEXCEL_PLUGIN_VERSION : false;

		wp_enqueue_script( 'gfexcel-chartjs', $base . 'vendor/chart.umd.js', [], '4.4.9', true );
		wp_enqueue_script( 'gfexcel-download-chart', $base . 'download-chart.js', [ 'gfexcel-chartjs' ], $version, true );
	}

	/**
	 * Returns the chart markup.
	 *
	 * @since $ver$
	 *
	 * @param int $form_id The form id.
	 *
	 * @return string The markup.
	 */
	private function markup( int $form_id ): string {
		$series = $this->history->series( $form_id, self::DAYS );
		$total  = array_sum( $series );

		$summary = sprintf(
			/* translators: 1: number of exports, 2: number of days. */
			esc_html( _n(
				'%1$s export in the last %2$d days.',
				'%1$s exports in the last %2$d days.',
				$total,
				'gk-gravityexport-lite'
			) ),
			esc_html( number_format_i18n( $total ) ),
			self::DAYS
		);

		// The canvas is opaque to assistive technology, so the same data is also
		// rendered as a table. It is visually hidden rather than omitted: a chart
		// with no text equivalent is simply unavailable to a screen reader, and a
		// summary sentence alone loses the shape the chart exists to show.
		$rows = '';

		foreach ( $series as $day => $count ) {
			$rows .= sprintf(
				'<tr><th scope="row">%1$s</th><td>%2$s</td></tr>',
				esc_html( date_i18n( get_option( 'date_format' ), strtotime( $day ) ) ),
				esc_html( number_format_i18n( $count ) )
			);
		}

		$caption = sprintf(
			/* translators: %d: number of days. */
			esc_html__( 'Exports per day for the last %d days', 'gk-gravityexport-lite' ),
			self::DAYS
		);

		return sprintf(
			'<p class="description">%1$s %2$s</p>'
			. '<div style="height:180px">'
			. '<canvas data-gfexcel-download-chart data-series="%3$s" role="img" aria-label="%4$s"></canvas>'
			. '</div>'
			. '<table class="screen-reader-text"><caption>%5$s</caption>'
			. '<thead><tr><th scope="col">%6$s</th><th scope="col">%7$s</th></tr></thead>'
			. '<tbody>%8$s</tbody></table>',
			$summary,
			esc_html__( 'This is recorded on your site and is not sent anywhere.', 'gk-gravityexport-lite' ),
			esc_attr( (string) wp_json_encode( $series ) ),
			esc_attr( $caption . '. ' . wp_strip_all_tags( $summary ) ),
			$caption,
			esc_html__( 'Date', 'gk-gravityexport-lite' ),
			esc_html__( 'Exports', 'gk-gravityexport-lite' ),
			$rows
		);
	}

	/**
	 * Returns the form id being edited, or 0 when this is not that screen.
	 *
	 * @since $ver$
	 *
	 * @return int The form id.
	 */
	private function currentFormId(): int {
		if ( ! is_admin() || 'gravityexport-lite' !== rgget( 'subview' ) ) {
			return 0;
		}

		return (int) rgget( 'id' );
	}
}
