<?php

namespace GFExcel;

use GFAddOn;
use GFCommon;
use GFExcel\Renderer\PHPExcelMultisheetRenderer;
use GFExcel\Renderer\PHPExcelRenderer;
use GFExcel\Repository\FieldsRepository;
use GFExcel\Repository\FormsRepository;
use GFFormsModel;

class GFExcelAdmin extends GFAddOn
{
    const BULK_DOWNLOAD = 'gfexcel_download';

    private static $_instance = null;

    protected $_min_gravityforms_version = "2.0";

    protected $_capabilities_form_settings = ['gravityforms_export_entries'];

    /** @var FormsRepository micro cache */
    private $repository;

    /** @var string  micro cache for file name */
    private $_file = '';

    /**
     * @return string
     */
    public function plugin_settings_icon()
    {
        return '<i class="fa fa-table"></i>';
    }

    public function __construct()
    {
        $this->_version = GFExcel::$version;
        $this->_title = __(GFExcel::$name, GFExcel::$slug);
        $this->_short_title = __(GFExcel::$shortname, GFExcel::$slug);
        $this->_slug = GFExcel::$slug;

        parent::__construct();
    }

    /**
     * Set minimum requirements to prevent bugs when using older versions, or missing dependencies
     * @return array
     */
    public function minimum_requirements()
    {
        return [
            'php' => [
                'version' => '5.6',
                'extensions' => [
                    'zip', 'ctype', 'dom', 'zlib',
                ],
            ]
        ];
    }

    public function render_uninstall()
    {
        return null;
    }

    public function plugin_settings_fields()
    {
        return [[
            'description' => $this->plugin_settings_description(),
            'fields' => [[
                'name' => 'field_address',
                'label' => esc_html__('Address columns', GFExcel::$slug),
                'type' => 'checkbox',
                'choices' => [[
                    'label' => esc_html__('Split address field into multiple columns', GFExcel::$slug),
                    'name' => 'field_address_split_enabled',
                    'default_value' => false,
                ]]
            ], [
                'name' => 'notes',
                'label' => esc_html__('Notes', 'gravityforms'),
                'type' => 'checkbox',
                'choices' => [[
                    'label' => esc_html__('Enable notes by default', GFExcel::$slug),
                    'name' => 'notes_enabled',
                    'default_value' => false,
                ]]
            ], [
                'name' => 'sections',
                'label' => esc_html__('Sections', GFExcel::$slug),
                'type' => 'checkbox',
                'choices' => [[
                    'label' => esc_html__('Enable (empty) section column', GFExcel::$slug),
                    'name' => 'sections_enabled',
                    'default_value' => false,
                ]]
            ], [
                'name' => 'fileuploads',
                'label' => esc_html__('File uploads', GFExcel::$slug),
                'type' => 'checkbox',

                'choices' => [[
                    'label' => esc_html__('Enable file upload columns', GFExcel::$slug),
                    'name' => 'fileuploads_enabled',
                    'default_value' => true,
                ]]
            ], [
                'name' => 'hyperlinks',
                'label' => esc_html__('Hyperlinks', GFExcel::$slug),
                'type' => 'checkbox',

                'choices' => [[
                    'label' => esc_html__('Enable hyperlinks on url-only columns', GFExcel::$slug),
                    'name' => 'hyperlinks_enabled',
                    'default_value' => true,
                ]]
            ]],
        ], [
            'fields' => [
                [
                    'name' => 'enabled_metafields',
                    'label' => esc_html__('Enabled meta fields', GFExcel::$slug),
                    'description' => esc_html__('Select all meta fields that are enabled by default', GFExcel::$slug),
                    'type' => 'checkbox',

                    'choices' => $this->meta_fields(),
                ]
            ]
        ]];
    }

    public function init_admin()
    {
        parent::init_admin();

        add_action("bulk_actions-toplevel_page_gf_edit_forms", [$this, "bulk_actions"], 10, 2);
        add_action("wp_loaded", [$this, 'handle_bulk_actions']);
        add_action("admin_enqueue_scripts", [$this, "register_assets"]);
    }

    public function init()
    {
        parent::init();

        if ($form = $this->get_current_form()) {
            $this->repository = new FormsRepository($form['id']);
        }

        add_action('gform_notification', [$this, 'handle_notification'], 10, 3);
        add_action('gform_after_email', [$this, 'remove_temporary_file'], 10, 13);
        add_filter('plugin_row_meta', [__CLASS__, 'plugin_row_meta'], 10, 2);
        add_filter('plugin_action_links', [__CLASS__, 'plugin_action_links'], 10, 2);
    }

