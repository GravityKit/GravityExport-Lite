<?php

namespace GFExcel\Action;

use GFExcel\Addon\GravityExportAddon;
use GFExcel\GFExcel;
use GFExcel\GFExcelOutput;
use GFExcel\Repository\FormsRepository;

/**
 * Handles the attachment for a notification.
 * @since 2.0.0
 */
final class NotificationAttachmentAction {
	/**
	 * Holds the file name.
	 * @since 2.0.0
	 * @var string
	 */
	private $file;

	/**
	 * Registers the event hooks.
	 * @since 2.0.0
	 */
	public function __construct() {
		add_filter( 'gform_notification', \Closure::fromCallable( [ $this, 'handle_notification' ] ), 10, 3 );
		add_action( 'gform_after_email', \Closure::fromCallable( [ $this, 'remove_temporary_file' ] ), 10, 13 );
	}

	/**
	 * Adds the attachment to the notification.
	 *
	 * @since 2.0.0
	 *
	 * @param array $form The form object.
	 * @param array $entry The entry object.
	 * @param mixed $notification The notification object.
	 *
	 * @return mixed The notification with attachment.
	 */
	private function handle_notification( $notification, array $form, array $entry ) {
		// in some cases an notification can be something else than an array.
		if ( ! is_array( $notification ) ) {
			return $notification;
		}

		// get notification to add to by form setting
		$repository = new FormsRepository( $form['id'] );
		if ( $repository->getSelectedNotification() !== \rgar( $notification, 'id' ) ) {
			// Not the right notification
			return $notification;
		}

		$feed    = GravityExportAddon::get_instance()->get_feed_by_form_id( $form['id'] );
		$feed_id = $feed['id'] ?? null;

		// create a file based on the settings in the form, with only this entry.
		$output = new GFExcelOutput( $form['id'], GFExcel::getRenderer( $form['id'] ), null, $feed_id );
		$output->setEntries( [ $entry ] );

		// save the file to a temporary file
		$this->file = $output->render( $save = true );
		if ( ! file_exists( $this->file ) ) {
			return $notification;
		}

		// attach file to $notification['attachments'][]
		$notification['attachments'][] = $this->file;

		return $notification;
	}

	/**
	 * Removes the attachment after the notification was sent.
	 * @since 2.0.0
	 */
	private function remove_temporary_file(): void {
		$attachments = func_get_arg( 5 );

		if ( ! is_array( $attachments ) || count( $attachments ) < 1 ) {
			return;
		}

		if ( in_array( $this->file, $attachments ) && file_exists( $this->file ) ) {
			@unlink( $this->file );
		}
	}
}
