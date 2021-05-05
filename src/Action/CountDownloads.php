<?php

namespace GFExcel\Action;

use GFExcel\GFExcelConfigConstants;
use GFFormsModel;

/**
 * @since 1.6.1
 */
class CountDownloads
{
    /**
     * Key used to store the download count in.
     * @var string
     */
    const KEY_COUNT = 'gfexcel_download_count';

    /** @var string */
    const ACTION_RESET = 'reset_count';

    /**
     * Micro cache form object.
     * @var mixed[]|null
     */
    private $form;

    /**
     * Register action to event.
     */
    public function __construct()
    {
        add_action(GFExcelConfigConstants::GFEXCEL_EVENT_DOWNLOAD, [$this, 'incrementCounter']);
        add_action('gfexcel_action_' . self::ACTION_RESET, [$this, 'resetCounter']);
    }

    /**
     * Updates the download counter for a form.
     * @since 1.6.1
     * @param int $form_id The form id.
     */
    public function incrementCounter($form_id)
    {
        // Get the form data.
        $form_meta = $this->getForm($form_id);
        $count = (int) rgar( $form_meta, static::KEY_COUNT, 0 );

        $this->setCounter($form_id, ++$count);
    }

    /**
     * Resets the download counter for a form.
     * @since 1.6.1
     * @param int $form_id The id of form to reset.
     */
    public function resetCounter($form_id)
    {
        $this->setCounter($form_id, 0);
    }

    /**
     * Prevent multiple calls to get the same data.
     * @since 1.6.1
     * @param int $form_id The form id.
     * @return mixed[]|null The form object.
     */
    private function getForm($form_id)
    {
        if (!$this->form) {
            $this->form = GFFormsModel::get_form_meta($form_id);
        }

        return $this->form;
    }

    /**
     * Helper function to actually set the value.
     * @since 1.6.1
     * @param int $form_id The form id.
     * @param int $count The value to set the counter to.
     */
    private function setCounter($form_id, $count = 0)
    {
        $form_meta = $this->getForm($form_id);
        $form_meta[self::KEY_COUNT] = (int) $count;
        // store new data.
        GFFormsModel::update_form_meta($form_id, $form_meta);
    }
}
