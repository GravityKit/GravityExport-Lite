<?php

namespace GFExcel;

use GFAddOn;
use GFCommon;
use GFExcel\Renderer\PHPExcelMultisheetRenderer;
use GFExcel\Renderer\PHPExcelRenderer;
use GFExcel\Repository\FieldsRepository;
use GFFormsModel;

class GFExcelAdmin extends GFAddOn
{
    const BULK_DOWNLOAD = 'gfexcel_download';

    protected $_version;

    protected $_min_gravityforms_version = "1.9";

    protected $_short_title;

    protected $_title;

    protected $_slug;

    protected $_capabilities_form_settings = ['gravityforms_export_entries'];

    public function __construct()
    {
        $this->_version = GFExcel::$version;
        $this->_title = __(GFExcel::$name, GFExcel::$slug);
        $this->_short_title = __(GFExcel::$shortname, GFExcel::$slug);
        $this->_slug = GFExcel::$slug;

        add_action("bulk_actions-toplevel_page_gf_edit_forms", array($this, "bulk_actions"), 10, 2);
        add_action("wp_loaded", array($this, 'handle_bulk_actions'));
        add_action("admin_head", array($this, 'set_scripts'));

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

        echo "<style>
                .gaddon-setting-inline { display:inline-block; line-height: 26px; }
                span.gf_settings_description { font-size: smaller; } 
                ul.fields-select {
                    background-color: #FFF;
                    padding: 3px 5px;
                    border: 1px solid #E1E1E1;
                    min-height: 30px;
                }
                ul.fields-select li {
                    display: flex;
                }
                ul.fields-select li div:not(.move) {
                    flex: 1;
                }
                ul.fields-select li div.move { 
                    font-weight: bold;
                    padding: 0 5px;
                    cursor: pointer;
                }
                ul.fields-select li div.move:hover {
                    color: #006799;
                }
                .ui-sortable-handle {
                    cursor: pointer;
                    cursor: -webkit-grab;
                }
                .ui-sortable-helper {
                    cursor: -webkit-grabbing;
                }
                ul.fields-select li {
                    background-color: #f7f7f7;
                    margin: 2px 0;
                    padding: 3px;
                }
                .gaddon-section.sortfields table {
                    table-layout: fixed;
                }
                .gaddon-section.sortfields table td {
                    padding-right: 10px;
                }
                .gaddon-section.sortfields table td + td {
                    padding-left: 10px;
                    padding-right: 10px;
                }
                .gaddon-section.sortfields p {
                    margin-bottom: 10px;
                }
              </style>";

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

        $this->sortableFields($form);

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
            if ($key === FieldsRepository::KEY_DISABLED_FIELDS) {
                if (is_array($value)) {
                    $value = implode(',', array_keys(array_filter($value)));
                }
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
                                'label' => '.' . $extension,
                                'value' => $extension,
                            ];
                    }, ['xlsx', 'xls', 'csv',]),
                    'description' => __('Please note that .xls does not support unicode 😐, and the output will not be readable.', GFExcel::$slug),]],]);
    }

    private function sortableFields($form)
    {
        $repository = new FieldsRepository($form);
        $disabled_fields = $repository->get_disabled_fields();
        $all_fields = $repository->getFields($unfiltered = true);
        $active_fields = $inactive_fields = [];
        foreach ($all_fields as $field) {
            $array_name = in_array($field->id, $disabled_fields) ? 'inactive_fields' : 'active_fields';
            array_push($$array_name, $field);
        }
        $active_fields = $repository->sortFields($active_fields);

        $this->single_section([
            'title' => __('Disabled fields from export', GFExcel::$slug),
            'class' => 'sortfields',
            'fields' => [
                [
                    'label' => __('Disabled fields', GFExcel::$slug),
                    'name' => 'gfexcel_disabled_fields',
                    'move_to' => 'gfexcel_enabled_fields',
                    'type' => 'sortable',
                    'class' => 'fields-select',
                    'side' => 'left',
                    'choices' => array_map(function (\GF_Field $field) {
                        return [
                            'value' => $field->id,
                            'label' => $field->label,
                        ];
                    }, $inactive_fields),
                ], [
                    'label' => __('Drop & sort the fields to enable', GFExcel::$slug),
                    'name' => 'gfexcel_enabled_fields',
                    'move_to' => 'gfexcel_disabled_fields',
                    'type' => 'sortable',
                    'class' => 'fields-select',
                    'side' => 'right',
                    'choices' => array_map(function (\GF_Field $field) {
                        return [
                            'value' => $field->id,
                            'label' => $field->label,
                        ];
                    }, $active_fields),
                ],
            ],
        ]);
    }

    public function settings_sortable($field, $echo = true)
    {
        $attributes = $this->get_field_attributes($field);
        $name = '' . esc_attr($field['name']);

        // If no choices were provided and there is a no choices message, display it.
        if ((empty($field['choices']) || !rgar($field, 'choices')) && rgar($field, 'no_choices')) {
            $html = $field['no_choices'];
        } else {

            $html = sprintf(
                '<input type="hidden" name="%1$s">
                    <ul id="%2$s" %3$s data-send-to="%5$s">%4$s</ul>',
                '_gaddon_setting_' . $name, $name, implode(' ', $attributes), implode("\n", array_map(function ($choice) {
                return sprintf('<li data-value="%s"><div>%s</div><div class="move">&times;</div></li>', $choice['value'], $choice['label']);
            }, $field['choices'])), $field['move_to']);

            $html .= rgar($field, 'after_select');

        }

        if ($this->field_failed_validation($field)) {
            $html .= $this->get_error_icon($field);
        }

        if ($echo) {
            echo $html;
        }

        return $html;
    }

    public function single_setting_row_sortable($field)
    {

        $display = rgar($field, 'hidden') || rgar($field, 'type') == 'hidden' ? 'style="display:none;"' : '';

        // Prepare setting description.
        $description = rgar($field, 'description') ? '<span class="gf_settings_description">' . $field['description'] . '</span>' : null;

        if (array_key_exists('side', $field) && $field['side'] === "left") {
            ?>
            <tr id="gaddon-setting-row-<?php echo $field['name'] ?>" <?php echo $display; ?>>
        <?php } ?>
        <td style="vertical-align: top; ">
            <p><strong><?php $this->single_setting_label($field); ?></strong></p>
            <?php
            $this->single_setting($field);
            echo $description;
            ?>
        </td>
        <?php if (array_key_exists('side', $field) && $field['side'] === "right") { ?></tr><?php }
    }

    /**
     * Adds javascript for the sortable to the page
     * @param array $ids
     * @param string $connector_class
     * @return string
     */
    private function sortable_script(array $ids, $connector_class = 'connected-sortable')
    {
        $ids = implode(', ', array_map(function ($id) {
            return '#' . $id;
        }, $ids));

        return '<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
        <script>(function($) {
            $(document).ready(function() {
                var $elements = $(\'' . $ids . '\');
                var updateLists = function($elements) {
                  $elements.each(function(i,el) {
                    var $input = $(el).prev();
                    $input.val($(el).sortable(\'toArray\',{ attribute: "data-value"}).join(","));                   
                  })
                };
                $elements.sortable({
                    connectWith: \'.' . $connector_class . '\',
                    update: function(e,ui) {
                       updateLists($elements);
                    }
                }).disableSelection();
               
                $elements.on(\'click\',\'.move\',function() {
                    var element = $(this).closest(\'li\');
                    element.appendTo($(\'#\'+element.closest(\'ul\').data(\'send-to\')));
                    $elements.sortable(\'refresh\');
                    updateLists($elements);
                });
            });
                
        })(jQuery);</script>';
    }

    /**
     * add scripts to header
     */
    public function set_scripts()
    {
        echo $this->sortable_script(['gfexcel_enabled_fields', 'gfexcel_disabled_fields'], 'fields-select');
    }
}