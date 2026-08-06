<?php

namespace GFExcel\Action;

use GFExcel\Addon\GravityExportAddon;
use GFExcel\Generator\HashGeneratorInterface;

/**
 * Action to reset the download URL for a form.
 * @since 2.0.0
 */
class DownloadUrlResetAction extends AbstractAction implements NotifyingActionInterface {
	use FiresWithNotice;

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
	 * The success notice for this action.
	 *
	 * Translated lazily, not in the constructor: the action is resolved from the
	 * service container during load (before after_setup_theme), and translating
	 * there trips WordPress 6.7's just-in-time translation notice.
	 *
	 * @since 2.7.0
	 *
	 * @return ActionNotice
	 */
	public function get_success_notice(): ActionNotice {
		return ActionNotice::success( esc_html__( 'The download URL has been reset.', 'gk-gravityexport-lite' ) );
	}

	/**
	 * @inheritDoc
	 * @since 2.7.0
	 */
	public function fire_with_notice( \GFAddOn $addon, array $form ): ?ActionNotice {
		if ( ! $addon instanceof GravityExportAddon ) {
			return null;
		}

		try {
			$hash = $this->generator->generate();
		} catch ( \Exception $exception ) {
			return ActionNotice::error(
				sprintf(
					esc_html__( 'There was an error generating the URL: %s', 'gk-gravityexport-lite' ),
					$exception->getMessage()
				)
			);
		}

		[ $feed_id, $form_id, $settings ] = $form;
		$settings['hash'] = $hash;

		$addon->save_feed_settings( $feed_id, $form_id, $settings );

		// Update the current and previous settings.
		$addon->set_settings( $settings );
		$addon->set_previous_settings( $settings );

		return $this->get_success_notice();
	}
}
