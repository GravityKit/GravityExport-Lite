<?php

namespace GFExcel\Analytics;

/**
 * The opt-in prompt.
 *
 * Nothing is sent before this is answered. The plugin ships with analytics off,
 * the prompt asks once, and declining is remembered so it never asks again.
 *
 * The exact wording below is hashed into the consent record. If the wording
 * changes, the hash changes, and a later reader can tell that a stored grant
 * was given against a different promise than the one now being made.
 *
 * @since $ver$
 */
class ConsentCard {
	/**
	 * The action name used for both the nonce and the form submission.
	 *
	 * @since $ver$
	 */
	public const ACTION = 'gk_analytics_consent';

	/**
	 * Where this consent was collected; a closed enum value in the schema.
	 *
	 * @since $ver$
	 */
	public const SOURCE = 'lite_settings_card';

	/**
	 * The consent store.
	 *
	 * @since $ver$
	 * @var Consent
	 */
	private $consent;

	/**
	 * @since $ver$
	 *
	 * @param Consent $consent The consent store.
	 */
	public function __construct( Consent $consent ) {
		$this->consent = $consent;

		add_action( 'admin_notices', [ $this, 'render' ] );
		add_action( 'admin_post_' . self::ACTION, [ $this, 'handle' ] );
	}

	/**
	 * Returns the disclosure shown to the user, which is what gets hashed.
	 *
	 * @since $ver$
	 *
	 * @return string The disclosure text.
	 */
	public static function disclosure(): string {
		return implode(
			' ',
			[
				__( 'Share anonymous usage data so we know which features matter.', 'gk-gravityexport-lite' ),
				__( 'We collect your WordPress and PHP versions, which export settings you use, and how often exports run.', 'gk-gravityexport-lite' ),
				__( 'We never collect form data, entry data, email addresses, or your site address.', 'gk-gravityexport-lite' ),
				__( 'Your site is identified only by a one-way hash that cannot be turned back into your address.', 'gk-gravityexport-lite' ),
				__( 'You can turn this off at any time.', 'gk-gravityexport-lite' ),
			]
		);
	}

	/**
	 * Prints the prompt, once, to users who could act on it.
	 *
	 * @since $ver$
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! $this->shouldPrompt() ) {
			return;
		}

		$url = admin_url( 'admin-post.php' );
		?>
		<div class="notice notice-info">
			<p>
				<strong><?php esc_html_e( 'Help improve GravityExport', 'gk-gravityexport-lite' ); ?></strong><br>
				<?php echo esc_html( self::disclosure() ); ?>
			</p>
			<p>
				<form method="post" action="<?php echo esc_url( $url ); ?>" style="display:inline">
					<?php wp_nonce_field( self::ACTION ); ?>
					<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>">
					<button type="submit" name="choice" value="grant" class="button button-primary">
						<?php esc_html_e( 'Share usage data', 'gk-gravityexport-lite' ); ?>
					</button>
					<button type="submit" name="choice" value="decline" class="button">
						<?php esc_html_e( 'No thanks', 'gk-gravityexport-lite' ); ?>
					</button>
				</form>
			</p>
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
			wp_die( esc_html__( 'You do not have permission to change this setting.', 'gk-gravityexport-lite' ), '', [ 'response' => 403 ] );
		}

		check_admin_referer( self::ACTION );

		$choice = isset( $_POST['choice'] ) ? sanitize_key( wp_unslash( $_POST['choice'] ) ) : '';

		if ( 'grant' === $choice ) {
			Analytics::optIn( self::SOURCE, self::disclosure() );
		} else {
			$this->consent->revoke( self::SOURCE );
		}

		wp_safe_redirect( wp_get_referer() ?: admin_url() );
		exit;
	}

	/**
	 * Returns true when the prompt should be shown.
	 *
	 * @since $ver$
	 *
	 * @return bool Whether to prompt.
	 */
	private function shouldPrompt(): bool {
		if ( Analytics::isDelegating() ) {
			return false; // Foundation owns the consent surface when it is present.
		}

		return $this->consent->undecided() && current_user_can( 'manage_options' );
	}
}