    public function render_settings($sections)
    {
        parent::render_settings($sections);
        ?>
        <div class="hr-divider"></div>

        <a name="help-me-out"></a>
        <h3><span><i class="fa fa-exclamation-circle"></i> <?php esc_html_e('Help me out!', 'gf-entries-in-excel'); ?></span>
        </h3>

        <p>
            <?php
            esc_html_e('I honestly ❤️ developing this plugin. It\'s fun, I get some practice, and I want to give back to the open-source community. But a good plugin, is a plugin that is constantly being updated and getting better. And I need your help to achieve this!', 'gf-entries-in-excel');
            ?>
        </p>
        <p>
            <?php
            printf(' ' . esc_html__('If you find a bug 🐞 or need a feature 💡, %slet me know%s! I\'m very open to suggestions and ways to make the plugin more accessible.', 'gf-entries-in-excel'), '<a href="https://wordpress.org/support/plugin/gf-entries-in-excel" target="_blank">', '</a>');
            ?>
        </p>
        <p>
            <?php
            printf(' ' . esc_html__('If you like the plugin, let me know, and maybe more important; 📣 %slet others know%s! We already have more than 2000+ active users. Let\'s get to 3k by spreading the news! Be the first to know about updates by %sfollowing me on twitter%s.  ', 'gf-entries-in-excel'), '<a href="https://wordpress.org/support/plugin/gf-entries-in-excel/reviews/#new-post" target="_blank">', '</a>', '<a href="https://twitter.com/doekenorg" target="_blank">','</a>');
            ?>
        </p>
        <p>
            <?php
            esc_html_e('Also, If you ❤️ the plugin, and it helps you a lot, please consider making a small donation 💰 and buy me a beer 🍺.', 'gf-entries-in-excel');
            ?>
        </p>
        <p>
            <a class="button button-cta" href="https://paypal.me/doekenorg"
               target="_blank"><?php _e('Make a donation', 'gf-entries-in-excel'); ?></a>
        </p>

        <?php
    }

    /**
     * Show row meta on the plugin screen.
     *
     * @param   mixed $links Plugin Row Meta.
     * @param   mixed $file Plugin Base file.
     * @return  array
     */
    public static function plugin_row_meta($links, $file)
    {
        if (plugin_basename(GFEXCEL_PLUGIN_FILE) !== $file) {
            return $links;
        }
        return array_merge($links, [
            'donate' => '<a href="' . esc_url('https://www.paypal.me/doekenorg') . '" aria-label="' . esc_attr__('Make a donation', 'gf-entries-in-excel') . '">' . esc_html__('Make a donation', 'gf-entries-in-excel') . '</a>',
        ]);
    }

