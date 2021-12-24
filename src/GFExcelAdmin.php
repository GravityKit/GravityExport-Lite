<?php

namespace GFExcel;

use GFExcel\Action\CountDownloads;
use GFExcel\Addon\AddonInterface;
use GFExcel\Addon\AddonTrait;
use GFExcel\Field\ProductField;
use GFExcel\Field\SeparableField;
use GFExcel\Renderer\PHPExcelMultisheetRenderer;
use GFExcel\Renderer\PHPExcelRenderer;
use GFExcel\Repository\FieldsRepository;
use GFExcel\Repository\FormsRepository;
use Gravity_Forms\Gravity_Forms\Settings\Fields\Base;

class GFExcelAdmin extends \GFAddOn implements AddonInterface
{
    use AddonTrait;

    public const BULK_DOWNLOAD = 'gfexcel_download';

    /**
     * @inheritdoc
     */
    protected $_min_gravityforms_version = '2.0';

    /**
     * @inheritDoc
     */
    protected $_capabilities_form_settings = 'gravityforms_export_entries';

    /**
     * @inheritdoc
     * @since 1.8.0
     */
    protected $_capabilities_settings_page = 'gravityforms_export_entries';

    /**
     * Microcache for form repositories.
     * @since $ver$
     * @var array<int, FormsRepository>
     */
    private $form_repositories = [];

    /** @var string  micro cache for file name */
    private $_file = '';

    /**
     * {@inheritdoc}
     */
    protected $_path = 'gf-entries-in-excel/gfexcel.php';

    /**
     * @inheritdoc
     * @since 1.8.0
     */
    protected $_full_path = __FILE__;

    /**
     * @inheritdoc
     * @since 1.8.0
     */
    protected $_url = 'https://gfexcel.com';

    /**
     * @return string
     */
    public function plugin_settings_icon()
    {
        return $this->get_menu_icon();
    }

