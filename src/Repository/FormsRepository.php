<?php

namespace GFExcel\Repository;

use GFAPI;
use GFExcel\GFExcel;
use GFExcel\GFExcelAdmin;

class FormsRepository
{
    /** @var array|false */
    private $form;
    /**
     * @var GFExcelAdmin
     */
    private $admin;

    public function __construct($form_id)
    {
        $this->form = $form_id ? GFAPI::get_form($form_id) : [];
        $this->admin = GFExcelAdmin::get_instance();
    }

    /**
     * Whether or not to show notes based on setting or filter
     * @return bool
     */
    public function showNotes()
    {
        $value = false; //default
        if ($setting = $this->admin->get_plugin_setting('notes_enabled')) {
            $value = (bool) $setting;
        }

        $form = $this->getForm();

        if (array_key_exists(GFExcel::KEY_ENABLED_NOTES, $form)) {
            $value = $form[GFExcel::KEY_ENABLED_NOTES];
        }

        return (bool) gf_apply_filters([
            'gfexcel_field_notes_enabled',
            $form['id'],
        ], $value);
    }

    /**
     * Get field to sort the data by
     * @return mixed
     */
    public function getSortField()
    {
        $value = 'date_created';

        $form = $this->getForm();
        if (array_key_exists('gfexcel_output_sort_field', $form)) {
            $value = $form['gfexcel_output_sort_field'];
        }

        return gf_apply_filters(array('gfexcel_output_sort_field', $form['id']), $value);
    }

    /**
     * In what order should the data be sorted
     * @return string
     */
    public function getSortOrder()
    {
        $value = 'ASC'; //default
        $form = $this->getForm();

        if (array_key_exists("gfexcel_output_sort_order", $form)) {
            $value = $form["gfexcel_output_sort_order"];
        }

        $value = gf_apply_filters(array('gfexcel_output_sort_order', $form['id']), $value);
        //force either ASC or DESC
        return $value === "ASC" ? "ASC" : "DESC";
    }

    /**
     * Return the notifications for this form
     * @return array
     */
    public function getNotifications()
    {
        return rgar($this->form, 'notifications', []);
    }

    /**
     * @return string
     */
    public function getSelectedNotification()
    {
        return rgar($this->form, GFExcel::KEY_ATTACHMENT_NOTIFICATION, '');
    }

    /**
     * Get the form instance
     * @return array|false
     */
    public function getForm()
    {
        return $this->form;
    }
}
