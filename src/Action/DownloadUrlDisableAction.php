<?php

namespace GFExcel\Action;

use GFExcel\Addon\GravityExportAddon;

/**
 * Action to enable / disable the download url for a form.
 * @since 2.0.0
 */
class DownloadUrlDisableAction extends AbstractAction implements NotifyingActionInterface
{
    use FiresWithNotice;

    /**
     * @inheritdoc
     * @since 2.0.0
     */
    public static $name = 'download_url_disable';

    /**
     * @inheritDoc
     * @since 2.7.0
     */
    public function fire_with_notice(\GFAddOn $addon, array $form): ?ActionNotice
    {
        if (! $addon instanceof GravityExportAddon) {
            return null;
        }

        [$feed_id, $form_id, $settings] = $form;
        $settings['hash'] = '';

        $addon->save_feed_settings($feed_id, $form_id, $settings);
        // Update the current and previous settings.
        $addon->set_settings($settings);
        $addon->set_previous_settings($settings);

        return $this->get_success_notice();
    }

    /**
     * @inheritDoc
     * @since 2.7.0
     */
    public function get_success_notice(): ActionNotice
    {
        return ActionNotice::success(esc_html__('The download URL has been disabled.', 'gk-gravityexport-lite'));
    }
}