    public function __construct()
    {
        $this->_version = GFExcel::$version;
        $this->_title = esc_html__('GravityExport Lite', GFExcel::$slug);
        $this->_short_title = esc_html__('GravityExport Lite', GFExcel::$slug);
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
                'version' => '7.1',
                'extensions' => [
                    'zip',
                    'ctype',
                    'dom',
                    'zlib',
                    'xml',
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
        $settings_sections = [];

        $settings_sections[] = [
            'id'          => 'gravityexport-lite-rating-fieldset',
            'description' => $this->get_rating_message(),
            'fields'      => [
                [
                    'name'  => 'gravityexport-rocks',
                    'type'  => 'hidden',
                    'value' => 'You should try it!',
                ],
            ],
        ];

	    $settings_sections[] = [
            'title' => esc_html__( 'Default Settings', GFExcel::$slug ),
            'description' => $this->plugin_settings_description(),
            'fields' => [
                [
                    'name' => 'field_separate',
                    'label' => esc_html__('Multiple Columns', GFExcel::$slug),
                    'type' => 'checkbox',
                    'choices' => [
                        [
                            'label' => esc_html__(
                                'Split multi-fields (name, address) into multiple columns',
                                GFExcel::$slug
                            ),
                            'name' => SeparableField::SETTING_KEY,
                            // backwards compatible with last known setting
                            'default_value' => static::get_instance()->get_plugin_setting('field_address_split_enabled')
                        ]
                    ]
                ],
                [
                    'name' => 'notes',
                    'label' => esc_html__('Notes', 'gravityforms'),
                    'type' => 'checkbox',
                    'choices' => [
                        [
                            'label' => esc_html__('Enable notes by default', GFExcel::$slug),
                            'name' => 'notes_enabled',
                            'default_value' => false,
                        ]
                    ]
                ],
                [
                    'name' => 'sections',
                    'label' => esc_html__('Sections', GFExcel::$slug),
                    'type' => 'checkbox',
                    'choices' => [
                        [
                            'label' => esc_html__('Enable (empty) section column', GFExcel::$slug),
                            'name' => 'sections_enabled',
                            'default_value' => false,
                        ]
                    ]
                ],
                [
                    'name' => 'fileuploads',
                    'label' => esc_html__('File Uploads', GFExcel::$slug),
                    'type' => 'checkbox',

                    'choices' => [
                        [
                            'label' => esc_html__('Enable file upload columns', GFExcel::$slug),
                            'name' => 'fileuploads_enabled',
                            'default_value' => true,
                        ]
                    ]
                ],
                [
                    'name' => 'hyperlinks',
                    'label' => esc_html__('Hyperlinks', GFExcel::$slug),
                    'type' => 'checkbox',

                    'choices' => [
                        [
                            'label' => esc_html__('Enable hyperlinks on URL-only columns', GFExcel::$slug),
                            'name' => 'hyperlinks_enabled',
                            'default_value' => true,
                        ]
                    ]
                ],
                [
                    'name' => 'products_price',
                    'label' => esc_html__('Product Fields', GFExcel::$slug),
                    'type' => 'checkbox',

                    'choices' => [
                        [
                            'label' => esc_html__(
                                'Export prices as numeric fields, without currency symbol ($)',
                                GFExcel::$slug
                            ),
                            'name' => ProductField::SETTING_KEY,
                            'default_value' => false,
                        ]
                    ]
                ]
            ]
        ];

        $settings_sections[] = [
            'title' => esc_html__('Default Enabled Meta Fields', GFExcel::$slug ),
            'fields' => [
                [
                    'name' => 'enabled_metafields',
                    'description' => esc_html__(
                        'Select all meta fields that are enabled by default. Once you\'ve saved your form, these settings will not do anything any more.',
                        GFExcel::$slug
                    ),
                    'type' => 'checkbox',
                    'choices' => $this->meta_fields(),
                ]
            ]
        ];

	    if ( class_exists( 'GravityKit\GravityExport\GravityExport' ) ) {
		    $settings_sections[] = [
			    'title'       => '',
			    'description' => $this->get_gravityexport_message(),
			    'fields'      => [
				    [
					    'name'  => 'gravityexport-rocks',
					    'type'  => 'hidden',
					    'value' => 'You should try it!',
				    ],
			    ],
		    ];
	    }

        return $settings_sections;
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

	    if ( ( $action = rgget( 'gf_action' ) ) && ( $id = rgget( 'id' ) ) ) {
		    // trigger action
		    do_action( 'gfexcel_action_' . strtolower( trim( $action ) ), $id, $this );

		    // redirect back to same page without the action
		    wp_safe_redirect( remove_query_arg( 'gf_action' ) );

		    exit( 0 );
	    }

        add_action('gform_notification', [$this, 'handle_notification'], 10, 3);
        add_action('gform_after_email', [$this, 'remove_temporary_file'], 10, 13);
        add_filter('plugin_row_meta', [__CLASS__, 'plugin_row_meta'], 10, 2);
        add_filter('plugin_action_links', [__CLASS__, 'plugin_action_links'], 10, 2);
        add_filter('gform_form_actions', [__CLASS__, 'gform_form_actions'], 10, 2);
        add_filter('gform_post_form_duplicated', [$this, 'refresh_download_data'], 10, 2);
        add_filter('gform_entry_detail_meta_boxes', [__CLASS__, 'gform_entry_detail_meta_boxes'], 10, 3);
        add_filter('wp_before_admin_bar_render', [__CLASS__, 'admin_bar'], 20);
    }

    public function get_rating_message()
    {
        ob_start();
        ?>
        <div id="gravityexport-lite-rating" class="wrap gravityexport-lite-callout">
            <p><?php
		        printf(' ' . esc_html__(
				        'If you like the plugin, 📣 %slet others know%s! We already have %s active users. Let\'s get to %s by spreading the news!',
				        GFExcel::$slug
			        ),
			        '<strong><a href="https://wordpress.org/support/plugin/gf-entries-in-excel/reviews/?filter=5#new-post" target="_blank" title="' . esc_attr__( 'This link opens in a new window', GFExcel::$slug ) . '">',
			        '</a></strong>', esc_html( $this->getUsageCount() ), esc_html( $this->getUsageTarget() )
		        );
		        ?>
            </p>
        </div>
	    <?php
	    return ob_get_clean();
    }

	/**
	 * Return the plugin's icon for the plugin/form settings menu.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_menu_icon(): string {
		return '<svg style="height: 24px; width: 37px;" enable-background="new 0 0 226 148" height="148" viewBox="0 0 226 148" width="226" xmlns="http://www.w3.org/2000/svg"><path d="m176.8 118.8c-1.6 1.6-4.1 1.6-5.7 0l-5.7-5.7c-1.6-1.6-1.6-4.1 0-5.7l27.6-27.4h-49.2c-4.3 39.6-40 68.2-79.6 63.9s-68.2-40-63.9-79.6 40.1-68.2 79.7-63.9c25.9 2.8 48.3 19.5 58.5 43.5.6 1.5-.1 3.3-1.7 3.9-.4.1-.7.2-1.1.2h-9.9c-1.9 0-3.6-1.1-4.4-2.7-14.7-27.1-48.7-37.1-75.8-22.4s-37.2 48.8-22.4 75.9 48.8 37.2 75.9 22.4c15.5-8.4 26.1-23.7 28.6-41.2h-59.4c-2.2 0-4-1.8-4-4v-8c0-2.2 1.8-4 4-4h124.7l-27.5-27.5c-1.6-1.6-1.6-4.1 0-5.7l5.7-5.7c1.6-1.6 4.1-1.6 5.7 0l41.1 41.2c3.1 3.1 3.1 8.2 0 11.3z"/></svg>';
	}

	public function get_gravityexport_message()
    {
        ob_start();
        ?>
        <div id="gravityexport-additional-features" class="wrap gravityexport-lite-callout">
            <h2><?php esc_html_e('Upgrade to GravityExport for these useful features:', GFExcel::$slug); ?></h2>

            <div>
                <h3><?php esc_html_e( 'Save exports to Dropbox, FTP, &amp; local storage', GFExcel::$slug ); ?> 💾</h3>
                <p><?php esc_html_e( 'Automatically upload exports to Dropbox, a remote server using SFTP and FTP, or store locally.', GFExcel::$slug ); ?></p>
            </div>

            <div>
                <h3><?php esc_html_e( 'Filter exports with Conditional Logic', GFExcel::$slug ); ?> 😎</h3>
                <p><?php esc_html_e( 'Create advanced filters, including exporting entries created by only the currently logged-in user.', GFExcel::$slug ); ?></p>
            </div>

            <div>
                <h3><?php esc_html_e( 'Exports are ready for data analysis', GFExcel::$slug ); ?> 📊</h3>
                <p><?php esc_html_e( 'When analyzing data, you want fields with multiple values broken into multiple rows each with one value. If you work with data, you&rsquo;ll love this feature!', GFExcel::$slug ); ?></p>
            </div>

            <p>
                <a class="button button-hero button-cta" href="https://gravityview.co/extensions/gravityexport/"
                   target="_blank" title="<?php esc_attr_e( 'This link opens in a new window', GFExcel::$slug ); ?>"><?php esc_html_e('Gain Powerful Features with GravityExport', GFExcel::$slug); ?>️</a>
            </p>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Show row meta on the plugin screen.
     *
     * @param mixed $links Plugin Row Meta.
     * @param mixed $file Plugin Base file.
     * @return array
     */
    public static function plugin_row_meta($links, $file)
    {
        if (plugin_basename(GFEXCEL_PLUGIN_FILE) !== $file) {
            return $links;
        }

        return array_merge($links, [
            'docs' => '<a href="' . esc_url('https://gfexcel.com/docs/getting-started/') . '" aria-label="' . esc_attr__(
                    'Documentation',
                    GFExcel::$slug
                ) . '" target="_blank">' . esc_html__('Documentation', GFExcel::$slug) . '</a>',
            'donate' => '<a href="' . esc_url('https://www.paypal.me/GravityView') . '" aria-label="' . esc_attr__(
                    'Make a donation',
                    GFExcel::$slug
                ) . '">' . esc_html__('Make a donation', GFExcel::$slug) . '</a>',
        ]);
    }

    /**
     * Adds the settings link to the plugin row.
     * @since 1.8.0
     * @param string[] $actions The action links.
     * @param string $plugin_file The name of the plugin file.
     * @return string[] The new action links.
     */
    public static function plugin_action_links(
        array $actions,
        string $plugin_file
    ): array {
        if (plugin_basename(GFEXCEL_PLUGIN_FILE) !== $plugin_file) {
            return $actions;
        }

        // Already has GravityExport
	    if ( class_exists( 'GravityKit\GravityExport\GravityExport' ) ) {
	        return $actions;
	    }

	    // Lite is active
	    if ( array_key_exists( 'deactivate', $actions ) ) {
		    $actions[] = implode( '', [
			    '<a target="_blank" rel="nofollow noopener" href="https://gravityview.co/extensions/gravityexport/"><b>⚡️ ',
			    esc_html__( 'Gain Access to More Features', 'gk-gravityexport' ),
			    '</b></a>',
		    ] );
	    }

        return $actions;
    }

    public static function gform_form_actions($form_actions, $form_id)
    {
        $form_actions['download'] = array(
            'label' => __('Download', GFExcel::$slug),
            'title' => __('Download an Export', GFExcel::$slug),
            'url' => GFExcel::url($form_id),
            'menu_class' => 'download',
        );

        return $form_actions;
    }

	/**
	 * @inheritdoc
	 * @since 1.9.3
	 */
    public function form_settings_fields( $form ) {
	    return parent::form_settings_fields( $form );
    }

	public function form_settings($form)
    {
        //reads current form settings
        $settings = $this->get_form_settings($form);
        $this->set_settings($settings);

        if ($this->is_save_postback()) {
            $this->saveSettings($form);
            $form = \GFFormsModel::get_form_meta($form['id']);
        }

        if ($this->is_postback()) {
            if (!rgempty('regenerate_hash')) {
                $form = GFExcel::setHash($form['id']);
                \GFCommon::add_message(__('The Download URL has been regenerated.', GFExcel::$slug), false);
            } elseif (!rgempty('enable_download_url')) {
                $form = GFExcel::setHash($form['id']);
                \GFCommon::add_message(__('The Download URL has been enabled.', GFExcel::$slug), false);
            } elseif (!rgempty('disable_download_url')) {
                $form = GFExcel::setHash($form['id'], '');
                \GFCommon::add_message(__('The Download URL has been disabled.', GFExcel::$slug), false);
            }
        }

        \GFCommon::display_admin_message();
        printf(
            '<h3>%s</h3>',
            esc_html__(GFExcel::$name, GFExcel::$slug)
        );

	    printf( '<div class="gaddon-section__download_count">%s: <strong>%d</strong> %s</div>',
		    esc_html__( 'Download Count', GFExcel::$slug ),
		    $this->download_count( $form ),
		    '<a role="button" href="' . esc_url( add_query_arg( array( 'gf_action' => CountDownloads::ACTION_RESET ) ) ) . '">' . esc_html__( 'Reset count', GFExcel::$slug ) . '</a>'
	    );

        $this->downloadURLSettings($form);

        $this->downloadFileSettings($form);

        echo '<form method="post" id="gform-settings">';

        $this->securitySettings($form);

        $this->generalSettings($form);

        $this->sortableFields($form);

        if (method_exists($this, 'get_settings_renderer') && $this->get_settings_renderer() !== false) {
            echo $this->get_settings_renderer()->render_save_button();
        } else {
            $this->settings_save(['value' => esc_html__('Save Settings', GFExcel::$slug ) ]);
        }
        echo '</form>';
    }

	/**
	 * Adds the Download URL settings for the plugin.
	 * @since 1.8.14
	 * @param array $form The form information.
	 */
	function downloadFileSettings( $form ) {

		$url = GFExcel::url( $form['id'] );

		if ( ! $url ) {
		    return;
		}

	    echo '<div class="gaddon-section__download_file">';
		printf(
			'<h4 class="gaddon-section-title gf_settings_subgroup_title"><i class="dashicons dashicons-download"></i> %s</h4>',
			esc_html__( 'Download File', GFExcel::$slug )
		);
		echo '<form method="post" action="' . esc_url( $url ) . '" target="_blank">
            <label for="start_date">' . esc_html__( 'Select Date Range (optional)', GFExcel::$slug ) . ' ' .
		     gform_tooltip( 'export_date_range', '', true ) . '</label>' .
		     '<div class="download-block">
                <div class="date-field">
                    <input type="text" id="start_date" name="start_date" />
                    <label for="start_date">' . esc_html__( 'Start', 'gravityforms' ) . '</label>
                </div>
    
                <div class="date-field">
                    <input type="text" id="end_date" name="end_date" />
                    <label for="end_date">' . esc_html__( 'End', 'gravityforms' ) . '</label>
                </div>
                
                <div class="download-button">
                    <button class="button primary button-primary">' . esc_html__( 'Download', GFExcel::$slug ) . '</button>
                </div>
            </div>
        </form>';
		echo '</div>';
	}

	/**
	 * Adds the Download URL settings for the plugin.
	 * @since 1.8.14
	 * @param array $form The form information.
	 */
	function downloadURLSettings( $form ) {
		printf(
			'<h4 class="gaddon-section-title gf_settings_subgroup_title">%s</h4>',
			esc_html__( 'Download URL', GFExcel::$slug )
		);

		$url = GFExcel::url( $form['id'] );

		if ( ! $url ) {
			echo '<form method="post">';
			echo '<p>' .
			     esc_html__( 'The download URL is not (yet) enabled. Click the button to enable this feature.', GFExcel::$slug ) .
			     '</p>';

			echo "<input
                    type='submit'
                    name='enable_download_url'
                    class='button button-primary primary'
                    value='" . esc_html__( 'Enable Download', GFExcel::$slug ) . "'>";
			echo '</form>';

			// Download is disabled, so other settings are hidden.
			return;
		}

		echo '<form method="post">';
		printf(
			'<p>
            <input class="widefat" type="text" value="%s" readonly /><input
            onclick="%s"
            class="button white" type="submit" name="regenerate_hash"
            value="' . esc_html__( 'Regenerate URL', GFExcel::$slug ) . '"/>
            <input
            onclick="%s"
            class="button button-danger button-small alignright" type="submit" name="disable_download_url"
            value="' . esc_html__( 'Disable Download', GFExcel::$slug ) . '"/>
        </p>',
			$url,
			"return confirm('" . esc_js( 'This changes the download URL permanently!', GFExcel::$slug ) . "');",
			"return confirm('" . esc_js( 'This disables and removes the download URL!', GFExcel::$slug ) . "');"
		);
		echo '</form>';
	}

    /**
     * Handles the download of multiple forms as a bulk action.
     * @return bool
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function handle_bulk_actions()
    {
        if (!current_user_can('editor') &&
            !current_user_can('administrator') &&
            !\GFCommon::current_user_can_any('gravityforms_export_entries')) {
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
     * @param mixed[] $actions The current actions.
     * @return mixed[] The new actions.
     */
    public function bulk_actions($actions)
    {
        if( 'form_list' !== \GFForms::get_page() ) {
	        return $actions;
        }

        $actions[self::BULK_DOWNLOAD] = esc_html__('Download as one file', GFExcel::$slug);

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
     * @param mixed[] $form The form object.
     * @return int The number of downloads.
     */
    private function download_count($form)
    {
        if (array_key_exists("gfexcel_download_count", $form)) {
            return (int) $form["gfexcel_download_count"];
        }

        return 0;
    }

    /**
     * Helper method to make the sort options field.
     * @since 1.2.0
     * @param array $form The form object.
     */
    private function select_sort_field_options($form)
    {
        $this->settings_select([
            'name' => 'gfexcel_output_sort_field',
            'choices' => (new FieldsRepository($form))->getSortFieldOptions(),
            'default_value' => $this->getFormRepository()->getSortField(),
        ]);
    }

    /**
     * Helper method to add the select order options.
     * @since 1.2.0
     */
    private function select_order_options(): void
    {
        $this->settings_select([
            'name' => 'gfexcel_output_sort_order',
            'choices' => [
                ['value' => 'ASC', 'label' => esc_html__('Ascending', GFExcel::$slug)],
                ['value' => 'DESC', 'label' => esc_html__('Descending', GFExcel::$slug)],
            ],
            'default_value' => $this->getFormRepository()->getSortOrder(),
        ]);
    }

    /**
     * Helper method to actually save the settings.
     * @since 1.2.0
     * @param mixed[] $form The form object.
     */
    private function saveSettings($form): void
    {

        // get_posted_settings() doesn't capture all the settings added using the `gfexcel_general_settings` filter,
        // so we check for others here.
        $gfexcel_keys = array_filter(array_keys($_POST), static function ($key) {
            return ( stripos($key, 'gfexcel_') === 0 || stripos($key, 'gravityexport') === 0 );
        });

        $form_meta = \GFFormsModel::get_form_meta($form['id']);

        foreach ($gfexcel_keys as $key) {
            $form_meta[$key] = $_POST[$key];
        }

        foreach ($this->get_posted_settings() as $key => $value) {
            if (($key === FieldsRepository::KEY_DISABLED_FIELDS) && is_array($value)) {
                $value = implode(',', array_keys(array_filter($value)));
            }

            if ($key === GFExcel::KEY_CUSTOM_FILENAME) {
                $value = preg_replace('/\.(' . GFExcel::getPluginFileExtensions(true) . ')$/is', '', $value);
                $value = preg_replace('/[^a-z0-9_-]+/i', '_', $value);
            }
            $form_meta[$key] = $value;
        }

        \GFFormsModel::update_form_meta($form['id'], $form_meta);
        \GFCommon::add_message(__('The settings have been saved.', GFExcel::$slug));
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

    /**
     * {@inheritdoc}
     */
    public function get_form_settings($form)
    {
        $settings = array_filter((array) parent::get_form_settings($form));

        // get_posted_settings() doesn't capture all the settings added using the `gfexcel_general_settings` filter,
        // so we add the values back in here.
        return array_merge($settings, array_reduce(array_keys($form), function ($settings, $key) use ($form, $extra_settings) {

            if ( stripos($key, 'gfexcel_') === 0 || stripos($key, 'gravityexport') === 0 ) {
                $settings[$key] = $form[$key];
            }

            return $settings;
        }, []));
    }

    /**
     * Adds the security settings for the plugin.
     * @since 1.7.0
     * @param array $form The form information.
     */
    private function securitySettings($form)
    {
        $this->settings([
            [
                'title' => __('Security Settings', GFExcel::$slug),
                'fields' => [
                    [
                        'name' => GFExcelConfigConstants::GFEXCEL_DOWNLOAD_SECURED,
                        'label' => esc_html__('Download Permissions', GFExcel::$slug),
                        'type' => 'select',
                        'description' => sprintf( esc_html__( 'If set to "Everyone can download", anyone with the link can download. If "Logged-in users who have \'Export Entries\' access" is selected, users must be logged-in and have the %s capability.', GFExcel::$slug ), '<code>gravityforms_export_entries</code>' ),
                        'default_value' => GFExcel::isAllSecured(),
                        'choices' => array_filter([
                            GFExcel::isAllSecured() ? null :
                                [
                                    'name' => GFExcelConfigConstants::GFEXCEL_DOWNLOAD_SECURED,
                                    'label' => __('Everyone can download', GFExcel::$slug),
                                    'value' => false,
                                ],
                            [
                                'name' => GFExcelConfigConstants::GFEXCEL_DOWNLOAD_SECURED,
                                'label' => __('Logged-in users who have "Export Entries" access', GFExcel::$slug),
                                'value' => true,
                            ],
                        ]),
                    ]
                ]
            ]
        ]);
    }


    /**
     * Adds the general settings for the plugin.
     * @since 1.0
     * @param array $form The form information.
     */
    private function generalSettings($form)
    {
        $this->settings(apply_filters('gfexcel_general_settings', [
            [
                'title' => __('General Settings', GFExcel::$slug),
                'fields' => [
                    [
                        'name' => 'enable_notes',
                        'label' => esc_html__('Include Entry Notes', GFExcel::$slug),
                        'type' => 'checkbox',
                        'choices' => [
                            [
                                'name' => GFExcel::KEY_ENABLED_NOTES,
                                'label' => esc_html__('Yes, enable the notes for every entry', GFExcel::$slug),
                                'value' => '1',
                                'default_value' => $this->enabled_notes($form),
                            ]
                        ],
                    ],
                    [
                        'name' => 'order_by',
                        'type' => 'callback',
                        'label' => esc_html__('Order By', GFExcel::$slug),
                        'callback' => function () use ($form) {
                            $this->select_sort_field_options($form);
                            echo ' ';
                            $this->select_order_options();
                        }
                    ],
                    [
                        'name' => GFExcelConfigConstants::GFEXCEL_RENDERER_TRANSPOSE,
                        'type' => 'radio',
                        'label' => esc_html__('Column Position', GFExcel::$slug),
                        'default_value' => \rgar( $form , GFExcelConfigConstants::GFEXCEL_RENDERER_TRANSPOSE, 0 ),
                        'choices' => [
                            [
                                'name' => GFExcelConfigConstants::GFEXCEL_RENDERER_TRANSPOSE,
                                'label' => esc_html__('At the top (normal)', GFExcel::$slug),
                                'value' => 0,
                            ],
                            [
                                'name' => GFExcelConfigConstants::GFEXCEL_RENDERER_TRANSPOSE,
                                'label' => esc_html__('At the left (transposed)', GFExcel::$slug),
                                'value' => 1,
                            ]
                        ]
                    ],
                    [
                        'label' => esc_html__('Custom Filename', GFExcel::$slug),
                        'type' => 'text',
                        'name' => GFExcel::KEY_CUSTOM_FILENAME,
                        'value' => \rgar( $form, GFExcel::KEY_CUSTOM_FILENAME ),
                        'description' => esc_html__(
                            'Only letters, numbers and dashes are allowed. The rest will be stripped. Leave empty for default.',
                            GFExcel::$slug
                        ),
                    ],
                    [
                        'label' => esc_html__('File Extension', GFExcel::$slug),
                        'type' => 'select',
                        'name' => GFExcel::KEY_FILE_EXTENSION,
                        'default_value' => \rgar( $form, GFExcel::KEY_FILE_EXTENSION, 'xlsx' ),
                        'description' => sprintf( esc_html__('Note: You may override the file type by adding the desired extension (%s) to the end of the Download URL.', GFExcel::$slug ), '<code>.' . implode( '</code>, <code>.', GFExcel::getPluginFileExtensions() ) . '</code>' ),
                        'choices' => array_map(static function ($extension) {
                            return
                                [
                                    'name' => GFExcel::KEY_FILE_EXTENSION,
                                    'label' => '.' . $extension,
                                    'value' => $extension,
                                ];
                        }, GFExcel::getPluginFileExtensions()),
                    ],
                    [
                        'label' => esc_html__('Attach Single Entry to Notification', GFExcel::$slug),
                        'type' => 'select',
                        'name' => GFExcel::KEY_ATTACHMENT_NOTIFICATION,
                        'default_value' => \rgar( $form, GFExcel::KEY_ATTACHMENT_NOTIFICATION ),
                        'choices' => $this->getNotifications(),
                    ],
                ],
            ]
        ]));
    }

    /**
     * Adds the sortable fields section to the settings page
     * @param mixed[] $form The form object.
     */
    private function sortableFields($form)
    {
        $repository = new FieldsRepository($form);
        $disabled_fields = $repository->getDisabledFields();
        $all_fields = $repository->getFields($unfiltered = true);

        $active_fields = $inactive_fields = [];
        foreach ($all_fields as $field) {
            $array_name = in_array($field->id, $disabled_fields) ? 'inactive_fields' : 'active_fields';
            array_push($$array_name, $field);
        }
        $active_fields = $repository->sortFields($active_fields);

        $this->single_section([
            'title' => esc_html__('Field Settings', GFExcel::$slug),
            'class' => 'sortfields',
            'description' => esc_html__( 'Drag & drop fields to re-order them in the exported file.', GFExcel::$slug ),
            'fields' => [
                [
                    'label' => esc_html__('Disabled Fields', GFExcel::$slug),
                    'name' => 'gfexcel_disabled_fields',
                    'move_to' => 'gfexcel_enabled_fields',
                    'type' => 'sortable',
                    'class' => 'fields-select',
                    'side' => 'left',
                    'value' => \rgar( $form, 'gfexcel_disabled_fields', '' ),
                    'choices' => array_map(function (\GF_Field $field) {
                        $label = gf_apply_filters([
                            'gfexcel_field_label',
                            $field->get_input_type(),
                            $field->formId,
                            $field->id
                        ], $field->get_field_label(true, ''), $field);

                        return [
                            'value' => $field->id,
                            'label' => $label,
                        ];
                    }, $inactive_fields),
                ],
                [
                    'label' => esc_html__('Enabled Fields', GFExcel::$slug),
                    'name' => 'gfexcel_enabled_fields',
                    'value' => \rgar( $form, 'gfexcel_enabled_fields', '' ),
                    'move_to' => 'gfexcel_disabled_fields',
                    'type' => 'sortable',
                    'class' => 'fields-select',
                    'side' => 'right',
                    'choices' => array_map(static function (\GF_Field $field) {
                        $label = gf_apply_filters([
                            'gfexcel_field_label',
                            $field->get_input_type(),
                            $field->formId,
                            $field->id
                        ], $field->get_field_label(true, ''), $field);

                        return [
                            'value' => $field->id,
                            'label' => $label,
                        ];
                    }, $active_fields),
                ],
            ],
        ]);
    }

    /**
     * Renders the html for a single sortable fields.
     * I don't like this inline html approach Gravity Forms uses.
     * @param mixed[] $field The field object.
     * @param bool $echo Whether to echo or return.
     * @return string The HTML.
     */
    public function settings_sortable($field, $echo = true)
    {
        $attributes = $this->get_field_attributes($field);
        $name = '' . esc_attr($field['name']);
        $value = $field instanceof Base
            ? $field->get_value()
            : \rgar($field, 'value'); //comma-separated list from database

        // If no choices were provided and there is a no choices message, display it.
        if ((empty($field['choices']) || !\rgar($field, 'choices')) && \rgar($field, 'no_choices')) {
            $html = $field['no_choices'];
        } else {
            $html = sprintf(
                '<input type="hidden" name="%s" value="%s">',
                method_exists($this, 'get_settings_renderer') && $this->get_settings_renderer() !== false
                    ? $this->get_settings_renderer()->get_input_name_prefix() . '_' . $name
                    : '_gaddon_setting_' . $name,
                $value
            );
            $html .= sprintf(
                '<ul id="%1$s" %2$s data-send-to="%4$s">%3$s</ul>',
                $name, implode(' ', $attributes), implode("\n", array_map(static function ($choice): string {
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

            $html .= \rgar($field, 'after_select');
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
     * @param mixed[] $field The field object.
     */
    public function single_setting_row_sortable($field)
    {
        $display = \rgar($field, 'hidden') || \rgar($field, 'type') == 'hidden' ? 'style="display:none;"' : '';

        // Prepare setting description.
        $description = \rgar($field,
            'description') ? '<span class="gf_settings_description">' . $field['description'] . '</span>' : null;

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
     */
    private function sortable_script(array $ids, $connector_class = 'connected-sortable')
    {
        $ids = implode(', ', array_map(function ($id) {
            return '#' . $id;
        }, $ids));

        wp_add_inline_script('gfexcel-js',
            '(function($) { $(document).ready(function() { gfexcel_sortable(\'' . $ids . '\',\'' . $connector_class . '\'); }); })(jQuery);');
    }

    /**
     * Add JavaScript and custom CSS to the page.
     */
    public function register_assets()
    {
	    if ( 'gf_edit_forms' !== rgget( 'page' ) || 'gf-entries-in-excel' !== rgget( 'subview' ) ) {
		    return;
	    }

	    $this->sortable_script( [ 'gfexcel_enabled_fields', 'gfexcel_disabled_fields' ], 'fields-select' );
    }

    /**
     * Get the assets path
     * @return string
     */
    public static function assets()
    {
        return plugin_dir_url(dirname(__DIR__) . '/gfexcel.php');
    }

    /**
     * Registers the meta boxes for the entry detail page.
     * @since 1.7.0
     * @param array $meta_boxes The metaboxes
     * @param array $lead the lead data
     * @param array $form the form data
     * @return array All the meta boxes
     */
    public static function gform_entry_detail_meta_boxes($meta_boxes, $lead, $form)
    {
        if (GFExcel::url($form['id'])) {
            $meta_boxes[] = [
                'title' => esc_html__( 'GravityExport Lite', GFExcel::$slug),
                'callback' => [__CLASS__, 'single_entry_download'],
                'context' => 'side',
                'priority' => 'high',
            ];
        }

        return $meta_boxes;
    }

    public function scripts()
    {
        return array_merge(parent::scripts(), [
            [
                'handle' => 'jquery-ui-sortable',
                'enqueue' => [
                    [
                        'admin_page' => 'form_settings',
                        'tab' => GFExcel::$slug,
                    ]
                ],
            ],
            [
                'handle' => 'gfexcel-js',
                'src' => self::assets() . 'public/js/gfexcel.js',
                'enqueue' => [
                    [
                        'admin_page' => 'form_settings',
                        'tab' => GFExcel::$slug,
                    ]
                ],
                'deps' => ['jquery', 'jquery-ui-sortable', 'jquery-ui-datepicker'],
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
                        'tab' => GFExcel::$slug,
                    ],
                    [
                        'admin_page' => 'plugin_settings',
                        'tab' => GFExcel::$slug,
                    ],
                ],
            ],
        ]);
    }

    /**
     * Adds the attachment to the notification.
     * @param mixed[] $notification The notification object.
     * @param mixed[] $form The form object.
     * @param mixed[] $entry The entry object.
     * @return mixed[] The notification with attachment.
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function handle_notification($notification, $form, $entry)
    {
        // get notification to add to by form setting
	    if ( $this->getFormRepository( $form['id'] )->getSelectedNotification() !== \rgar( $notification, 'id' ) ) {
		    // Not the right notification
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

    /**
     * Returns the notification options list.
     * @since $ver$
     * @return mixed[] The notification options.
     */
    private function getNotifications(): array
    {
        $options = [['label' => __('Select a Notification', GFExcel::$slug), 'value' => '']];
        foreach ($this->getFormRepository()->getNotifications() as $key => $notification) {
            $options[] = ['label' => \rgar($notification, 'name', __('Unknown')), 'value' => $key];
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

    /**
     * Updates download data for a duplicated form.
     * @since 1.7.0
     * @param int $form_id the ID of the duplicated form
     * @param int $new_id the ID of the new form.
     */
    public function refresh_download_data($form_id, $new_id)
    {
        // new hash to prevent doubles.
        GFExcel::setHash($new_id);
        // reset the download counter
        do_action('gfexcel_action_' . CountDownloads::ACTION_RESET, $new_id);
    }

    /**
     * Adds a download button for a single entry on the entry detail page.
     * @since 1.7.0
     * @param array $args arguments from metabox.
     * @param array $metabox the metabox information.
     */
    public static function single_entry_download($args, $metabox)
    {
        $form = \rgar($args, 'form', []);
        $entry = \rgar($args, 'entry', []);

        $html = '<div class="gfexcel_entry_download">
            <p>%s</p>
            <a href="%s" class="button button-primary primary">%s</a>
            <a href="%s" class="button button-secondary">%s</a>
        </div>';

        $url = GFExcel::url($form['id']);

        printf(
            $html,
	        esc_html__('Download this single entry as a file.', GFExcel::$slug),
            esc_url( $url . '.xlsx?entry=' . $entry['id'] ),
            esc_html__('Excel', GFExcel::$slug),
            esc_url( $url . '.csv?entry=' . $entry['id'] ),
	        esc_html__('CSV', GFExcel::$slug)
        );
    }

    private function plugin_settings_description()
    {
        return sprintf(
            '<p>%s</p>',
            esc_html__(
                'These are global settings for new forms. You can overwrite them per form using the available hooks.',
                GFExcel::$slug
            ));
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
        }

        return $this->get_plugin_setting('notes_enabled');
    }

    /**
     * Get the current usage count from the plugin repo.
     * Info is cached for a week.
     *
     * @param bool $number_format Whether to return a formatted number.
     *
     * @return string
     */
    private function getUsageCount( $number_format = true )
    {
        if (!$active_installs = get_transient(GFExcel::$slug . '-active_installs')) {
            if (!function_exists('plugins_api')) {
                require_once(ABSPATH . 'wp-admin/includes/plugin-install.php');
            }
            $data = plugins_api('plugin_information', [
                'slug' => GFExcel::$slug,
                'fields' => ['active_installs' => true],
            ]);

            if ($data instanceof \WP_Error || !is_object($data) || !isset($data->active_installs)) {
                return __('countless', GFExcel::$slug);
            }
            $active_installs = $data->active_installs;
            set_transient(GFExcel::$slug . '-active_installs', $active_installs, WEEK_IN_SECONDS );
        }

        return $number_format ? number_format_i18n( $active_installs, 0 ) : $active_installs;
    }

    /**
     * Get a target usage count for the plugin repo.
     *
     * @param bool $number_format Whether to return a formatted number.
     *
     * @return string
     */
    private function getUsageTarget( $number_format = true )
    {
	    $current_count = $this->getUsageCount( false );
	    if ( $current_count === __( 'countless', GFExcel::$slug ) ) {
		    return __( 'even more', GFExcel::$slug );
	    }

	    // What step should we reach for?
	    $next_level = 1000;

	    $usage_target = ( ( $current_count / $next_level ) + 1 ) * $next_level;

	    return $number_format ? number_format_i18n( $usage_target ) : $usage_target;
    }

    /**
     * Adds the export links to the admin bar.
     * @since 1.7.0
     */
    public static function admin_bar()
    {
        // only show links if the user has the rights for exporting.
        if (!\GFCommon::current_user_can_any('gravityforms_export_entries')) {
            return;
        }

        /**
         * @var  \WP_Admin_Bar $wp_admin_bar
         */
        global $wp_admin_bar;

        // get all recent form id's.
        $form_ids = array_reduce(array_keys($wp_admin_bar->get_nodes()), static function (array $output, $key) {
            if (preg_match('/gform-form-(\d)$/i', $key, $matches)) {
                $output[] = (int) $matches[1];
            }

            return $output;
        }, []);

        // add download URL to every form that has a hash.
        foreach ($form_ids as $id) {
            $url = GFExcel::url($id);
            if ($url) {
                $wp_admin_bar->add_node([
                    'id' => 'gfexcel-form-' . $id . '-download',
                    'parent' => 'gform-form-' . $id,
                    'title' => esc_html__('Download', GFExcel::$slug),
                    'href' => trailingslashit( esc_url( $url ) ),
                ]);
            }
        }
    }

    /**
     * @inheritdoc
     *
     * Full path is a level deeper than other add-ons.
     *
     * @since 1.8.0
     */
    public function update_path(): void
    {
        $path_dirname = dirname($this->_path);
        if ($path_dirname !== '.') {
            $full_path_dirname = basename(dirname($this->_full_path, 2));
            if ($path_dirname !== $full_path_dirname) {
                $this->_path = trailingslashit($full_path_dirname) . basename($this->_path);
            }
        }
    }

	/**
	 * Helper function to retrieve the required form repository, with micro cache.
	 *
	 * @since 1.10
	 *
	 * @param string|int|null $form_id The form id.
	 *
	 * @return FormsRepository The form repository.
	 */
	private function getFormRepository( $form_id = null ): FormsRepository {
		$form_id = (int) ( $form_id ?: rgget( 'id' ) );
		if ( ! isset( $this->form_repositories[ $form_id ] ) ) {
			$this->form_repositories[ $form_id ] = new FormsRepository( $form_id );
		}

		return $this->form_repositories[ $form_id ];
	}
}
