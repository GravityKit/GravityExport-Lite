<?php

namespace GFExcel\Analytics;

use GFExcel\GFExcel;
use GFExcel\GFExcelConfigConstants;
use GFExcel\GFExcelOutput;

/**
 * Emits the `export_completed` activation event.
 *
 * Deliberately does NOT capture inside the download hook. That hook is
 * documented as running "before download has been rendered", so capturing
 * there would count a render that subsequently throws — inflating the
 * north-star activation metric with failures. The event is staged on the hook
 * and only sent on shutdown, once the request has demonstrably survived.
 *
 * Three further filters apply, because this URL is public and anonymous:
 * a fatal error suppresses the event, obvious bot traffic is excluded, and
 * repeat downloads of the same form on the same day count once. Without those
 * the metric measures crawler behaviour rather than customer behaviour.
 *
 * @since $ver$
 */
class DownloadCompletedListener {
	/**
	 * Properties staged during the request, sent on shutdown.
	 *
	 * @since $ver$
	 * @var array|null
	 */
	private $pending;

	/**
	 * Registers the hooks.
	 *
	 * @since $ver$
	 */
	public function __construct() {
		add_action( GFExcelConfigConstants::GFEXCEL_EVENT_DOWNLOAD, [ $this, 'stage' ], 10, 2 );
		add_action( 'shutdown', [ $this, 'send' ], 10 );
	}

	/**
	 * Records what would be sent, without sending it.
	 *
	 * @since $ver$
	 *
	 * @param int|string         $form_id The form id.
	 * @param GFExcelOutput|null $output  The output being rendered.
	 *
	 * @return void
	 */
	public function stage( $form_id, $output = null ): void {
		if ( ! Analytics::isEnabled() || $this->isBot() ) {
			return;
		}

		$columns = $output instanceof GFExcelOutput ? $output->getColumns() : [];

		$this->pending = [
			'form_id' => (int) $form_id,
			'props'   => [
				'object_type'  => 'export',
				'file_format'  => (string) GFExcel::getFileExtension( [ 'id' => $form_id ] ),
				'column_count' => count( $columns ),
			],
		];
	}

	/**
	 * Sends the staged event, unless the request died on the way here.
	 *
	 * @since $ver$
	 *
	 * @return void
	 */
	public function send(): void {
		if ( null === $this->pending || $this->requestFailed() ) {
			return;
		}

		$pending       = $this->pending;
		$this->pending = null;

		if ( ! $this->claimDailySlot( $pending['form_id'] ) ) {
			return;
		}

		Analytics::capture( 'export_completed', $pending['props'] );
	}

	/**
	 * Returns true when the request ended in a fatal error.
	 *
	 * @since $ver$
	 *
	 * @return bool Whether the request failed.
	 */
	private function requestFailed(): bool {
		$error = error_get_last();

		if ( null === $error ) {
			return false;
		}

		return in_array( $error['type'], [ E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR ], true );
	}

	/**
	 * Claims the once-per-form-per-day slot, returning false when already taken.
	 *
	 * @since $ver$
	 *
	 * @param int $form_id The form id.
	 *
	 * @return bool Whether this request owns the slot.
	 */
	private function claimDailySlot( int $form_id ): bool {
		$key = 'gk_ax_export_' . $form_id . '_' . gmdate( 'Ymd' );

		if ( get_transient( $key ) ) {
			return false;
		}

		set_transient( $key, 1, DAY_IN_SECONDS );

		return true;
	}

	/**
	 * Returns true for obvious automated traffic.
	 *
	 * The download URL is public and the plugin already Disallows it in
	 * robots.txt, so anything reaching it that announces itself as a crawler
	 * is ignoring that and should not count as a customer action.
	 *
	 * @since $ver$
	 *
	 * @return bool Whether the caller looks automated.
	 */
	private function isBot(): bool {
		$agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

		if ( ! is_string( $agent ) || '' === $agent ) {
			return true;
		}

		return 1 === preg_match( '/bot|crawl|spider|slurp|curl|wget|headless|monitor|preview|fetch/i', $agent );
	}
}
