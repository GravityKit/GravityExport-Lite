<?php

namespace GFExcel\Action;

use GFExcel\GFExcel;
use GFExcel\Notification\Exception\NotificationManagerException;
use GFExcel\Notification\Manager\NotificationManager;
use GFExcel\Notification\Notification;

/**
 * Action that handles the notifications.
 * @since $ver$
 */
class NotificationsAction
{
    /**
     * The action that triggers a dismissal.
     * @since $ver$
     * @var string
     */
    public const ACTION_DISMISS = 'gfexcel_dismiss_notification';

    /**
     * The name of the nonce action.
     * @since $ver$
     * @var string
     */
    public const KEY_NONCE = 'gfexcel_notification_nonce';

    /**
     * The notification manager.
     * @since $ver$
     * @var NotificationManager
     */
    private $manager;

    /**
     * Notifications constructor.
     * @since $ver$
     * @param NotificationManager $manager The notification manager.
     */
    public function __construct(NotificationManager $manager)
    {
        $this->manager = $manager;

        add_action('admin_enqueue_scripts', [$this, 'registerScripts']);
        add_action('all_admin_notices', [$this, 'showNotices']);
        add_action('wp_ajax_' . self::ACTION_DISMISS, [$this, 'dismissNotification']);
    }

    /**
     * Returns the available notifications.
     * @since $ver$
     * @return Notification[] The notifications.
     * @throws NotificationManagerException When the notification is of a wrong type.
     */
    public function getNotifications(): array
    {
        try {
            return $this->manager->getNotifications();
        } catch (NotificationManagerException $e) {
            return [
                new Notification(
                    'manager-error',
                    __('The notifications could not be retrieved.', GFExcel::$slug),
                    Notification::TYPE_ERROR,
                    false
                )
            ];
        }
    }

    /**
     * Dismisses a notification by the id.
     * @since $ver$
     */
    public function dismissNotification(): void
    {
        $notification_id = $_POST['notification_key'] ?? null;
        $nonce = $_POST['nonce'] ?? null;

        if (!$notification_id || !$nonce || !wp_verify_nonce($nonce, self::KEY_NONCE)) {
            wp_die('No key or (valid) nonce provided.', 'Something went wrong.', [
                'response' => 400,
            ]);
        } else {
            try {
                $this->manager->dismiss($notification_id);
                wp_die();
            } catch (NotificationManagerException $e) {
                wp_die($e->getMessage(), 'Something went wrong.', [
                    'response' => 400,
                ]);
            }
        }
    }

    /**
     * Shows the available notifications.
     * @since $ver$
     * @codeCoverageIgnore
     */
    public function showNotices(): void
    {
        $template = dirname(GFEXCEL_PLUGIN_FILE) . '/views/notifications.php';
        if (file_exists($template)) {
            require_once $template;
        }
    }

    /**
     * Registers the notification scripts.
     * @since $ver$
     */
    public function registerScripts(): void
    {
        wp_enqueue_script(
            'gfexcel-notifications',
            plugin_dir_url(GFEXCEL_PLUGIN_FILE) . 'public/js/notifications.js',
            ['jquery']
        );
    }
}
