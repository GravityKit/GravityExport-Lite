<?php

namespace GFExcel\Action;

use GFExcel\Addon\GravityExportAddon;
use GFExcel\Generator\HashGeneratorInterface;

/**
 * Action to reset the download URL for a form.
 * @since 2.0.0
 */
class DownloadUrlResetAction extends AbstractAction {
	/**
	 * @inheritdoc
	 * @since 2.0.0
	 */
	public static $name = 'download_url_reset';

	/**
	 * The hash generator.
	 * @since 2.0.0
	 * @var HashGeneratorInterface
	 */
	private $generator;

	/**
	 * Creates the action.
	 *
	 * @param HashGeneratorInterface $generator The hash generator.
	 */
	public function __construct( HashGeneratorInterface $generator ) {
		$this->generator = $generator;
	}

	/**
	 * The message to show when the action was successful.
	 *
	 * Translated lazily, not in the constructor: the action is resolved from the
	 * service container during load (before after_setup_theme), and translating
	 * there trips WordPress 6.7's just-in-time translation notice.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	protected function get_success_message(): string {
		return esc_html__( 'The download URL has been reset.', 'gk-gravityexport-lite' );
	}

	/**
	 * @inheritdoc
	 * @since 2.0.0
	 */
	public function fire( \GFAddOn $addon, array $form ): void {
		if ( ! $addon instanceof GravityExportAddon ) {
			return;
		}

		try {
			$hash = $this->generator->generate();
		} catch ( \Exception $exception ) {
			$addon->add_error_message(
				sprintf(
					esc_html__( 'There was an error generating the URL: %s', 'gk-gravityexport-lite' ),
					$exception->getMessage()
				)
			);

			return;
		}

		[ $feed_id, $form_id, $settings ] = $form;
		$settings['hash'] = $hash;

		$addon->save_feed_settings( $feed_id, $form_id, $settings );

		// Update the current and previous settings.
		$addon->set_settings( $settings );
		$addon->set_previous_settings( $settings );

		// Set notification of success.
		$addon->add_message( $this->get_success_message() );
	}
}
