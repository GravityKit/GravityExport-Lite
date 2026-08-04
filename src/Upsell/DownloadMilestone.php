<?php

namespace GFExcel\Upsell;

use GFExcel\GFExcelConfigConstants;
use GFExcel\Links\Links;

/**
 * Celebrates download milestones, and asks for the upgrade while it does.
 *
 * Modelled on Popup Maker's review module, which earns its asks by leading with
 * the user's own number rather than with the pitch. Three properties make that
 * work and are reproduced here: a log-scale ladder so the prompt stays relevant
 * whether a site has exported ten times or a million; a hard stop once the user
 * has acted; and a cooling-off period so a dismissal is respected rather than
 * merely deferred.
 *
 * Reads a local counter and sends nothing. This is deliberately independent of
 * analytics: it works for the ~85% of installs that decline telemetry, it works
 * offline, and it needs no consent. Deriving upsell targeting from opt-in data
 * would reach fewer people and cost a privacy argument to do it.
 *
 * That independence is invisible to the person reading the notice, which is a
 * problem in itself. Someone who declined the usage-data prompt and is then
 * shown a precise count has every reason to conclude they were counted anyway
 * and told otherwise. The notice therefore says where the number lives. The
 * sentence is deliberately true in both consent states and needs no runtime
 * check, so it carries no dependency on the analytics classes that get deleted
 * at handover.
 *
 * @since $ver$
 */
class DownloadMilestone {
	/**
	 * Running total of downloads across the site.
	 *
	 * @since $ver$
	 */
	public const OPTION_TOTAL = 'gfexcel_download_total';

	/**
	 * The highest milestone already celebrated.
	 *
	 * @since $ver$
	 */
	public const OPTION_SHOWN = 'gfexcel_milestone_shown';

	/**
	 * When the prompt was last dismissed, per user.
	 *
	 * @since $ver$
	 */
	public const META_DISMISSED = 'gfexcel_milestone_dismissed';

	/**
	 * Set once the user acts, after which the prompt never returns.
	 *
	 * @since $ver$
	 */
	public const OPTION_DONE = 'gfexcel_milestone_done';

	/**
	 * How long a dismissal is respected.
	 *
	 * Longer than Popup Maker's two weeks because this asks for money rather
	 * than a review, and a pushier ask has to be correspondingly more patient.
	 *
	 * @since $ver$
	 */
	public const COOLDOWN = 30 * DAY_IN_SECONDS;

	/**
	 * The action name used for both the nonce and the dismissal handler.
	 *
	 * @since $ver$
	 */
	public const ACTION = 'gfexcel_milestone';

	/**
	 * Registers the hooks.
	 *
	 * @since $ver$
	 */
	public function __construct() {
		add_action( GFExcelConfigConstants::GFEXCEL_EVENT_DOWNLOAD, [ $this, 'count' ] );
		add_action( 'admin_notices', [ $this, 'render' ] );
		add_action( 'admin_post_' . self::ACTION, [ $this, 'handle' ] );
	}

	/**
	 * Increments the site-wide download total.
	 *
	 * @since $ver$
	 *
	 * @return void
	 */
	public function count(): void {
		update_option( self::OPTION_TOTAL, $this->total() + 1, false );
	}

	/**
	 * Returns the milestone ladder.
	 *
	 * Log-scaled on purpose. A fixed threshold is either met immediately and
	 * then never again, or never met at all; a ladder keeps pace with however
	 * much a site actually exports.
	 *
	 * @since $ver$
	 *
	 * @return int[] The milestones, ascending.
	 */
	public function milestones(): array {
		/**
		 * Filters the download milestones that trigger the upgrade prompt.
		 *
		 * @since $ver$
		 *
		 * @param int[] $milestones The milestones, ascending.
		 */
		$milestones = (array) apply_filters(
			'gfexcel_download_milestones',
			[ 25, 100, 500, 1000, 5000, 10000, 50000, 100000 ]
		);

		sort( $milestones );

		return array_map( 'intval', $milestones );
	}

