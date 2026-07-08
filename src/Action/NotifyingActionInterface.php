<?php

namespace GFExcel\Action;

/**
 * An action that reports a typed notice describing its outcome.
 *
 * @since TBD
 */
interface NotifyingActionInterface extends ActionInterface {
	/**
	 * The static success notice, resolved without firing the action.
	 *
	 * Used to re-surface the confirmation after a post-redirect GET, where the action itself
	 * must not run again.
	 *
	 * @since TBD
	 *
	 * @return ActionNotice
	 */
	public function get_success_notice(): ActionNotice;

	/**
	 * Performs the action and returns the notice describing what happened.
	 *
	 * @since TBD
	 *
	 * @param \GFAddOn $addon The add-on instance.
	 * @param array    $form  The [feed_id, form_id, settings] tuple.
	 *
	 * @return ActionNotice|null The outcome notice, or null when nothing happened.
	 */
	public function fire_with_notice( \GFAddOn $addon, array $form ): ?ActionNotice;
}
