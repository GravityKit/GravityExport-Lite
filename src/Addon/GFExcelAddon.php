<?php

namespace GFExcel\Addon;

use GFExcel\GFExcel;
use GFExcel\Repository\FieldsRepository;

/**
 * GravityExport Lite add-on.
 * @since $ver$
 */
class GFExcelAddon extends \GFFeedAddon implements AddonInterface
{
    use AddonTrait;

    /**
     * @inheritdoc
     * @since $ver$
     */
    protected $_multiple_feeds = false;

    /**
     * @inheritdoc
     * @since $ver$
     */
    protected $_title = 'GravityExport Lite (V2)';

    /**
     * @inheritdoc
     * @since $ver$
     */
    protected $_short_title = 'GravityExport Lite (V2)';

    /**
     * @inheritdoc
     * @since $ver$
     */
    protected $_slug = 'gravityexport-lite';

    /**
     * @since $ver$
     * @var string Feed settings permissions.
     */
    protected $_capabilities_form_settings = 'gravityforms_export_entries';

    /**
     * @inheritdoc
     * @since $ver$
     */
    public function feed_settings_fields(): array
    {
        $feed = $this->get_current_feed();

        $settings_sections[] =
            [
                'title' => __('Security Settings', GFExcel::$slug),
                'fields' => [
                    [
                        'name' => 'is_secured',
                        'label' => esc_html__('Download Permissions', GFExcel::$slug),
                        'type' => 'select',
                        'description' => sprintf(
                            esc_html__(
                                'If set to "Everyone can download", anyone with the link can download. If "Logged-in users who have \'Export Entries\' access" is selected, users must be logged-in and have the %s capability.',
                                GFExcel::$slug
                            ),
                            '<code>gravityforms_export_entries</code>'
                        ),
                        'default_value' => 0,
                        'choices' => (static function (): array {
                            $options = [];
                            if (!GFExcel::isAllSecured()) {
                                $options[] = [
                                    'name' => 'is_secured',
                                    'label' => __('Everyone can download', GFExcel::$slug),
                                    'value' => 0,
                                ];
                            }
                            $options[] = [
                                'name' => 'is_secured',
                                'label' => __('Logged-in users who have "Export Entries" access', GFExcel::$slug),
                                'value' => 1,
                            ];

                            return $options;
                        })(),
                    ],
                ],
            ];

        $settings_sections[] = apply_filters(
            'gfexcel_general_settings',
            [
                'title' => __('General Settings', GFExcel::$slug),
                'fields' => [
                    [
                        'name' => 'enable_notes',
                        'label' => esc_html__('Include Entry Notes', GFExcel::$slug),
                        'type' => 'checkbox',
                        'choices' => [
                            [
                                'name' => 'enable_notes',
                                'label' => esc_html__('Yes, enable the notes for every entry', GFExcel::$slug),
                                'value' => '1',
                            ],
                        ],
                    ],
                    [
                        'name' => 'order_by',
                        'label' => esc_html__('Order By', GFExcel::$slug),
                        'type' => 'callback',
                        'callback' => function () {
                            $sort_field = [
                                'name' => 'sort_field',
                                'choices' => (new FieldsRepository($this->get_current_form()))->getSortFieldOptions(),
                            ];

                            $sort_order = [
                                'name' => 'sort_order',
                                'type' => 'select',
                                'choices' => [
                                    [
                                        'value' => 'ASC',
                                        'label' => esc_html__('Ascending', 'gk-gravityexport'),
                                    ],
                                    [
                                        'value' => 'DESC',
                                        'label' => esc_html__('Descending', 'gk-gravityexport'),
                                    ],
                                ],
                            ];

                            $this->settings_select($sort_field);
                            $this->settings_select($sort_order);
                        },
                    ],
                    [
                        'name' => 'is_transposed',
                        'type' => 'radio',
                        'label' => esc_html__('Column Position', GFExcel::$slug),
                        'default_value' => 0,
                        'choices' => [
                            [
                                'name' => 'is_transposed',
                                'label' => esc_html__('At the top (normal)', GFExcel::$slug),
                                'value' => 0,
                            ],
                            [
                                'name' => 'is_transposed',
                                'label' => esc_html__('At the left (transposed)', GFExcel::$slug),
                                'value' => 1,
                            ],
                        ],
                    ],
                    [
                        'label' => esc_html__('Custom Filename', GFExcel::$slug),
                        'type' => 'text',
                        'name' => 'custom_filename',
                        'class' => 'medium code',
                        'description' => sprintf(
                            esc_html__(
                                'Most non-alphanumeric characters will be replaced with hyphens. Leave empty for default (for example: %s).',
                                'gk-gravityexport'
                            ),
                            '<code>' . esc_html(GFExcel::getFilename($this->get_current_form())) . '</code>'
                        ),
                        'save_callback' => function ($field, $value) {
                            return sanitize_file_name($value);
                        },
                    ],
                    [
                        'label' => esc_html__('File Extension', GFExcel::$slug),
                        'type' => 'select',
                        'name' => 'file_extension',
                        'class'   => 'small-text',
                        'description' => sprintf(
                            esc_html__(
                                'Note: You may override the file type by adding the desired extension (%s) to the end of the Download URL.',
                                GFExcel::$slug
                            ),
                            '<code>.' . implode('</code>, <code>.', GFExcel::getPluginFileExtensions()) . '</code>'
                        ),
                        'choices' => array_map(static function ($extension) {
                            return
                                [
                                    'name' => 'file_extension',
                                    'label' => '.' . $extension,
                                    'value' => $extension,
                                ];
                        }, GFExcel::getPluginFileExtensions()),
                    ],
                    [
                        'label' => esc_html__('Attach Single Entry to Notification', GFExcel::$slug),
                        'type' => 'select',
                        'name' => 'attachment_notification',
                        'choices' => $this->getNotifications(),
                    ],
                ],
            ]
        );

        return $settings_sections;
    }