	/**
	 * Returns the milestone to celebrate now, or null when there is none.
	 *
	 * @since $ver$
	 *
	 * @return int|null The milestone.
	 */
	public function due(): ?int {
		$total = $this->total();
		$shown = (int) get_option( self::OPTION_SHOWN, 0 );
		$due   = null;

		foreach ( $this->milestones() as $milestone ) {
			if ( $total >= $milestone && $milestone > $shown ) {
				$due = $milestone;
			}
		}

		// The highest unshown milestone wins, so a site that arrives already
		// past several is congratulated once on the biggest rather than
		// marched through every rung it skipped.
		return $due;
	}

	/**
	 * Prints the prompt when one is due.
	 *
	 * @since $ver$
	 *
	 * @return void
	 */
	public function render(): void {
		$milestone = $this->due();

		if ( null === $milestone || $this->silenced() ) {
			return;
		}

		$url = admin_url( 'admin-post.php' );
		?>
		<div class="notice notice-success">
			<h2 class="notice-title" style="margin:.5em 0;font-size:1em"><?php
				printf(
					/* translators: %s: the number of exports downloaded, already formatted. */
					esc_html__( 'Your forms have been exported %s times.', 'gk-gravityexport-lite' ),
					esc_html( number_format_i18n( $milestone ) )
				);
			?></h2>
			<p><?php esc_html_e(
				'GravityExport adds scheduled exports that send themselves, delivery straight to Dropbox, Google Drive or FTP, and PDF output.',
				'gk-gravityexport-lite'
			); ?></p>
			<p class="description"><?php esc_html_e(
				'This count is kept on your own site. It is only shared with us if you turned on usage data.',
				'gk-gravityexport-lite'
			); ?></p>
			<form method="post" action="<?php echo esc_url( $url ); ?>">
					<?php wp_nonce_field( self::ACTION ); ?>
					<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>">
					<input type="hidden" name="milestone" value="<?php echo esc_attr( (string) $milestone ); ?>">
					<button type="submit" name="choice" value="view" class="button button-primary">
						<?php esc_html_e( 'See what GravityExport adds', 'gk-gravityexport-lite' ); ?>
					</button>
					<button type="submit" name="choice" value="later" class="button">
						<?php esc_html_e( 'Not now', 'gk-gravityexport-lite' ); ?>
					</button>
					<button type="submit" name="choice" value="never" class="button-link">
						<?php esc_html_e( 'Don\'t show this again', 'gk-gravityexport-lite' ); ?>
					</button>
			</form>
		</div>
		<?php
	}

	/**
	 * Records the answer.
	 *
	 * @since $ver$
	 *
	 * @return void
	 */
	public function handle(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'gk-gravityexport-lite' ), '', [ 'response' => 403 ] );
		}

		check_admin_referer( self::ACTION );

		$choice    = isset( $_POST['choice'] ) ? sanitize_key( wp_unslash( $_POST['choice'] ) ) : '';
		$milestone = isset( $_POST['milestone'] ) ? (int) $_POST['milestone'] : 0;

		if ( $milestone > 0 ) {
			update_option( self::OPTION_SHOWN, $milestone, false );
		}

		if ( 'never' === $choice ) {
			update_option( self::OPTION_DONE, true, false );
		} else {
			update_user_meta( get_current_user_id(), self::META_DISMISSED, time() );
		}

		if ( 'view' === $choice ) {
			wp_redirect( Links::upgrade( 'download_milestone' ) );
			exit;
		}

		wp_safe_redirect( wp_get_referer() ?: admin_url() );
		exit;
	}

	/**
	 * Returns true when the prompt must not be shown.
	 *
	 * @since $ver$
	 *
	 * @return bool Whether to stay quiet.
	 */
	private function silenced(): bool {
		// Already a customer: never pitch the thing they bought.
		if ( defined( 'GK_GRAVITYEXPORT_PLUGIN_VERSION' ) ) {
			return true;
		}

		if ( get_option( self::OPTION_DONE ) ) {
			return true;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return true;
		}

		$dismissed = (int) get_user_meta( get_current_user_id(), self::META_DISMISSED, true );

		return $dismissed > 0 && ( time() - $dismissed ) < self::COOLDOWN;
	}

	/**
	 * Returns the site-wide download total.
	 *
	 * @since $ver$
	 *
	 * @return int The total.
	 */
	public function total(): int {
		return (int) get_option( self::OPTION_TOTAL, 0 );
	}
}
