<?php

namespace GFExcel\Action;

use GFExcel\Addon\GravityExportAddon;

/**
 * Bridges a NotifyingActionInterface to the legacy void fire() contract by pushing the returned
 * notice into the add-on's message queue. Lets callers that predate the notice return value (e.g.
 * feed duplication) keep working unchanged.
 *
 * @since 2.7.0
 */
trait FiresWithNotice {
	/**
	 * @inheritDoc
	 * @since 2.4.0
	 */
	public function fire( \GFAddOn $addon, array $form ): void {
		if ( ! $addon instanceof GravityExportAddon ) {
			return;
		}

		$notice = $this->fire_with_notice( $addon, $form );
		if ( ! $notice instanceof ActionNotice ) {
			return;
		}

		if ( ActionNotice::ERROR === $notice->type() ) {
			$addon->add_error_message( $notice->message() );

			return;
		}

		$addon->add_message( $notice->message() );
	}
}