    /**
     * @inheritdoc
     * @since $ver$
     */
    public function get_menu_icon(): string
    {
        return '<svg style="height: 24px; width: 37px;" enable-background="new 0 0 226 148" height="148" viewBox="0 0 226 148" width="226" xmlns="http://www.w3.org/2000/svg"><path d="m176.8 118.8c-1.6 1.6-4.1 1.6-5.7 0l-5.7-5.7c-1.6-1.6-1.6-4.1 0-5.7l27.6-27.4h-49.2c-4.3 39.6-40 68.2-79.6 63.9s-68.2-40-63.9-79.6 40.1-68.2 79.7-63.9c25.9 2.8 48.3 19.5 58.5 43.5.6 1.5-.1 3.3-1.7 3.9-.4.1-.7.2-1.1.2h-9.9c-1.9 0-3.6-1.1-4.4-2.7-14.7-27.1-48.7-37.1-75.8-22.4s-37.2 48.8-22.4 75.9 48.8 37.2 75.9 22.4c15.5-8.4 26.1-23.7 28.6-41.2h-59.4c-2.2 0-4-1.8-4-4v-8c0-2.2 1.8-4 4-4h124.7l-27.5-27.5c-1.6-1.6-1.6-4.1 0-5.7l5.7-5.7c1.6-1.6 4.1-1.6 5.7 0l41.1 41.2c3.1 3.1 3.1 8.2 0 11.3z"/></svg>';
    }

    /**
     * @inheritdoc
     * @since $ver$
     */
    public function plugin_settings_icon(): string
    {
        return $this->get_menu_icon();
    }

    /**
     * Returns the notification options list.
     * @since $ver$
     * @return mixed[] The notification options.
     */
    private function getNotifications(): array
    {
        $options = [['label' => __('Select a Notification', GFExcel::$slug), 'value' => '']];
        $notifications = $this->get_current_form()['notifications'] ?? [];
        foreach ($notifications as $key => $notification) {
            $options[] = ['label' => \rgar($notification, 'name', __('Unknown')), 'value' => $key];
        }

        return $options;
    }
}