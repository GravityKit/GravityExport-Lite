<?php

namespace GFExcel\Action;

use GFExcel\GFExcel;
use GFExcel\GFExcelOutput;
use GFExcel\Repository\FormsRepository;

/**
 * Handles the attachment for a notification.
 * @since $ver$
 */
final class NotificationAttachmentAction {
	/**
	 * Holds the file name.
	 * @since $ver$
	 * @var string
	 */
	private $file;

	/**
	 * Registers the event hooks.
	 * @since $ver$
	 */
	public function __construct() {
		add_action( 'gform_notification', \Closure::fromCallable( [ $this, 'handle_notification' ] ), 10, 3 );
		add_action( 'gform_after_email', \Closure::fromCallable( [ $this, 'remove_temporary_file' ] ), 10, 13 );
	}

	/**
	 * Adds the attachment to the notification.
	 *
	 * @since $ver$
	 *
	 * @param array $form The form object.
	 * @param array $entry The entry object.
	 * @param array $notification The notification object.
	 *
	 * @return array The notification with attachment.
	 */
	private function handle_notification( array $notification, array $form, array $entry ): array {
		// get notification to add to by form setting
		$repository = new FormsRepository( $form['id'] );
		if ( $repository->getSelectedNotification() !== \rgar( $notification, 'id' ) ) {
			// Not the right notification
			return $notification;
		}

		// create a file based on the settings in the form, with only this entry.
		$output = new GFExcelOutput( $form['id'], GFExcel::getRenderer( $form['id'] ) );
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
	 * @since $ver$
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
