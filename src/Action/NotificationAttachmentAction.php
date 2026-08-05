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
	 * The generated temporary files, as file path => private directory.
	 * @since TBD
	 * @var array<string, string>
	 */
	private $files = [];

	/**
	 * Every private directory created this request, pending removal.
	 *
	 * Registered before rendering, because the renderer exits the request on
	 * failure, which skips `finally` blocks but not the shutdown handler.
	 *
	 * @since TBD
	 * @var array<string, string>
	 */
	private $directories = [];

	/**
	 * Registers the event hooks.
	 * @since 2.0.0
	 */
	public function __construct() {
		add_filter( 'gform_notification', \Closure::fromCallable( [ $this, 'handle_notification' ] ), 10, 3 );

		// Clean up late, so other `gform_after_email` callbacks still have access to the file.
		add_action( 'gform_after_email', \Closure::fromCallable( [ $this, 'remove_temporary_files' ] ), PHP_INT_MAX, 13 );
		register_shutdown_function( \Closure::fromCallable( [ $this, 'remove_leftover_files' ] ) );
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

		// get notifications to add to by form setting
		$repository = new FormsRepository( $form['id'] );

		if ( ! in_array( (string) \rgar( $notification, 'id', '' ), $repository->getSelectedNotifications(), true ) ) {
			// Not one of the selected notifications
			return $notification;
		}

		$feed    = GravityExportAddon::get_instance()->get_feed_by_form_id( $form['id'] );
		$feed_id = $feed['id'] ?? null;

		// create a file based on the settings in the form, with only this entry.
		$output = new GFExcelOutput( $form['id'], GFExcel::getRenderer( $form['id'] ), null, $feed_id );
		$output->setEntries( [ $entry ] );

		// Save every render to its own directory, so parallel renders cannot overwrite or delete each other's file.
		$directory = $this->create_private_directory();

		if ( $directory === null ) {
			return $notification;
		}

		$save_path = static function ( $file ) use ( $directory ): string {
			return $directory . basename( (string) $file );
		};

		add_filter( 'gk/gravityexport/renderer/save-path', $save_path );

		try {
			// save the file to a temporary file
			$file = $output->render( $save = true );
		} finally {
			remove_filter( 'gk/gravityexport/renderer/save-path', $save_path );
		}

		if ( ! is_string( $file ) || ! file_exists( $file ) ) {
			$this->remove_directory( $directory );

			return $notification;
		}

		$this->files[ $file ] = $directory;

		$attachments = \rgar( $notification, 'attachments', [] );

		if ( ! is_array( $attachments ) ) {
			$attachments = array_filter( [ $attachments ] );
		}

		// Append only; other callbacks may use string keys as attachment filenames for `wp_mail()`.
		if ( ! in_array( $file, $attachments, true ) ) {
			$attachments[] = $file;
		}

		$notification['attachments'] = $attachments;

		return $notification;
	}

	/**
	 * Creates a hardened private directory for a single render.
	 *
	 * The directory is registered for shutdown cleanup up front, because a failed
	 * render exits the request before the file can be registered.
	 *
	 * @since TBD
	 * @return string|null The directory path, or null when it could not be created.
	 */
	private function create_private_directory(): ?string {
		$directory = get_temp_dir() . 'gravityexport-attachment-' . wp_generate_password( 12, false ) . '/';

		// Created 0700 in one step: the temp directory is world-writable on shared hosts,
		// so the directory must never exist with wider permissions, not even briefly.
		if ( ! @mkdir( $directory, 0700 ) || ! is_dir( $directory ) ) {
			GravityExportAddon::get_instance()->log_error( __METHOD__ . sprintf( '(): Could not create "%s"; the notification is sent without the export attachment.', $directory ) );

			return null;
		}

		$this->directories[ $directory ] = $directory;

		// `get_temp_dir()` can fall back to a web-reachable folder (wp-content); the random
		// directory name is the primary protection, the rest is best-effort per server type.
		@file_put_contents( $directory . 'index.html', '' );
		@file_put_contents(
			$directory . '.htaccess',
			"<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n"
		);

		return $directory;
	}

	/**
	 * Removes the generated attachments after their notification email was sent.
	 * @since 2.0.0
	 */
	private function remove_temporary_files(): void {
		$attachments = func_get_arg( 5 );

		if ( ! is_array( $attachments ) || count( $attachments ) < 1 ) {
			return;
		}

		foreach ( $attachments as $attachment ) {
			if ( ! is_string( $attachment ) || ! isset( $this->files[ $attachment ] ) ) {
				continue;
			}

			$directory = $this->files[ $attachment ];
			unset( $this->files[ $attachment ] );

			if ( file_exists( $attachment ) ) {
				@unlink( $attachment );
			}

			$this->remove_directory( $directory );
		}
	}

	/**
	 * Removes any generated files and directories that were not cleaned up after their email.
	 * @since TBD
	 */
	private function remove_leftover_files(): void {
		foreach ( $this->files as $file => $directory ) {
			if ( file_exists( $file ) ) {
				@unlink( $file );
			}
		}

		$this->files = [];

		foreach ( $this->directories as $directory ) {
			$this->remove_directory( $directory );
		}
	}

	/**
	 * Removes a private directory created by this request and everything in it.
	 *
	 * Only directories this request made are touched. The temp directory can be
	 * world-writable (`/tmp` on shared hosts), so anything else there is left alone:
	 * validating a path someone else controls cannot be done without a race.
	 *
	 * @since TBD
	 * @param string $directory The directory path.
	 */
	private function remove_directory( string $directory ): void {
		unset( $this->directories[ $directory ] );

		if ( $directory === '' || is_link( untrailingslashit( $directory ) ) || ! is_dir( $directory ) ) {
			return;
		}

		foreach ( array_merge( glob( $directory . '*' ) ?: [], glob( $directory . '.[!.]*' ) ?: [] ) as $file ) {
			// A symlink is removed, never followed.
			if ( is_link( $file ) || is_file( $file ) ) {
				@unlink( $file );
			}
		}

		@rmdir( $directory );
	}
}
