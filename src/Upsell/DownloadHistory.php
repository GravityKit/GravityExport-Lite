<?php

namespace GFExcel\Upsell;

use GFExcel\GFExcelConfigConstants;

/**
 * Records how many exports each form produced, per day.
 *
 * This exists so the number can be shown to the person it belongs to, on a
 * screen they chose to open, rather than only appearing at the moment we want
 * something from them. A count that only ever surfaces alongside an offer reads
 * as having been kept for that purpose; the same count on a chart they can look
 * at whenever they like is a feature.
 *
 * Kept separate from the lifetime total in {@see DownloadMilestone}: this window
 * is pruned, that one is not, and they answer different questions.
 *
 * @since $ver$
 */
class DownloadHistory {
	/**
	 * Option prefix. One row per form, created only once a form is exported.
	 *
	 * @since $ver$
	 */
	public const OPTION_PREFIX = 'gfexcel_download_history_';

	/**
	 * How many days of history to keep.
	 *
	 * Bounds the option at roughly ninety small integers. Long enough to show a
	 * trend and a seasonal dip, short enough that nobody has to think about the
	 * storage.
	 *
	 * @since $ver$
	 */
	public const RETENTION_DAYS = 90;

	/**
	 * Registers the hook.
	 *
	 * @since $ver$
	 */
	public function __construct() {
		add_action( GFExcelConfigConstants::GFEXCEL_EVENT_DOWNLOAD, [ $this, 'record' ] );
	}

	/**
	 * Records one export against today for the given form.
	 *
	 * @since $ver$
	 *
	 * @param int|string $form_id The form id.
	 *
	 * @return void
	 */
	public function record( $form_id ): void {
		$form_id = (int) $form_id;

		if ( $form_id < 1 ) {
			return;
		}

		$history  = $this->raw( $form_id );
		$today    = gmdate( 'Ymd' );
		$history[ $today ] = ( $history[ $today ] ?? 0 ) + 1;

		update_option( self::OPTION_PREFIX . $form_id, $this->prune( $history ), false );
	}

	/**
	 * Returns a zero-filled daily series, oldest first.
	 *
	 * Gaps are filled rather than omitted: a chart drawn from stored days alone
	 * would silently compress quiet periods and make usage look steadier than it
	 * was, which is the kind of flattering error that costs trust when someone
	 * notices.
	 *
	 * @since $ver$
	 *
	 * @param int $form_id The form id.
	 * @param int $days    How many days to return.
	 *
	 * @return array<string,int> Date (Y-m-d) to count.
	 */
	public function series( int $form_id, int $days = 30 ): array {
		$history = $this->raw( $form_id );
		$series  = [];

		for ( $i = $days - 1; $i >= 0; $i-- ) {
			$stamp             = strtotime( "-{$i} days" );
			$series[ gmdate( 'Y-m-d', $stamp ) ] = (int) ( $history[ gmdate( 'Ymd', $stamp ) ] ?? 0 );
		}

		return $series;
	}

	/**
	 * Returns the total across the retained window.
	 *
	 * @since $ver$
	 *
	 * @param int $form_id The form id.
	 *
	 * @return int The total.
	 */
	public function total( int $form_id ): int {
		return (int) array_sum( $this->raw( $form_id ) );
	}

	/**
	 * Returns true when there is nothing worth charting yet.
	 *
	 * @since $ver$
	 *
	 * @param int $form_id The form id.
	 *
	 * @return bool Whether the history is empty.
	 */
	public function isEmpty( int $form_id ): bool {
		return [] === $this->raw( $form_id );
	}

	/**
	 * Removes the history for a form. Called when its feed is deleted.
	 *
	 * @since $ver$
	 *
	 * @param int $form_id The form id.
	 *
	 * @return void
	 */
	public function forget( int $form_id ): void {
		delete_option( self::OPTION_PREFIX . $form_id );
	}

	/**
	 * Returns the stored history, keyed Ymd.
	 *
	 * @since $ver$
	 *
	 * @param int $form_id The form id.
	 *
	 * @return array<string,int> The history.
	 */
	private function raw( int $form_id ): array {
		$history = get_option( self::OPTION_PREFIX . $form_id );

		return is_array( $history ) ? array_map( 'intval', $history ) : [];
	}

	/**
	 * Drops days outside the retention window.
	 *
	 * @since $ver$
	 *
	 * @param array<string,int> $history The history.
	 *
	 * @return array<string,int> The pruned history.
	 */
	private function prune( array $history ): array {
		$cutoff = gmdate( 'Ymd', strtotime( '-' . self::RETENTION_DAYS . ' days' ) );

		foreach ( array_keys( $history ) as $day ) {
			if ( (string) $day < $cutoff ) {
				unset( $history[ $day ] );
			}
		}

		ksort( $history );

		return $history;
	}
}
