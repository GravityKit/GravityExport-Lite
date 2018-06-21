<?php

namespace GFExcel;

use GFAddOn;
use GFCommon;
use GFExcel\Renderer\PHPExcelMultisheetRenderer;
use GFExcel\Renderer\PHPExcelRenderer;
use GFExport;
use GFFormsModel;

class GFExcelAdmin extends GFAddOn
{
    const BULK_DOWNLOAD = 'gfexcel_download';

    protected $_version;

    protected $_min_gravityforms_version = "1.9";

    protected $_short_title;

    protected $_title;

    protected $_slug;

    public function __construct()
    {
        $this->_version = GFExcel::$version;
        $this->_title = __(GFExcel::$name, GFExcel::$slug);
        $this->_short_title = __(GFExcel::$shortname, GFExcel::$slug);
        $this->_slug = GFExcel::$slug;

        add_action("bulk_actions-toplevel_page_gf_edit_forms", array($this, "bulk_actions"), 10, 2);
        add_action("wp_loaded", array($this, 'handle_bulk_actions'));

        parent::__construct();
    }

    public function form_settings($form)
    {

        if ($this->is_save_postback()) {
            $this->saveSettings($form);
            $form = GFFormsModel::get_form_meta($form['id']);
        }

        if ($this->is_postback()) {
            if (!rgempty('regenerate_hash')) {
                $form = GFExcel::setHash($form['id']);
                GFCommon::add_message(__('The download url has been regenerated.', GFExcel::$slug), false);
            }
        }

        GFCommon::display_admin_message();
        printf(
            '<h3>%s</h3>',
            esc_html__(GFExcel::$name, GFExcel::$slug)
        );

        printf('<h4 class="gaddon-section-title gf_settings_subgroup_title">%s:</h4>',
            esc_html__('Download url', GFExcel::$slug)
        );

        $url = GFExcel::url($form);

        printf(
            "<p>
                <input style='width:80%%;' type='text' value='%s' readonly />
            </p>",
            $url
        );

        echo "<style>.gaddon-setting-inline { display:inline-block; line-height: 26px; }</style>";

        echo "<form method=\"post\">";
        printf(
            "<p>
                <input 
                onclick=\"return confirm('" . __('This changed the download url permanently!', GFExcel::$slug) . "');\" 
                class='button' type='submit' name='regenerate_hash' 
                value='" . __('Regenerate url', GFExcel::$slug) . "'/> 
                <a class='button-primary' href=' % s' target='_blank'>%s</a>
                " . __("Download count", GFExcel::$slug) . ": %d
            </p>",
            $url,
            esc_html__('Download', GFExcel::$slug),
            $this->download_count($form)
        );
        echo "<br/>";

        $disabled_fields = GFExcel::get_disabled_fields($form);
        $meta = GFExport::add_default_export_fields(array('id' => $form['id'], 'fields' => array()));

        $this->single_section([
            'title' => __('Disable fields from export', GFExcel::$slug),
            'fields' => [
                [
                    'label' => __('Select the fields to disable', GFExcel::$slug),
                    'name' => 'gfexcel_disable_fields[]',
                    'type' => 'checkbox',
                    'horizontal' => true,

                    'choices' => array_reduce($form['fields'], function ($fields, \GF_Field $field) use ($disabled_fields) {
                        $fields[] = [
                            'name' => GFExcel::KEY_DISABLED_FIELDS . '[' . $field->id . ']',
                            'value' => (int) in_array($field->id, $disabled_fields),
                            'default_value' => (int) in_array($field->id, $disabled_fields),
                            'label' => $field->label,
                        ];

                        return $fields;
                    }, []),
                ],

                [
                    'label' => __('Select the meta fields to disable', GFExcel::$slug),
                    'name' => 'gfexcel_disable_fields[]',
                    'type' => 'checkbox',
                    'horizontal' => true,

                    'choices' => array_reduce($meta['fields'], function ($fields, \GF_Field $field) use ($disabled_fields) {
                        $fields[] = [
                            'name' => GFExcel::KEY_DISABLED_FIELDS . '[' . $field->id . ']',
                            'value' => (int) in_array($field->id, $disabled_fields),
                            'default_value' => (int) in_array($field->id, $disabled_fields),
                            'label' => $field->label,
                        ];

                        return $fields;
                    }, []),
                ],
            ],
        ]);


        echo "<h4 class='gf_settings_subgroup_title'>" . __("Settings", GFExcel::$slug) . "</h4>";
        printf("<p>" . __("Order by", GFExcel::$slug) . ": %s %s <br/> %s",
            $this->select_sort_field_options($form),
            $this->select_order_options($form),
            $this->settings_save(['value' => __("Save settings", GFExcel::$slug)], false)
        );


        echo "</form>";
    }


    public function handle_bulk_actions()
    {
        if (!current_user_can('editor') &&
            !current_user_can('administrator') &&
            !GFCommon::current_user_can_any('gravityforms_export_entries')) {
            return false; // How you doin?
        }

        if ($this->current_action() === self::BULK_DOWNLOAD && array_key_exists('form', $_REQUEST)) {
            $form_ids = (array) $_REQUEST['form'];
            if (count($form_ids) < 1) {
                return false;
            }
            $renderer = count($form_ids) > 1
                ? new PHPExcelMultisheetRenderer()
                : new PHPExcelRenderer();

            foreach ($form_ids as $form_id) {
                $output = new GFExcelOutput((int) $form_id, $renderer);
                $output->render();
            }

            $renderer->renderOutput();
            return true;
        }

        return false; // i'm DONE!
    }

    /**
     * Add GFExcel download option to bulk actions dropdown
     * @param $actions
     * @return array
     */
    public function bulk_actions($actions)
    {
        $actions[self::BULK_DOWNLOAD] = esc_html__('Download as one Excel file', GFExcel::$slug);
        return $actions;
    }

    private function current_action()
    {
        if (isset($_REQUEST['filter_action']) && !empty($_REQUEST['filter_action'])) {
            return false;
        }

        if (isset($_REQUEST['action']) && -1 != $_REQUEST['action']) {
            return $_REQUEST['action'];
        }

        if (isset($_REQUEST['action2']) && -1 != $_REQUEST['action2']) {
            return $_REQUEST['action2'];
        }

        return false;
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
        }, "<option value=\"date_created\">" . __("Date of entry", GFExcel::$slug) . "</option>");

        return "<select name=\"gfexcel_output_sort_field\">" . $options . "</select>";
    }

    private function select_order_options($form)
    {
        $value = GFExcelOutput::getSortOrder($form['id']);
        $options = "<option value=\"ASC\"" . ($value === "ASC" ? " selected" : "") . ">" . __("Acending",
                GFExcel::$slug) . " </option >
                    <option value = \"DESC\"" . ($value === "DESC" ? " selected" : "") . ">" . __("Descending",
                GFExcel::$slug) . "</option>";

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

        foreach ($this->get_posted_settings() as $key => $value) {
            if ($key === GFExcel::KEY_DISABLED_FIELDS) {
                $value = implode(',', array_keys(array_filter($value)));
            }

            $form_meta[$key] = $value;
        }

        GFFormsModel::update_form_meta($form['id'], $form_meta);
        GFCommon::add_message(__('The settings have been saved.', GFExcel::$slug), false);
    }


}