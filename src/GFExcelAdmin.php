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

        $this->generalSettings($form);

        $this->disableFields($form);

        $this->settings_save(['value' => __("Save settings", GFExcel::$slug)]);
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
        $fields = array_merge([
            [
                'value' => 'date_created',
                'label' => __("Date of entry", GFExcel::$slug),
            ]
        ], array_map(function ($field) {
            return [
                'value' => $field->id,
                'label' => $field->label,
            ];
        }, (array) $form['fields']));

        $this->settings_select([
            'name' => 'gfexcel_output_sort_field',
            'choices' => $fields,
            'default_value' => GFExcelOutput::getSortField($form['id']),
        ]);
    }

    private function select_order_options($form)
    {
        $this->settings_select([
            'name' => 'gfexcel_output_sort_order',
            'choices' => [[
                'value' => 'ASC',
                'label' => __("Acending", GFExcel::$slug)
            ], [
                'value' => 'DESC',
                'label' => __("Descending", GFExcel::$slug)
            ]],
            'default_value' => GFExcelOutput::getSortOrder($form['id']),
        ]);
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
            if ($key === GFExcel::KEY_CUSTOM_FILENAME) {
                $value = preg_replace('/\.(xlsx?|csv)$/is', '', $value);
                $value = preg_replace('/[^a-z0-9_-]+/is', '_', $value);
            }
            $form_meta[$key] = $value;
        }

        GFFormsModel::update_form_meta($form['id'], $form_meta);
        GFCommon::add_message(__('The settings have been saved.', GFExcel::$slug), false);
    }

    /**
     * Add settings for disabling fields
     * @param $form
     */
    private function disableFields($form)
    {
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

                    'choices' => array_reduce((array) $form['fields'], function ($fields, \GF_Field $field) use ($disabled_fields) {
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

    }

    /**
     * Remove filename so it returns the newly formatted filename
     *
     * @return array
     */
    public function get_current_settings()
    {
        $settings = parent::get_current_settings();
        unset($settings[GFExcel::KEY_CUSTOM_FILENAME]);

        return $settings;
    }

    private function generalSettings($form)
    {
        $this->single_section([
            'title' => __('General settings', GFExcel::$slug),
            'fields' => [
                [
                    'name' => 'enable_notes',
                    'label' => __('Enable notes', GFExcel::$slug),
                    'type' => 'checkbox',
                    'choices' => [[
                        'name' => GFExcel::KEY_ENABLED_NOTES,
                        'label' => __('Yes, enable the notes for every entry', GFExcel::$slug),
                        'value' => '1',
                        'default_value' => (int) @$form[GFExcel::KEY_ENABLED_NOTES],
                    ]],
                ],
                [
                    'name' => 'order_by',
                    'type' => 'callback',
                    'label' => __("Order by", GFExcel::$slug),
                    'callback' => function () use ($form) {
                        $this->select_sort_field_options($form);
                        echo ' ';
                        $this->select_order_options($form);
                    }
                ],
                [
                    'label' => __('Custom filename', GFExcel::$slug),
                    'type' => 'text',
                    'name' => GFExcel::KEY_CUSTOM_FILENAME,
                    'value' => @$form[GFExcel::KEY_CUSTOM_FILENAME],
                    'description' => __('Only letters, numbers and dashes are allowed. The rest will be stripped. Leave empty for default.', GFExcel::$slug)
                ],
                [
                    'label' => __('File extension', GFExcel::$slug),
                    'type' => 'select',
                    'name' => GFExcel::KEY_FILE_EXTENSION,
                    'default_value' => @$form[GFExcel::KEY_FILE_EXTENSION],
                    'choices' => array_map(function ($extension) {
                        return
                            [
                                'name' => GFExcel::KEY_FILE_EXTENSION,
                                'label' => '.'.$extension,
                                'value' => $extension,
                            ];
                    }, ['xlsx', 'xls', 'csv',]),
                    'description' => __('Please note that .xls does not support unicode, and the output will not be readable.', GFExcel::$slug),]],]);
    }
}