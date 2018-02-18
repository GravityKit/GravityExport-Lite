<?php

namespace GFExcel;

use GFAddOn;
use GFFormsModel;

class GFExcelAdmin extends GFAddOn
{
    protected $_version;

    protected $_min_gravityforms_version = "1.9";

    protected $_short_title;

    protected $_title;

    protected $_slug;

    public function __construct()
    {
        $this->_version = GFExcel::$version;
        $this->_title = GFExcel::$name;
        $this->_short_title = GFExcel::$shortname;
        $this->_slug = GFExcel::$slug;

        parent::__construct();
    }

    public function form_settings($form)
    {
        if ($this->is_postback()) {
            $this->saveSettings($form);
            $form = GFFormsModel::get_form_meta($form['id']);
        }

        printf(
            '<h3>%s</h3>',
            esc_html__(GFExcel::$name, 'gfexcel')
        );

        printf('<p>%s</p>',
            esc_html__('Download url:', 'gfexcel')
        );

        $url = GFExcel::url($form);

        printf(
            "<p>
                <input style='width:80%%;' type='text' value='%s' readonly />
            </p>",
            $url
        );

        printf(
            "<p>
                <a class='button-primary' href='%s' target='_blank'>%s</a>
                " . __("Download count", "gfexcel") . ": %d
            </p>",
            $url,
            esc_html__('Download', 'gfexcel'),
            $this->download_count($form)
        );
        echo "<br/>";

        echo "<form method=\"post\">";
        echo "<h4 class='gf_settings_subgroup_title'>" . __("Settings", "gfexcel") . "</h4>";
        printf("<p>" . __("Order by: ") . "%s %s",
            $this->select_sort_field_options($form),
            $this->select_order_options($form)
        );
        echo "<p><button type=\"submit\" class=\"button\">" . __("Save settings", "gfexcel") . "</button></p>";

        echo "</form>";
    }

    /**
     * Returns the number of downloads
     * @param $form
     * @return int
     */
    private function download_count($form)
    {
        if (array_key_exists("gfexcel_download_count", $form)) {
            return (int) $form["gfexcel_download_count"];
        }

        return 0;
    }

    private function select_sort_field_options($form)
    {
        $value = GFExcelOutput::getSortField($form['id']);
        $options = array_reduce($form["fields"], function ($options, \GF_Field $field) use ($value) {
            $options .= "<option value=\"" . $field->id . "\"" . ((int) $value === $field->id ? " selected" : "") . ">" . $field->label . "</option>";
            return $options;
        }, "<option value=\"date_created\">" . __("Date of entry", "gfexcel") . "</option>");

        return "<select name=\"gfexcel_output_sort_field\">" . $options . "</select>";
    }

    private function select_order_options($form)
    {
        $value = GFExcelOutput::getSortOrder($form['id']);
        $options = "<option value=\"ASC\"" . ($value === "ASC" ? " selected" : "") . ">" . __("Acending", "gfexcel") . " </option >
                    <option value = \"DESC\"" . ($value === "DESC" ? " selected" : "") . ">" . __("Descending",
                "gfexcel") . "</option>";

        return "<select name=\"gfexcel_output_sort_order\">" . $options . "</select>";
    }

    private function saveSettings($form)
    {
        /** php5.3 proof. */
        $gfexcel_keys = array_filter(array_keys($_POST), function ($key) {
            return stripos($key, 'gfexcel_') === 0;
        });

        $form_meta = GFFormsModel::get_form_meta($form['id']);

        foreach ($gfexcel_keys as $key) {
            $form_meta[$key] = $_POST[$key];
        }
        GFFormsModel::update_form_meta($form['id'], $form_meta);
    }
}