    /**
     * Add settings link to plugin page
     * @param $links
     * @param $file
     * @return array
     */
    public static function plugin_action_links($links, $file)
    {
        if (plugin_basename(GFEXCEL_PLUGIN_FILE) !== $file) {
            return $links;
        }
        return array_merge([
            'settings' => '<a href="' . admin_url('admin.php?page=gf_settings&subview=gf-entries-in-excel') . '" aria-label="' . esc_attr__('View settings', 'gf-entries-in-excel') . '">' . esc_html__('Settings', 'gf-entries-in-excel') . '</a>',
        ], $links);
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

        echo "<form method=\"post\">";
        printf(
            "<p>
                <input 
                onclick=\"return confirm('" . __('This changes the download url permanently!', GFExcel::$slug) . "');\" 
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
            'default_value' => $this->repository->getSortField(),
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
            'default_value' => $this->repository->getSortOrder(),
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
                $value = preg_replace('/\.(xlsx|csv)$/is', '', $value);
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
                        'default_value' => $this->enabled_notes($form),
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
                    }, ['xlsx', 'csv',]),
                ],
                [
                    'label' => __('Attach single entry to notification', GFExcel::$slug),
                    'type' => 'select',
                    'name' => GFExcel::KEY_ATTACHMENT_NOTIFICATION,
                    'default_value' => @$form[GFExcel::KEY_ATTACHMENT_NOTIFICATION],
                    'choices' => $this->getNotifications(),
                ],
            ],
        ]);
    }

    /**
     * Adds the sortable fields section to the settings page
     */
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
            'title' => __('Field settings', GFExcel::$slug),
            'class' => 'sortfields',
            'fields' => [
                [
                    'label' => __('Disabled fields', GFExcel::$slug),
                    'name' => 'gfexcel_disabled_fields',
                    'move_to' => 'gfexcel_enabled_fields',
                    'type' => 'sortable',
                    'class' => 'fields-select',
                    'side' => 'left',
                    'value' => @$form['gfexcel_disabled_fields'] ?: '',
                    'choices' => array_map(function (\GF_Field $field) {
                        return [
                            'value' => $field->id,
                            'label' => $field->label,
                        ];
                    }, $inactive_fields),
                ], [
                    'label' => __('Enable & sort the fields', GFExcel::$slug),
                    'name' => 'gfexcel_enabled_fields',
                    'value' => @$form['gfexcel_enabled_fields'] ?: '',
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

    /**
     * Renders the html for a single sortable fields.
     * I don't like this inline html approach Gravity Forms uses.
     * @param $field
     * @param bool $echo
     * @return string
     */
    public function settings_sortable($field, $echo = true)
    {
        $attributes = $this->get_field_attributes($field);
        $name = '' . esc_attr($field['name']);
        $value = rgar($field, 'value'); //comma-separated list from database

        // If no choices were provided and there is a no choices message, display it.
        if ((empty($field['choices']) || !rgar($field, 'choices')) && rgar($field, 'no_choices')) {
            $html = $field['no_choices'];
        } else {

            $html = sprintf('<input type="hidden" name="%s" value="%s">', '_gaddon_setting_' . $name, $value);
            $html .= sprintf(
                '<ul id="%1$s" %2$s data-send-to="%4$s">%3$s</ul>',
                $name, implode(' ', $attributes), implode("\n", array_map(function ($choice) use ($field) {
                return sprintf(
                    '<li data-value="%s">
                        <div class="field"><i class="fa fa-bars"></i> %s</div>
                        <div class="move">
                            <i class="fa fa-arrow-right"></i>
                            <i class="fa fa-close"></i>
                        </div>
                    </li>',
                    $choice['value'], $choice['label']
                );
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

    /**
     * Renders the html for the sortable fields
     * @param $field
     */
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

        wp_add_inline_script('gfexcel-js', '(function($) { $(document).ready(function() { gfexcel_sortable(\'' . $ids . '\',\'' . $connector_class . '\'); }); })(jQuery);');
    }

    /**
     * Add javascript and custom css to the page
     */
    public function register_assets()
    {
        $this->sortable_script(['gfexcel_enabled_fields', 'gfexcel_disabled_fields'], 'fields-select');
    }

    /**
     * Get the assets path
     * @return string
     */
    public static function assets()
    {
        return plugin_dir_url(dirname(__DIR__) . '/gfexcel.php');
    }

    public function scripts()
    {
        return array_merge(parent::scripts(), [
            [
                'handle' => 'jquery-ui-sortable',
                'enqueue' => [[
                    'admin_page' => 'form_settings',
                    'tab' => 'gf-entries-in-excel',
                ]],
            ],
            [
                'handle' => 'gfexcel-js',
                'src' => self::assets() . 'public/js/gfexcel.js',
                'enqueue' => [[
                    'admin_page' => 'form_settings',
                    'tab' => 'gf-entries-in-excel',
                ]],
                'deps' => ['jquery', 'jquery-ui-sortable'],
            ],
        ]);
    }

    public function styles()
    {
        return array_merge(parent::styles(), [
            [
                'handle' => 'gfexcel-css',
                'src' => self::assets() . 'public/css/gfexcel.css',
                'enqueue' => [
                    [
                        'admin_page' => 'form_settings',
                        'tab' => 'gf-entries-in-excel',
                    ],
                    [
                        'admin_page' => 'plugin_settings',
                        'tab' => 'gf-entries-in-excel',
                    ],
                ],
            ],
        ]);
    }

    /**
     * @param $notification
     * @param $form
     * @param $entry
     * @return mixed
     */
    public function handle_notification($notification, $form, $entry)
    {
        if (!$this->repository) {
            $this->repository = new FormsRepository($form['id']);
        }

        // get notification to add to by form setting
        if (!$this->repository || $this->repository->getSelectedNotification() !== rgar($notification, 'id')) {
            //Not the right notification
            return $notification;
        }

        // create a file based on the settings in the form, with only this entry.
        $output = new GFExcelOutput($form['id'], new PHPExcelRenderer());
        $output->setEntries([$entry]);

        // save the file to a temporary file
        $this->_file = $output->render($save = true);
        if (!file_exists($this->_file)) {
            return $notification;
        }
        // attach file to $notification['attachments'][]
        $notification['attachments'][] = $this->_file;

        return $notification;
    }


    public static function get_instance()
    {
        if (self::$_instance == null) {
            self::$_instance = new GFExcelAdmin();
        }

        return self::$_instance;
    }

    private function getNotifications()
    {
        $options = [['label' => __('Select a notification', GFExcel::$slug), 'value' => '']];
        foreach ($this->repository->getNotifications() as $key => $notification) {
            $options[] = ['label' => rgar($notification, 'name', __('Unknown')), 'value' => $key];
        }

        return $options;
    }

    public function remove_temporary_file()
    {
        $args = func_get_args();
        $attachments = $args[5];
        if (is_array($attachments) && count($attachments) < 1) {
            return false;
        }
        if (in_array($this->_file, $attachments) && file_exists($this->_file)) {
            unlink($this->_file);
        }
        return true;
    }

    private function plugin_settings_description()
    {
        $html = "<p>";
        $html .= esc_html__('These are global settings for new forms. You can overwrite them per form using the available hooks. Once you\'ve saved your form, these settings will not do anything any more.', GFExcel::$slug);
        $html .= "</p>";

        return $html;
    }

    private function meta_fields()
    {
        $repository = new FieldsRepository(['fields' => []]);

        //suppress notices of missing form. There is no form, we just need the meta data
        $fields = @$repository->getFields(true);

        return array_map(function ($field) {
            return [
                'label' => $field->label,
                'name' => 'enabled_metafield_' . $field->id,
                'default_value' => true,
            ];
        }, $fields);
    }

    private function enabled_notes($form = [])
    {
        if (array_key_exists(GFExcel::KEY_ENABLED_NOTES, $form)) {
            return (int) $form[GFExcel::KEY_ENABLED_NOTES];
        };

        return $this->get_plugin_setting('notes_enabled');
    }

}