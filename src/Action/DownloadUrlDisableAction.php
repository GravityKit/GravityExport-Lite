<?php

namespace GFExcel\Action;

use GFExcel\Addon\GFExcelAddon;

/**
 * Action to enable / disable the download url for a form.
 * @since $ver$
 */
class DownloadUrlDisableAction extends AbstractAction
{
    /**
     * @inheritdoc
     * @since $ver$
     */
    public static $name = 'download_url_disable';

    /**
     * @inheritdoc
     * @since $ver$
     */
    public function fire(\GFAddOn $addon, array $form): void
    {
        if (!$addon instanceof GFExcelAddon) {
            return;
        }

        [$feed_id, $form_id, $settings] = $form;
        $settings['hash'] = null;

        $addon->save_feed_settings($feed_id, $form_id, $settings);
        // Update the current and previous settings.
        $addon->set_settings($settings);
        $addon->set_previous_settings($settings);

        // Set notification of success.
        $addon->add_message(esc_html__('The download URL has been disabled.', 'gk-gravityexport'));
    }
}
